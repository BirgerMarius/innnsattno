import importlib.util
import json
import sqlite3
import tempfile
import unittest
from pathlib import Path


SCRIPT = Path(__file__).parents[2] / "scripts" / "generate_admin_statistics.py"
SPEC = importlib.util.spec_from_file_location("generator", SCRIPT)
GENERATOR = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(GENERATOR)


class GenerateAdminStatisticsTest(unittest.TestCase):
    def test_generates_aggregates_without_ip_addresses(self):
        with tempfile.TemporaryDirectory() as directory:
            database = Path(directory) / "history.sqlite3"
            output = Path(directory) / "admin-summary.json"
            connection = sqlite3.connect(database)
            connection.executescript("""
                CREATE TABLE daily_stats (date TEXT, pageviews INTEGER, requests INTEGER);
                CREATE TABLE daily_page_stats (date TEXT, path TEXT, pageviews INTEGER);
                CREATE TABLE daily_ip_stats (date TEXT, ip TEXT, requests INTEGER);
                CREATE TABLE daily_page_ip_stats (date TEXT, path TEXT, ip TEXT, pageviews INTEGER);
                INSERT INTO daily_stats VALUES ('2026-08-03', 10, 15), ('2026-08-04', 20, 30);
                INSERT INTO daily_page_stats VALUES ('2026-08-03', '/nyheter', 8), ('2026-08-04', '/nyheter', 12);
                INSERT INTO daily_ip_stats VALUES ('2026-08-04', '203.0.113.10', 4), ('2026-08-04', '203.0.113.11', 5), ('2026-08-04', '203.0.113.10', 2);
                INSERT INTO daily_page_ip_stats VALUES
                    ('2026-08-03', '/bonnetider-ilseng', '203.0.113.10', 8),
                    ('2026-08-04', '/bonnetider-ilseng', '203.0.113.10', 12),
                    ('2026-08-04', '/tv', '203.0.113.11', 10),
                    ('2026-08-04', '/tv', '203.0.113.12', 5);
            """)
            connection.commit()
            connection.close()

            GENERATOR.atomic_write(output, GENERATOR.generate(database, test_data=True))
            data = json.loads(output.read_text())

            self.assertEqual(20, data["latest_day"]["pageviews"])
            self.assertEqual(2, data["latest_day"]["unique_visitors"])
            self.assertEqual(30, data["last_7_days"]["pageviews"])
            self.assertEqual(45, data["last_7_days"]["requests"])
            self.assertEqual("/nyheter", data["last_7_days"]["top_page"]["path"])
            self.assertTrue(data["test_data"])
            self.assertNotIn("203.0.113", output.read_text())
            self.assertEqual(2, data["schema_version"])
            self.assertEqual("Bønnetider Ilseng", data["top_pages"]["7"]["pages"][0]["name"])
            self.assertEqual(20, data["top_pages"]["7"]["pages"][0]["pageviews"])
            self.assertEqual(1, data["top_pages"]["7"]["pages"][0]["unique_visitors"])
            self.assertEqual(2, data["top_pages"]["1"]["pages"][0]["unique_visitors"])

    def test_old_database_keeps_summary_and_marks_ranking_unavailable(self):
        with tempfile.TemporaryDirectory() as directory:
            database = Path(directory) / "history.sqlite3"
            connection = sqlite3.connect(database)
            connection.executescript("""
                CREATE TABLE daily_stats (date TEXT, pageviews INTEGER, requests INTEGER);
                CREATE TABLE daily_page_stats (date TEXT, path TEXT, pageviews INTEGER);
                CREATE TABLE daily_ip_stats (date TEXT, ip TEXT);
                INSERT INTO daily_stats VALUES ('2026-08-04', 1, 2);
                INSERT INTO daily_page_stats VALUES ('2026-08-04', '/tv', 1);
                INSERT INTO daily_ip_stats VALUES ('2026-08-04', '203.0.113.10');
            """)
            connection.commit()
            connection.close()
            data = GENERATOR.generate(database)
            self.assertIsNone(data["top_pages"])


if __name__ == "__main__":
    unittest.main()
