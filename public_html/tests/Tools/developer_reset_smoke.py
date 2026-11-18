"""SQLite-engine check using the reset service's actual fixed deletion order.
Not a substitute for executing the PHP service, middleware or Laravel tests.
"""
from pathlib import Path
import re,sqlite3,tempfile
source=(Path(__file__).resolve().parents[2]/'app/Services/CcsDeveloperReset.php').read_text()
order=re.findall(r"'([a-z_]+)'",re.search(r'private const TABLES=\[(.*?)\];',source,re.S)[1])
assert order and 'users' not in order and 'audit_logs' not in order
schema='''
CREATE TABLE users(id INTEGER PRIMARY KEY);
CREATE TABLE ccs_developer_accounts(id INTEGER PRIMARY KEY, authorized_by INTEGER REFERENCES users(id));
CREATE TABLE ccs_developer_events(id INTEGER PRIMARY KEY, developer_account_id INTEGER REFERENCES ccs_developer_accounts(id));
CREATE TABLE customers(id INTEGER PRIMARY KEY);
CREATE TABLE vehicles(id INTEGER PRIMARY KEY,customer_id INTEGER REFERENCES customers(id) ON DELETE RESTRICT);
CREATE TABLE repair_jobs(id INTEGER PRIMARY KEY,vehicle_id INTEGER REFERENCES vehicles(id) ON DELETE RESTRICT);
CREATE TABLE inventory_items(id INTEGER PRIMARY KEY,quantity_on_hand INTEGER);
CREATE TABLE invoices(id INTEGER PRIMARY KEY,repair_job_id INTEGER REFERENCES repair_jobs(id) ON DELETE RESTRICT);
CREATE TABLE stock_units(id INTEGER PRIMARY KEY,inventory_item_id INTEGER REFERENCES inventory_items(id) ON DELETE RESTRICT,invoice_id INTEGER REFERENCES invoices(id) ON DELETE RESTRICT);
CREATE TABLE invoice_items(id INTEGER PRIMARY KEY,invoice_id INTEGER REFERENCES invoices(id) ON DELETE RESTRICT,stock_unit_id INTEGER REFERENCES stock_units(id) ON DELETE RESTRICT);
CREATE TABLE payments(id INTEGER PRIMARY KEY,invoice_id INTEGER REFERENCES invoices(id) ON DELETE RESTRICT,amount INTEGER);
CREATE TABLE warranties(id INTEGER PRIMARY KEY,repair_job_id INTEGER REFERENCES repair_jobs(id) ON DELETE RESTRICT,stock_sale_item_id INTEGER REFERENCES invoice_items(id) ON DELETE RESTRICT);
CREATE TABLE warranty_claims(id INTEGER PRIMARY KEY,warranty_id INTEGER REFERENCES warranties(id) ON DELETE RESTRICT,repair_job_id INTEGER REFERENCES repair_jobs(id) ON DELETE RESTRICT);
CREATE TABLE warranty_service_visits(id INTEGER PRIMARY KEY,warranty_id INTEGER REFERENCES warranties(id) ON DELETE RESTRICT);
CREATE TABLE battery_service_records(id INTEGER PRIMARY KEY,vehicle_id INTEGER REFERENCES vehicles(id) ON DELETE RESTRICT,repair_job_id INTEGER REFERENCES repair_jobs(id) ON DELETE RESTRICT);
CREATE TABLE battery_module_readings(id INTEGER PRIMARY KEY,battery_service_record_id INTEGER REFERENCES battery_service_records(id) ON DELETE RESTRICT);
CREATE TABLE stock_movements(id INTEGER PRIMARY KEY,inventory_item_id INTEGER REFERENCES inventory_items(id) ON DELETE RESTRICT,repair_job_id INTEGER REFERENCES repair_jobs(id) ON DELETE RESTRICT);
CREATE TABLE customer_portal_accounts(id INTEGER PRIMARY KEY,customer_id INTEGER REFERENCES customers(id) ON DELETE RESTRICT);
CREATE TABLE purchases(id INTEGER PRIMARY KEY);
CREATE TABLE ccs_notification_reads(id INTEGER PRIMARY KEY);
CREATE TABLE audit_logs(id INTEGER PRIMARY KEY);
INSERT INTO users VALUES(1);INSERT INTO ccs_developer_accounts VALUES(1,1);INSERT INTO ccs_developer_events VALUES(1,1);
INSERT INTO customers VALUES(1);INSERT INTO vehicles VALUES(1,1);INSERT INTO repair_jobs VALUES(1,1);INSERT INTO inventory_items VALUES(1,2);INSERT INTO invoices VALUES(1,1);INSERT INTO stock_units VALUES(1,1,1);INSERT INTO invoice_items VALUES(1,1,1);INSERT INTO payments VALUES(1,1,4000);INSERT INTO warranties VALUES(1,1,1);INSERT INTO warranty_claims VALUES(1,1,1);INSERT INTO warranty_service_visits VALUES(1,1);INSERT INTO battery_service_records VALUES(1,1,1);INSERT INTO battery_module_readings VALUES(1,1);INSERT INTO stock_movements VALUES(1,1,1);INSERT INTO customer_portal_accounts VALUES(1,1);INSERT INTO purchases VALUES(1);INSERT INTO ccs_notification_reads VALUES(1);INSERT INTO audit_logs VALUES(1);
'''
with tempfile.TemporaryDirectory() as root:
 for full in (False,True):
  conn=sqlite3.connect(str(Path(root)/f'live-{full}.sqlite'));conn.execute('PRAGMA foreign_keys=ON');conn.executescript(schema)
  backup=sqlite3.connect(str(Path(root)/f'safety-{full}.sqlite'));conn.backup(backup)
  # An actual failed delete must roll back earlier dependent deletes too.
  conn.execute("CREATE TRIGGER fail_cleanup BEFORE DELETE ON customers BEGIN SELECT RAISE(ABORT,'fixture failure'); END")
  try:
   with conn:
    for table in order:conn.execute(f'DELETE FROM "{table}"')
  except sqlite3.IntegrityError:pass
  else:raise AssertionError('Expected forced delete failure')
  assert conn.execute('SELECT COUNT(*) FROM payments').fetchone()[0]==1
  assert conn.execute('SELECT COUNT(*) FROM warranties').fetchone()[0]==1
  conn.execute('DROP TRIGGER fail_cleanup')
  with conn:
   for table in order:conn.execute(f'DELETE FROM "{table}"')
   conn.execute('DELETE FROM inventory_items' if full else 'UPDATE inventory_items SET quantity_on_hand=0')
   assert not conn.execute('PRAGMA foreign_key_check').fetchall()
  assert all(conn.execute(f'SELECT COUNT(*) FROM "{table}"').fetchone()[0]==0 for table in order)
  for table in ('users','ccs_developer_accounts','ccs_developer_events','audit_logs'):assert conn.execute(f'SELECT COUNT(*) FROM {table}').fetchone()[0]==1
  assert conn.execute('SELECT COUNT(*) FROM inventory_items').fetchone()[0]==(0 if full else 1)
  if not full:assert conn.execute('SELECT quantity_on_hand FROM inventory_items').fetchone()[0]==0
  assert backup.execute('SELECT amount FROM payments').fetchone()[0]==4000
  assert backup.execute('SELECT COUNT(*) FROM customers').fetchone()[0]==1
  conn.close();backup.close()
print('PASS: actual deletion order handles cyclic stock/invoice dependencies, full/keep-catalogue scopes, rollback, retained accounts/audit and a recoverable pre-reset SQLite backup.')
