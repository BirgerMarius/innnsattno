#!/usr/bin/env python3
"""Build privacy-limited human traffic and technical aggregates from access logs."""

import argparse
import datetime as dt
import gzip
import ipaddress
import os
import re
import sqlite3
from collections import Counter, defaultdict
from dataclasses import dataclass
from pathlib import Path
from urllib.parse import unquote, urlsplit
from zoneinfo import ZoneInfo


LOG_PATTERN = re.compile(
    r'^(?P<ip>\S+) \S+ \S+ \[(?P<time>[^]]+)] "(?P<method>\S+) (?P<target>\S+) [^"]+" '
    r'(?P<status>\d{3}) \S+ "[^"]*" "(?P<agent>[^"]*)"'
)
BOT_PATTERN = re.compile(
    r'claudebot|serankingbacklinksbot|amazonbot|googlebot|applebot|bingbot|ahrefsbot|'
    r'wp-safe-scanner|bot|crawler|spider|scanner|slurp|headless|lighthouse|preview|'
    r'curl|wget|python|go-http-client|libwww|httpclient|nikto|sqlmap|masscan|zgrab', re.I,
)
MONITOR_PATTERN = re.compile(r'uptime[ _-]?kuma|uptime|monitor', re.I)
TECHNICAL_PREFIXES = (
    '/admin', '/adm', '/api', '/statistikk', '/storage', '/vendor', '/_debugbar',
    '/login', '/logout', '/telescope', '/horizon', '/.env', '/wp-', '/xmlrpc',
)
SCANNER_PATH_PATTERN = re.compile(
    r'/(?:wp-(?:admin|content|includes)|admin\.php|wso\.php|phpmyadmin|\.git|cgi-bin|'
    r'boaform|shell|vendor/phpunit|eval-stdin\.php)', re.I,
)
STATIC_EXTENSIONS = {
    '.css', '.js', '.map', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.webp', '.ico',
    '.woff', '.woff2', '.ttf', '.eot', '.pdf', '.xml', '.txt', '.json', '.zip', '.mp4',
}
CANONICAL_PATHS = {
    '/': '/tv', '/guide': '/tv', '/tvguide': '/tv', '/tv-guide': '/tv', '/tv-guiden': '/tv',
    '/ilseng': '/print-ilseng', '/football': '/fotball',
}
SESSION_GAP = dt.timedelta(minutes=30)
OSLO_TIMEZONE = ZoneInfo('Europe/Oslo')


@dataclass
class Event:
    when: dt.datetime
    day: str
    ip: str
    agent: str
    target: str
    path: str | None
    status: int
    kind: str


def target_path(target):
    try:
        return unquote(urlsplit(target).path)
    except ValueError:
        return None


def canonical_public_path(target):
    path = target_path(target)
    if path is None:
        return None
    path = re.sub(r'/+', '/', path).rstrip('/') or '/'
    lower = path.lower()
    if lower == '/index.php' or lower.startswith('/index.php/'):
        return None
    if any(lower == prefix or lower.startswith(prefix + '/') for prefix in TECHNICAL_PREFIXES):
        return None
    if lower == '/test' or lower.endswith('/test'):
        return None
    if Path(lower).suffix in STATIC_EXTENSIONS or lower.startswith('/favicon') or lower == '/robots.txt':
        return None
    return CANONICAL_PATHS.get(lower, lower)


def is_static(target):
    path = (target_path(target) or '').lower()
    return Path(path).suffix in STATIC_EXTENSIONS or path.startswith('/favicon') or path == '/robots.txt'


def parse_event(line):
    match = LOG_PATTERN.match(line)
    if not match:
        return None
    try:
        address = str(ipaddress.ip_address(match['ip']))
        when = dt.datetime.strptime(match['time'], '%d/%b/%Y:%H:%M:%S %z').astimezone(OSLO_TIMEZONE)
        status = int(match['status'])
    except ValueError:
        return None
    target, agent = match['target'], match['agent'].strip()
    raw_path = (target_path(target) or '').lower()
    if MONITOR_PATTERN.search(agent):
        kind = 'monitoring'
    elif BOT_PATTERN.search(agent):
        kind = 'bot'
    elif SCANNER_PATH_PATTERN.search(raw_path) or (status >= 400 and any(raw_path.startswith(prefix) for prefix in TECHNICAL_PREFIXES)):
        kind = 'scanner'
    elif match['method'] == 'GET' and 200 <= status < 400 and canonical_public_path(target) and agent:
        kind = 'candidate'
    else:
        kind = 'other'
    return Event(when, when.date().isoformat(), address, agent, target, canonical_public_path(target), status, kind)


def configured_ips(admin_ip=None, excluded_ips=None):
    values = []
    if admin_ip:
        values.append(admin_ip)
    values.extend(excluded_ips or [])
    result = set()
    for value in values:
        for item in str(value).split(','):
            if item.strip():
                result.add(str(ipaddress.ip_address(item.strip())))
    return result


