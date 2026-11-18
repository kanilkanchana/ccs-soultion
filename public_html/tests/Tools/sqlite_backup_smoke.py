"""Isolated SQLite engine checks, not a replacement for the PHP/Laravel tests."""
import hashlib
import hmac
import json
import sqlite3
import tempfile
import zipfile
from pathlib import Path

with tempfile.TemporaryDirectory(prefix="ccs-backup-smoke-") as work:
    root = Path(work)
    live = sqlite3.connect(root / "live.sqlite")
    live.execute("PRAGMA journal_mode=WAL")
    live.executescript("""
      PRAGMA foreign_keys=ON;
      CREATE TABLE customers(id INTEGER PRIMARY KEY,name TEXT);
      CREATE TABLE invoices(id INTEGER PRIMARY KEY,customer_id INTEGER REFERENCES customers(id),total INTEGER);
      CREATE TABLE payments(id INTEGER PRIMARY KEY,invoice_id INTEGER REFERENCES invoices(id),amount INTEGER);
      CREATE TABLE stock(id INTEGER PRIMARY KEY,quantity INTEGER);
      INSERT INTO customers VALUES(1,'Before backup');
      INSERT INTO invoices VALUES(1,1,10000);
      INSERT INTO payments VALUES(1,1,4000);
      INSERT INTO stock VALUES(1,2);
    """)
    snapshot = sqlite3.connect(root / "snapshot.sqlite")
    live.backup(snapshot)
    assert snapshot.execute("PRAGMA integrity_check").fetchall() == [("ok",)]
    assert snapshot.execute("PRAGMA foreign_key_check").fetchall() == []
    snapshot.close()
    payload = (root / "snapshot.sqlite").read_bytes()
    meta = {"sha256": hashlib.sha256(payload).hexdigest(), "format": "test-snapshot"}
    key = b"isolated-test-key-only"
    encoded = json.dumps(meta, separators=(",", ":")).encode()
    signature = hmac.new(key, encoded, hashlib.sha256).hexdigest()
    assert hmac.compare_digest(signature, hmac.new(key, encoded, hashlib.sha256).hexdigest())
    with zipfile.ZipFile(root / "backup.zip", "w", zipfile.ZIP_DEFLATED) as archive:
        archive.writestr("database.sqlite", payload)
        archive.writestr("manifest.json", json.dumps({**meta, "signature": signature}))
    live.execute("UPDATE customers SET name='After backup'")
    live.execute("INSERT INTO payments VALUES(2,1,1000)")
    live.execute("UPDATE stock SET quantity=1")
    live.commit()
    safety = sqlite3.connect(root / "safety.sqlite")
    live.backup(safety)
    assert safety.execute("SELECT SUM(amount) FROM payments").fetchone()[0] == 5000
    # Restore into the existing WAL database, without replacing the file or deleting sidecars.
    source = sqlite3.connect(root / "snapshot.sqlite")
    destination = sqlite3.connect(root / "live.sqlite")
    source.backup(destination)
    destination.close()
    source.close()
    assert live.execute("SELECT name FROM customers").fetchone()[0] == "Before backup"
    assert live.execute("SELECT SUM(amount) FROM payments").fetchone()[0] == 4000
    assert live.execute("SELECT quantity FROM stock").fetchone()[0] == 2
    assert live.execute("PRAGMA integrity_check").fetchone()[0] == "ok"
    assert live.execute("PRAGMA foreign_key_check").fetchall() == []
    tampered = payload[:-1] + bytes([payload[-1] ^ 1])
    assert not hmac.compare_digest(meta["sha256"], hashlib.sha256(tampered).hexdigest())
    with zipfile.ZipFile(root / "backup.zip") as archive:
        assert sorted(archive.namelist()) == ["database.sqlite", "manifest.json"]
        assert hashlib.sha256(archive.read("database.sqlite")).hexdigest() == meta["sha256"]
    safety.close()
    live.close()
print("Isolated SQLite WAL snapshot/restore, safety snapshot, receipt/stock recovery, integrity/FK and tamper checks passed")
