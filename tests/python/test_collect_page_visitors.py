import importlib.util
import sqlite3
import tempfile
import unittest
from datetime import date
from pathlib import Path


SCRIPT = Path(__file__).parents[2] / 'scripts' / 'collect_page_visitors.py'
SPEC = importlib.util.spec_from_file_location('collector', SCRIPT)
COLLECTOR = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(COLLECTOR)


def line(ip, day, target, agent='Mozilla/5.0', status=200, method='GET', time='12:00:00'):
    return f'{ip} - - [{day}:{time} +0000] "{method} {target} HTTP/1.1" {status} 123 "-" "{agent}"\n'


class CollectPageVisitorsTest(unittest.TestCase):
    def test_classifies_normal_browser_session_and_filters_known_automation(self):
        with tempfile.TemporaryDirectory() as directory:
            log = Path(directory) / 'access.log'
            log.write_text(
                line('139.117.13.196', '04/Aug/2026', '/tv', time='12:00:00')
                + line('139.117.13.196', '04/Aug/2026', '/css/app.css', time='12:00:01')
                + line('139.117.13.196', '04/Aug/2026', '/quiz', time='12:02:00')
                + line('139.117.13.196', '04/Aug/2026', '/print', time='12:03:00')
                + line('203.0.113.10', '04/Aug/2026', '/tv', 'Uptime Kuma')
                + line('203.0.113.11', '04/Aug/2026', '/tv', 'ClaudeBot')
                + line('203.0.113.12', '04/Aug/2026', '/wp-content/plugins/x.php', status=404)
                + line('203.0.113.13', '04/Aug/2026', '/tv')
            )
            pages, traffic, _, _ = COLLECTOR.collect([log], today=date(2026, 8, 4))
            self.assertEqual(1, pages[('2026-08-04', '/tv', '139.117.13.196')])
            self.assertEqual(1, pages[('2026-08-04', '/quiz', '139.117.13.196')])
            self.assertEqual(1, pages[('2026-08-04', '/print', '139.117.13.196')])
            self.assertEqual(3, traffic[('2026-08-04', 'human', 'pageviews')])
            self.assertEqual(1, traffic[('2026-08-04', 'human', 'sessions')])
            self.assertEqual(1, traffic[('2026-08-04', 'monitoring', 'requests')])
            self.assertEqual(1, traffic[('2026-08-04', 'bot', 'requests')])
            self.assertEqual(1, traffic[('2026-08-04', 'scanner', 'requests')])
            self.assertEqual(2, traffic[('2026-08-04', 'other', 'requests')])
            self.assertEqual(1, traffic[('2026-08-04', 'other', 'single_page_candidates')])

    def test_detects_fast_bonnetider_enumeration_and_configurable_exclusions(self):
        with tempfile.TemporaryDirectory() as directory:
            log = Path(directory) / 'access.log'
            automated = ''.join(line('85.25.43.170', '04/Aug/2026', f'/bonnetider?month={month}&year=202{month % 7}', time=f'12:00:{month:02d}') for month in range(10))
            normal = line('203.0.113.10', '04/Aug/2026', '/tv', time='13:00:00') + line('203.0.113.10', '04/Aug/2026', '/quiz', time='13:01:00')
            log.write_text(automated + normal)
            pages, traffic, _, _ = COLLECTOR.collect([log], today=date(2026, 8, 4), excluded_ips=['203.0.113.10'])
            self.assertFalse(pages)
            self.assertEqual(10, traffic[('2026-08-04', 'scanner', 'requests')])
            self.assertEqual(2, traffic[('2026-08-04', 'excluded', 'requests')])

    def test_rebuild_is_idempotent_and_removes_rows_older_than_retention(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory); log = root / 'access.log'; database = root / 'history.sqlite3'
            log.write_text(line('203.0.113.10', '04/Aug/2026', '/print') + line('203.0.113.10', '04/Aug/2026', '/print-ilseng'))
            pages, traffic, first, latest = COLLECTOR.collect([log], today=date(2026, 8, 4))
            connection = sqlite3.connect(database)
            connection.execute('CREATE TABLE daily_page_ip_stats (date TEXT, path TEXT, ip TEXT, pageviews INTEGER, PRIMARY KEY(date,path,ip))')
            connection.execute("INSERT INTO daily_page_ip_stats VALUES ('2026-05-01','/tv','203.0.113.20',1)")
            connection.commit(); connection.close()
            COLLECTOR.replace_database_rows(database, pages, traffic, first, latest)
            COLLECTOR.replace_database_rows(database, pages, traffic, first, latest)
            connection = sqlite3.connect(database)
            rows = connection.execute('SELECT path,pageviews FROM daily_page_ip_stats ORDER BY path').fetchall()
            categories = connection.execute('SELECT category,metric,count FROM daily_traffic_classification_stats ORDER BY category,metric').fetchall()
            connection.close()
            self.assertEqual([('/print', 1), ('/print-ilseng', 1)], rows)
            self.assertIn(('human', 'sessions', 1), categories)


if __name__ == '__main__':
    unittest.main()
