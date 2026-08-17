#!/usr/bin/env python3
"""Generate the privacy-minimised admin statistics JSON from SQLite."""

import argparse
import datetime as dt
import ipaddress
import json
import os
import sqlite3
import tempfile
from pathlib import Path


DATE_COLUMNS = ("date", "day", "stat_date")
PAGEVIEW_COLUMNS = ("external_pageviews", "pageviews", "page_views", "hits")
REQUEST_COLUMNS = ("external_requests", "requests", "total_requests", "hits")
PAGE_COLUMNS = ("page", "path", "url", "request")
IP_COLUMNS = ("ip", "ip_address", "visitor_ip")
COUNT_COLUMNS = ("pageviews", "page_views", "requests", "hits", "count")
PAGE_NAMES = {
    "/oppdrag": "Spin the wheel – oppdrag",
    "/visitasjon": "Visitasjonsrullett",
    "/tv": "TV-guide",
    "/print": "TV-guide – utskrift Ringerike",
    "/print-ilseng": "TV-guide – utskrift Ilseng",
    "/bonnetider": "Bønnetider Ringerike",
    "/bonnetider-ilseng": "Bønnetider Ilseng",
    "/bonnetider/utskrift": "Bønnetider Ringerike – utskrift",
    "/bonnetider-ilseng/utskrift": "Bønnetider Ilseng – utskrift",
    "/nyheter": "Nyheter",
    "/fagstoff": "Fagstoff",
    "/dagen-i-dag": "Dagen i dag",
    "/vaer": "Vær Ringerike",
    "/vaer-ilseng": "Vær Ilseng",
    "/fotball": "Fotball",
    "/eliteserien": "Eliteserien",
    "/premier-league": "Premier League",
    "/forslag-og-tilbakemeldinger": "Forslag og tilbakemeldinger",
}


def column(columns, candidates, table):
    for name in candidates:
        if name in columns:
            return name
    raise RuntimeError(f"Fant ingen av kolonnene {', '.join(candidates)} i {table}")


def columns(connection, table):
    rows = connection.execute(f'PRAGMA table_info("{table}")').fetchall()
    if not rows:
        raise RuntimeError(f"Påkrevd tabell mangler: {table}")
    return {row[1] for row in rows}


def contains_ip(value):
    text = str(value).replace("[", " ").replace("]", " ")
    for token in text.replace("/", " ").replace("?", " ").replace("=", " ").split():
        try:
            ipaddress.ip_address(token.strip(":,;"))
            return True
        except ValueError:
            pass
    return False


def scalar(connection, sql, parameters=()):
    value = connection.execute(sql, parameters).fetchone()[0]
    return max(0, int(value or 0))


def page_name(path):
    if path.startswith("/dagen-i-dag/"):
        return "Dagen i dag"
    if path in PAGE_NAMES:
        return PAGE_NAMES[path]
    words = path.strip("/").replace("-", " ").replace("_", " ")
    return words.capitalize() if words else "Forside"


def top_pages(connection, latest_date):
    if not connection.execute("SELECT 1 FROM sqlite_master WHERE type='table' AND name='daily_page_ip_stats'").fetchone():
        return None
    required = {"date", "path", "ip", "pageviews"}
    if not required.issubset(columns(connection, "daily_page_ip_stats")):
        return None
    result = {}
    for days in (1, 7, 30):
        first = latest_date - dt.timedelta(days=days - 1)
        rows = connection.execute(
            "SELECT path, SUM(pageviews) views, COUNT(DISTINCT ip) visitors "
            "FROM daily_page_ip_stats WHERE date BETWEEN ? AND ? "
            "GROUP BY path ORDER BY views DESC, visitors DESC, path ASC",
            (first.isoformat(), latest_date.isoformat()),
        ).fetchall()
        result[str(days)] = {
            "from": first.isoformat(), "to": latest_date.isoformat(),
            "pages": [{"name": page_name(row["path"]), "path": row["path"],
                       "pageviews": max(0, int(row["views"] or 0)),
                       "unique_visitors": max(0, int(row["visitors"] or 0))} for row in rows],
        }
    return result