def sessionize(events, excluded=None):
    """Return candidate event ids confirmed as human and one session start per session."""
    groups = defaultdict(list)
    for index, event in enumerate(events):
        if event.ip not in (excluded or set()) and event.kind in ('candidate', 'other') and event.agent:
            groups[(event.ip, event.agent)].append((index, event))
    human_ids, sessions, scanner_ids = set(), [], set()
    for group in groups.values():
        group.sort(key=lambda item: item[1].when)
        chunks, current = [], []
        previous = None
        for item in group:
            if previous and item[1].when - previous > SESSION_GAP:
                chunks.append(current); current = []
            current.append(item); previous = item[1].when
        if current:
            chunks.append(current)
        for chunk in chunks:
            candidates = [(index, event) for index, event in chunk if event.kind == 'candidate']
            # Month/year enumeration at machine speed is treated as automation even with a browser UA.
            raw_targets = {event.target for _, event in candidates if (event.path or '').startswith('/bonnetider')}
            duration = chunk[-1][1].when - chunk[0][1].when
            if len(candidates) >= 10 and len(raw_targets) >= 6 and duration <= dt.timedelta(minutes=10):
                scanner_ids.update(index for index, _ in candidates)
                continue
            # Require a second page or a normal static-resource follow-up before calling it human.
            has_static = any(is_static(event.target) for _, event in chunk)
            if len(candidates) >= 2 or (candidates and has_static):
                human_ids.update(index for index, _ in candidates)
                sessions.append(candidates[0][1].day)
    return human_ids, sessions, scanner_ids


def collect(log_paths, admin_ip=None, today=None, retention_days=60, excluded_ips=None):
    excluded = configured_ips(admin_ip, excluded_ips)
    latest = today or dt.date.today()
    first = latest - dt.timedelta(days=retention_days - 1)
    events = []
    for path in log_paths:
        with open_log(path) as handle:
            for line in handle:
                event = parse_event(line)
                if event and first <= dt.date.fromisoformat(event.day) <= latest:
                    events.append(event)
    human_ids, sessions, scanner_ids = sessionize(events, excluded)
    pages, traffic = Counter(), Counter()
    for index, event in enumerate(events):
        kind = event.kind
        if event.ip in excluded:
            kind = 'excluded'
        elif index in scanner_ids:
            kind = 'scanner'
        elif index in human_ids:
            kind = 'human'
        elif kind == 'candidate':
            kind = 'other'
        traffic[(event.day, kind, 'requests')] += 1
        if kind == 'other' and event.kind == 'candidate':
            traffic[(event.day, kind, 'single_page_candidates')] += 1
        if kind == 'human':
            traffic[(event.day, kind, 'pageviews')] += 1
            pages[(event.day, event.path, event.ip)] += 1
    for day in sessions:
        traffic[(day, 'human', 'sessions')] += 1
    return pages, traffic, first, latest


def open_log(path):
    return gzip.open(path, 'rt', encoding='utf-8', errors='replace') if str(path).endswith('.gz') else open(path, encoding='utf-8', errors='replace')


def replace_database_rows(database, pages, traffic, first, latest):
    connection = sqlite3.connect(database)
    try:
        with connection:
            connection.execute('''CREATE TABLE IF NOT EXISTS daily_page_ip_stats (
                date TEXT NOT NULL, path TEXT NOT NULL, ip TEXT NOT NULL, pageviews INTEGER NOT NULL,
                PRIMARY KEY (date, path, ip))''')
            connection.execute('''CREATE TABLE IF NOT EXISTS daily_traffic_classification_stats (
                date TEXT NOT NULL, category TEXT NOT NULL, metric TEXT NOT NULL, count INTEGER NOT NULL,
                PRIMARY KEY (date, category, metric))''')
            connection.execute('CREATE INDEX IF NOT EXISTS daily_page_ip_stats_date_idx ON daily_page_ip_stats(date)')
            connection.execute('CREATE INDEX IF NOT EXISTS daily_traffic_classification_stats_date_idx ON daily_traffic_classification_stats(date)')
            bounds = (first.isoformat(), latest.isoformat())
            connection.execute('DELETE FROM daily_page_ip_stats WHERE date BETWEEN ? AND ?', bounds)
            connection.execute('DELETE FROM daily_traffic_classification_stats WHERE date BETWEEN ? AND ?', bounds)
            connection.executemany('INSERT INTO daily_page_ip_stats(date,path,ip,pageviews) VALUES (?,?,?,?)',
                                   [(day, path, ip, count) for (day, path, ip), count in pages.items()])
            connection.executemany('INSERT INTO daily_traffic_classification_stats(date,category,metric,count) VALUES (?,?,?,?)',
                                   [(day, category, metric, count) for (day, category, metric), count in traffic.items()])
            connection.execute('DELETE FROM daily_page_ip_stats WHERE date < ?', (first.isoformat(),))
            connection.execute('DELETE FROM daily_traffic_classification_stats WHERE date < ?', (first.isoformat(),))
    finally:
        connection.close()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('database', help='sti til historikk.sqlite3')
    parser.add_argument('access_logs', nargs='+', help='komplette aktive/roterte access-logger, eventuelt .gz')
    parser.add_argument('--admin-ip', default=os.getenv('ADMIN_IP'), help='eldre enkel IP-innstilling som skal utelates')
    parser.add_argument('--exclude-ip', action='append', default=os.getenv('STATISTICS_EXCLUDED_IPS', '').split(','), help='IP som utelates; kan gjentas eller settes i STATISTICS_EXCLUDED_IPS')
    parser.add_argument('--retention-days', type=int, default=60)
    args = parser.parse_args()
    if args.retention_days < 30:
        parser.error('--retention-days må være minst 30')
    pages, traffic, first, latest = collect(args.access_logs, args.admin_ip, retention_days=args.retention_days, excluded_ips=args.exclude_ip)
    replace_database_rows(args.database, pages, traffic, first, latest)
    print(f'Lagret {len(pages)} menneskelige side/besøkende-rader og {len(traffic)} tekniske aggregeringer for {first}–{latest}.')


if __name__ == '__main__':
    main()
