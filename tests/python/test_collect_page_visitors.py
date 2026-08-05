import importlib.util
import sqlite3
import tempfile
import unittest
from datetime import date
from pathlib import Path


SCRIPT = Path(__file__).parents[2] / "scripts" / "collect_page_visitors.py"
SPEC = importlib.util.spec_from_file_location("collector", SCRIPT)
COLLECTOR = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(COLLECTOR)


def line(ip, day, target, agent="Mozilla/5.0", status=200, method="GET"):
    return f'{ip} - - [{day}:12:00:00 +0000] "{method} {target} HTTP/1.1" {status} 123 "-" "{agent}"\n'


class CollectPageVisitorsTest(unittest.TestCase):
    def test_filters_bots_admin_technical_assets_and_normalizes_variants(self):
        with tempfile.TemporaryDirectory() as directory:
            log = Path(directory) / "access.log"
            log.write_text(
                line("203.0.113.10", "04/Aug/2026", "/tv?utm_source=x")
                + line("203.0.113.10", "04/Aug/2026", "/guide")
                + line("203.0.113.11", "04/Aug/2026", "/bonnetider-ilseng?refresh=1")
                + line("203.0.113.12", "04/Aug/2026", "/bonnetider-ilseng", "Googlebot")
                + line("203.0.113.12", "04/Aug/2026", "/css/app.css")
                + line("203.0.113.12", "04/Aug/2026", "/admin/login")
                + line("203.0.113.99", "04/Aug/2026", "/tv")
                + line("203.0.113.12", "04/Aug/2026", "/api/data")
                + line("203.0.113.12", "04/Aug/2026", "/eliteserien/test")
                + line("203.0.113.12", "04/Aug/2026", "/index.php")
                + line("203.0.113.12", "04/Aug/2026", "/index.php/tv")
            )
            counts, _, _ = COLLECTOR.collect([log], "203.0.113.99", date(2026, 8, 4))
            self.assertEqual(2, counts[("2026-08-04", "/tv", "203.0.113.10")])
            self.assertEqual(1, counts[("2026-08-04", "/bonnetider-ilseng", "203.0.113.11")])
            self.assertEqual(2, len(counts))

    def test_keeps_dated_today_pages_and_print_routes(self):
        with tempfile.TemporaryDirectory() as directory:
            log = Path(directory) / "access.log"
            log.write_text(
                line("203.0.113.10", "04/Aug/2026", "/dagen-i-dag/2026-08-04?source=test")
                + line("203.0.113.10", "04/Aug/2026", "/bonnetider/utskrift")
                + line("203.0.113.10", "04/Aug/2026", "/bonnetider-ilseng/utskrift")
            )
            counts, _, _ = COLLECTOR.collect([log], today=date(2026, 8, 4))
            self.assertIn(("2026-08-04", "/dagen-i-dag/2026-08-04", "203.0.113.10"), counts)
            self.assertIn(("2026-08-04", "/bonnetider/utskrift", "203.0.113.10"), counts)
            self.assertIn(("2026-08-04", "/bonnetider-ilseng/utskrift", "203.0.113.10"), counts)

    def test_rebuild_is_idempotent_and_removes_rows_older_than_retention(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            log = root / "access.log"
            database = root / "history.sqlite3"
            log.write_text(line("203.0.113.10", "04/Aug/2026", "/print") + line("203.0.113.10", "04/Aug/2026", "/print-ilseng"))
            counts, first, latest = COLLECTOR.collect([log], today=date(2026, 8, 4))
            connection = sqlite3.connect(database)
            connection.execute("CREATE TABLE daily_page_ip_stats (date TEXT, path TEXT, ip TEXT, pageviews INTEGER, PRIMARY KEY(date,path,ip))")
            connection.execute("INSERT INTO daily_page_ip_stats VALUES ('2026-05-01','/tv','203.0.113.20',1)")
            connection.commit(); connection.close()
            COLLECTOR.replace_database_rows(database, counts, first, latest)
            COLLECTOR.replace_database_rows(database, counts, first, latest)
            connection = sqlite3.connect(database)
            rows = connection.execute("SELECT path,pageviews FROM daily_page_ip_stats ORDER BY path").fetchall()
            connection.close()
            self.assertEqual([('/print', 1), ('/print-ilseng', 1)], rows)


if __name__ == "__main__":
    unittest.main()