def table_exists(connection, table):
    return connection.execute("SELECT 1 FROM sqlite_master WHERE type='table' AND name=?", (table,)).fetchone() is not None


def human_periods(connection, latest_date):
    """Read the collector's privacy-limited, classified log aggregates."""
    required = {'date', 'category', 'metric', 'count'}
    if not table_exists(connection, 'daily_traffic_classification_stats') or not required.issubset(columns(connection, 'daily_traffic_classification_stats')):
        return None
    if not table_exists(connection, 'daily_page_ip_stats'):
        return None
    return {str(days): human_periods_for_days(connection, latest_date, days) for days in (1, 7, 30)}


def daily_human_statistics(connection, latest_date):
    """Export all 60 retained calendar days, including days without traffic."""
    dates = [latest_date - dt.timedelta(days=offset) for offset in range(59, -1, -1)]
    result = {}
    for day in dates:
        value = day.isoformat()
        period = human_periods_for_days(connection, day, 1)
        pages = connection.execute(
            "SELECT path, SUM(pageviews) views, COUNT(DISTINCT ip) visitors FROM daily_page_ip_stats "
            "WHERE date = ? GROUP BY path ORDER BY views DESC, visitors DESC, path ASC", (value,)).fetchall()
        result[value] = {
            **period,
            'pages': [{'name': page_name(row['path']), 'path': row['path'],
                       'pageviews': max(0, int(row['views'] or 0)),
                       'unique_visitors': max(0, int(row['visitors'] or 0))} for row in pages],
        }
    return result


def human_periods_for_days(connection, latest_date, days):
    first = latest_date - dt.timedelta(days=days - 1)
    bounds = (first.isoformat(), latest_date.isoformat())
    classified = {row['category']: max(0, int(row['count'] or 0)) for row in connection.execute(
        "SELECT category, SUM(count) count FROM daily_traffic_classification_stats "
        "WHERE date BETWEEN ? AND ? AND metric = 'requests' GROUP BY category", bounds)}
    pageviews = scalar(connection, "SELECT SUM(pageviews) FROM daily_page_ip_stats WHERE date BETWEEN ? AND ?", bounds)
    visitors = scalar(connection, "SELECT COUNT(DISTINCT ip) FROM daily_page_ip_stats WHERE date BETWEEN ? AND ?", bounds)
    sessions = scalar(connection, "SELECT SUM(count) FROM daily_traffic_classification_stats "
                      "WHERE date BETWEEN ? AND ? AND category = 'human' AND metric = 'sessions'", bounds)
    single_page_candidates = scalar(connection, "SELECT SUM(count) FROM daily_traffic_classification_stats "
                                    "WHERE date BETWEEN ? AND ? AND category = 'other' AND metric = 'single_page_candidates'", bounds)
    prints = scalar(connection, "SELECT SUM(pageviews) FROM daily_page_ip_stats WHERE date BETWEEN ? AND ? "
                     "AND (path = '/print' OR path = '/print-ilseng' OR path LIKE '%/utskrift')", bounds)
    raw_requests = sum(classified.values())
    known_automated = sum(classified.get(category, 0) for category in ('bot', 'monitoring', 'scanner', 'excluded'))
    return {
        'from': bounds[0], 'to': bounds[1], 'suspected_human_pageviews': pageviews,
        'suspected_visitors': visitors, 'sessions': sessions, 'print_pageviews': prints,
        'traffic_quality': {
            'raw_requests': raw_requests, 'known_automated_technical_requests': known_automated,
            'known_bot': classified.get('bot', 0), 'monitoring': classified.get('monitoring', 0),
            'scanner': classified.get('scanner', 0), 'other': classified.get('other', 0),
            'excluded': classified.get('excluded', 0), 'single_page_candidates': single_page_candidates,
        },
    }


