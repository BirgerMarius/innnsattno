#!/usr/bin/env python3
"""Create a valid schema 6 summary through the production generator for PHPUnit."""

import importlib.util
import sqlite3
import sys
import tempfile
from datetime import date, timedelta
from pathlib import Path


repository, output = map(Path, sys.argv[1:3])
spec = importlib.util.spec_from_file_location('generator', repository / 'scripts/generate_admin_statistics.py')
generator = importlib.util.module_from_spec(spec)
spec.loader.exec_module(generator)

with tempfile.TemporaryDirectory() as directory:
    database = Path(directory) / 'history.sqlite3'
    connection = sqlite3.connect(database)
    connection.executescript('''
        CREATE TABLE daily_stats (date TEXT, pageviews INTEGER, requests INTEGER);
        CREATE TABLE daily_page_stats (date TEXT, path TEXT, pageviews INTEGER);
        CREATE TABLE daily_ip_stats (date TEXT, ip TEXT);
        CREATE TABLE daily_page_ip_stats (date TEXT, path TEXT, ip TEXT, pageviews INTEGER);
        CREATE TABLE daily_traffic_classification_stats (date TEXT, category TEXT, metric TEXT, count INTEGER);
        CREATE TABLE daily_statistics_coverage (date TEXT PRIMARY KEY, classifier_version INTEGER, updated_at TEXT);
    ''')
    for offset in range(30):
        day = date(2026, 10, 19) + timedelta(days=offset)
        value = day.isoformat()
        connection.execute('INSERT INTO daily_stats VALUES (?,?,?)', (value, 2, 2))
        connection.execute('INSERT INTO daily_page_stats VALUES (?,?,?)', (value, '/tv', 2))
        connection.execute('INSERT INTO daily_ip_stats VALUES (?,?)', (value, '203.0.113.10'))
        connection.executemany('INSERT INTO daily_page_ip_stats VALUES (?,?,?,?)', [
            (value, '/tv', '203.0.113.10', 1),
            (value, '/kongsvinger/tv-utskrift', '203.0.113.10', 1),
        ])
        connection.executemany('INSERT INTO daily_traffic_classification_stats VALUES (?,?,?,?)', [
            (value, 'human', 'requests', 2), (value, 'human', 'sessions', 1),
        ])
        connection.execute('INSERT INTO daily_statistics_coverage VALUES (?,?,?)', (value, 9, value+'T00:05:00+00:00'))
    connection.commit()
    connection.close()
    generator.atomic_write(output, generator.generate(database))
