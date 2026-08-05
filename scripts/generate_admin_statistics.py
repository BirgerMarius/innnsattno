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

        return {
            "schema_version": 1,
            "generated_at": dt.datetime.now(dt.timezone.utc).replace(microsecond=0).isoformat(),
            "test_data": bool(test_data),
            "latest_day": {"date": latest_date.isoformat(), "pageviews": latest_views, "unique_visitors": unique_visitors},
            "last_7_days": {
                "from": first_date.isoformat(), "to": latest_date.isoformat(),
                "pageviews": week_views, "requests": week_requests,
                "top_page": {"path": str(top["path"]), "pageviews": max(0, int(top["views"] or 0))},
            },
        }
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