def generate(database, test_data=False):
    connection = sqlite3.connect(f"file:{Path(database).resolve()}?mode=ro", uri=True)
    connection.row_factory = sqlite3.Row
    try:
        daily = columns(connection, "daily_stats")
        pages = columns(connection, "daily_page_stats")
        ips = columns(connection, "daily_ip_stats")
        daily_date = column(daily, DATE_COLUMNS, "daily_stats")
        daily_views = column(daily, PAGEVIEW_COLUMNS, "daily_stats")
        daily_requests = column(daily, REQUEST_COLUMNS, "daily_stats")
        page_date = column(pages, DATE_COLUMNS, "daily_page_stats")
        page_path = column(pages, PAGE_COLUMNS, "daily_page_stats")
        page_count = column(pages, COUNT_COLUMNS, "daily_page_stats")
        ip_date = column(ips, DATE_COLUMNS, "daily_ip_stats")
        ip_column = column(ips, IP_COLUMNS, "daily_ip_stats")

        latest = connection.execute(
            f'SELECT MAX("{daily_date}") FROM daily_stats'
        ).fetchone()[0]
        if not latest:
            raise RuntimeError("daily_stats inneholder ingen dager")
        latest_date = dt.date.fromisoformat(str(latest))
        first_date = latest_date - dt.timedelta(days=6)
        bounds = (first_date.isoformat(), latest_date.isoformat())
        latest_views = scalar(connection, f'SELECT SUM("{daily_views}") FROM daily_stats WHERE "{daily_date}" = ?', (latest,))
        unique_visitors = scalar(connection, f'SELECT COUNT(DISTINCT "{ip_column}") FROM daily_ip_stats WHERE "{ip_date}" = ?', (latest,))
        week_views = scalar(connection, f'SELECT SUM("{daily_views}") FROM daily_stats WHERE "{daily_date}" BETWEEN ? AND ?', bounds)
        week_requests = scalar(connection, f'SELECT SUM("{daily_requests}") FROM daily_stats WHERE "{daily_date}" BETWEEN ? AND ?', bounds)
        page_rows = connection.execute(
            f'SELECT "{page_path}" AS path, SUM("{page_count}") AS views '
            f'FROM daily_page_stats WHERE "{page_date}" BETWEEN ? AND ? '
            f'GROUP BY "{page_path}" ORDER BY views DESC', bounds
        ).fetchall()
        top = next((row for row in page_rows if row["path"] and not contains_ip(row["path"])), None)
        if top is None:
            raise RuntimeError("Fant ingen side uten IP-adresse for perioden")

        payload = {
            "schema_version": 2,
            "generated_at": dt.datetime.now(dt.timezone.utc).replace(microsecond=0).isoformat(),
            "test_data": bool(test_data),
            "latest_day": {"date": latest_date.isoformat(), "pageviews": latest_views, "unique_visitors": unique_visitors},
            "last_7_days": {
                "from": first_date.isoformat(), "to": latest_date.isoformat(),
                "pageviews": week_views, "requests": week_requests,
                "top_page": {"path": str(top["path"]), "pageviews": max(0, int(top["views"] or 0))},
            },
        }
        payload["top_pages"] = top_pages(connection, latest_date)
        periods = human_periods(connection, latest_date)
        if periods is not None:
            payload = {
                'schema_version': 3,
                'generated_at': payload['generated_at'],
                'test_data': payload['test_data'],
                'periods': periods,
                'top_pages': payload['top_pages'],
                'daily': daily_human_statistics(connection, latest_date),
            }
        return payload
    finally:
        connection.close()


def atomic_write(output, payload):
    target = Path(output)
    target.parent.mkdir(parents=True, exist_ok=True)
    fd, temporary = tempfile.mkstemp(prefix=f".{target.name}.", dir=target.parent)
    try:
        with os.fdopen(fd, "w", encoding="utf-8") as handle:
            json.dump(payload, handle, ensure_ascii=False, indent=2)
            handle.write("\n")
            handle.flush()
            os.fsync(handle.fileno())
        os.chmod(temporary, 0o640)
        os.replace(temporary, target)
    except BaseException:
        try:
            os.unlink(temporary)
        except FileNotFoundError:
            pass
        raise


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("database", help="sti til historikk.sqlite3")
    parser.add_argument("output", help="sti til admin-summary.json")
    parser.add_argument("--test-data", action="store_true", help="merk resultatet tydelig som lokale testdata")
    args = parser.parse_args()
    atomic_write(args.output, generate(args.database, args.test_data))


if __name__ == "__main__":
    main()
