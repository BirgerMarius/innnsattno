#!/usr/bin/env python3
"""Rebuild privacy-limited per-page visitor aggregates from web access logs."""

import argparse
import datetime as dt
import gzip
import ipaddress
import os
import re
import sqlite3
from collections import Counter
from pathlib import Path
from urllib.parse import unquote, urlsplit


LOG_PATTERN = re.compile(
    r'^(?P<ip>\S+) \S+ \S+ \[(?P<time>[^]]+)] "(?P<method>\S+) (?P<target>\S+) [^"]+" '
    r'(?P<status>\d{3}) \S+ "[^"]*" "(?P<agent>[^"]*)"'
)
BOT_PATTERN = re.compile(
    r'bot|crawler|spider|scanner|slurp|headless|lighthouse|monitor|uptime|preview|'
    r'curl|wget|python|go-http-client|libwww|httpclient|nikto|sqlmap|masscan|zgrab',
    re.IGNORECASE,
)
TECHNICAL_PREFIXES = (
    '/admin', '/adm', '/api', '/statistikk', '/storage', '/vendor', '/_debugbar',
    '/login', '/logout', '/telescope', '/horizon', '/.env', '/wp-', '/xmlrpc',
)
STATIC_EXTENSIONS = {
    '.css', '.js', '.map', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.webp', '.ico',
    '.woff', '.woff2', '.ttf', '.eot', '.pdf', '.xml', '.txt', '.json', '.zip', '.mp4',
}
CANONICAL_PATHS = {
    '/': '/tv', '/guide': '/tv', '/tvguide': '/tv', '/tv-guide': '/tv', '/tv-guiden': '/tv',
    '/ilseng': '/print-ilseng', '/football': '/fotball',
}


def canonical_public_path(target):
    try:
        path = unquote(urlsplit(target).path)
    except ValueError:
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


def parse_line(line, admin_ip=None):
    match = LOG_PATTERN.match(line)
    if not match or match['method'] != 'GET' or not 200 <= int(match['status']) < 400:
        return None
    try:
        address = str(ipaddress.ip_address(match['ip']))
    except ValueError:
        return None
    if admin_ip and address == admin_ip:
        return None
    if not match['agent'].strip() or BOT_PATTERN.search(match['agent']):
        return None
    path = canonical_public_path(match['target'])
    if path is None:
        return None
    try:
        day = dt.datetime.strptime(match['time'].split()[0], '%d/%b/%Y:%H:%M:%S').date()
    except ValueError:
        return None
    return day.isoformat(), path, address


def open_log(path):
    return gzip.open(path, 'rt', encoding='utf-8', errors='replace') if str(path).endswith('.gz') else open(path, encoding='utf-8', errors='replace')


def collect(log_paths, admin_ip=None, today=None, retention_days=60):
    if admin_ip:
        admin_ip = str(ipaddress.ip_address(admin_ip))
    latest = today or dt.date.today()
    first = latest - dt.timedelta(days=retention_days - 1)
    counts = Counter()
    for path in log_paths:
        with open_log(path) as handle:
            for line in handle:
                parsed = parse_line(line, admin_ip)
                if parsed and first.isoformat() <= parsed[0] <= latest.isoformat():
                    counts[parsed] += 1
    return counts, first, latest


def replace_database_rows(database, counts, first, latest):
    connection = sqlite3.connect(database)
    try:
        with connection:
            connection.execute('''CREATE TABLE IF NOT EXISTS daily_page_ip_stats (
                date TEXT NOT NULL, path TEXT NOT NULL, ip TEXT NOT NULL, pageviews INTEGER NOT NULL,
                PRIMARY KEY (date, path, ip)
            )''')
            connection.execute('CREATE INDEX IF NOT EXISTS daily_page_ip_stats_date_idx ON daily_page_ip_stats(date)')
            connection.execute('DELETE FROM daily_page_ip_stats WHERE date BETWEEN ? AND ?', (first.isoformat(), latest.isoformat()))
            connection.executemany(
                'INSERT INTO daily_page_ip_stats(date, path, ip, pageviews) VALUES (?, ?, ?, ?)',
                [(day, path, ip, views) for (day, path, ip), views in counts.items()],
            )
            connection.execute('DELETE FROM daily_page_ip_stats WHERE date < ?', (first.isoformat(),))
    finally:
        connection.close()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('database', help='sti til historikk.sqlite3')
    parser.add_argument('access_logs', nargs='+', help='komplette aktive/roterte access-logger, eventuelt .gz')
    parser.add_argument('--admin-ip', default=os.getenv('ADMIN_IP'), help='IP som skal utelates (standard: miljøvariabelen ADMIN_IP)')
    parser.add_argument('--retention-days', type=int, default=60)
    args = parser.parse_args()
    if args.retention_days < 30:
        parser.error('--retention-days må være minst 30')
    counts, first, latest = collect(args.access_logs, args.admin_ip, retention_days=args.retention_days)
    replace_database_rows(args.database, counts, first, latest)
    print(f'Lagret {len(counts)} aggregerte side/besøkende-rader for {first}–{latest}.')


if __name__ == '__main__':
    main()
