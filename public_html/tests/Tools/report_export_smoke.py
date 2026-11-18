import re,sqlite3,json,csv,io,zipfile,tempfile
from pathlib import Path
source=(Path(__file__).resolve().parents[2]/'app/Services/CcsReportExport.php').read_text(encoding='utf-8')
c=sqlite3.connect(':memory:');c.row_factory=sqlite3.Row
c.executescript('''
CREATE TABLE users(id INTEGER PRIMARY KEY,name TEXT,email TEXT,ccs_role TEXT,ccs_active INT,created_at TEXT,updated_at TEXT,password TEXT,remember_token TEXT);
CREATE TABLE customers(id INTEGER PRIMARY KEY,customer_code TEXT,name TEXT,phone TEXT,deleted_at TEXT);
CREATE TABLE vehicles(id INTEGER PRIMARY KEY,customer_id INT,registration_number TEXT,deleted_at TEXT);
CREATE TABLE repair_jobs(id INTEGER PRIMARY KEY,vehicle_id INT,job_number TEXT,deleted_at TEXT);
CREATE TABLE invoices(id INTEGER PRIMARY KEY,repair_job_id INT,invoice_number TEXT,status TEXT,issued_at TEXT,created_at TEXT,subtotal_lkr REAL,discount_lkr REAL,total_lkr REAL);
CREATE TABLE payments(id INTEGER PRIMARY KEY,invoice_id INT,paid_at TEXT,amount_lkr REAL,method TEXT,reference TEXT,received_by_user_id INT);
CREATE TABLE inventory_items(id INTEGER PRIMARY KEY,sku TEXT,name TEXT,category TEXT,service_application TEXT,condition TEXT,item_year TEXT,supplier TEXT,quantity_on_hand INT,reorder_level INT,purchase_price_lkr REAL,selling_price_lkr REAL,has_serial INT,is_battery_module INT,default_warranty_rule_id INT,active INT,created_at TEXT,updated_at TEXT);
CREATE TABLE audit_logs(id INTEGER PRIMARY KEY,user_id INT,created_at TEXT,action TEXT,entity_type TEXT,entity_id INT,metadata TEXT);
CREATE TABLE warranties(id INTEGER PRIMARY KEY,repair_job_id INT,terms_snapshot TEXT);
CREATE TABLE warranty_service_visits(id INTEGER PRIMARY KEY,warranty_id INT);
CREATE TABLE invoice_items(id INTEGER PRIMARY KEY,invoice_id INT,serial_number TEXT);
CREATE TABLE stock_units(id INTEGER PRIMARY KEY,serial_number TEXT);
CREATE TABLE stock_movements(id INTEGER PRIMARY KEY);
CREATE TABLE battery_service_records(id INTEGER PRIMARY KEY);
CREATE TABLE battery_module_readings(id INTEGER PRIMARY KEY,serial_number TEXT,voltage REAL);
CREATE TABLE warranty_rules(id INTEGER PRIMARY KEY);
CREATE TABLE warranty_claims(id INTEGER PRIMARY KEY);
INSERT INTO users VALUES(1,'=Formula test','staff@example.test','owner',1,NULL,NULL,'SECRET-HASH','SECRET-TOKEN');
INSERT INTO customers VALUES(1,'C1','සිංහල தமிழ் Test','0770000000',NULL);
INSERT INTO vehicles VALUES(1,1,'TEST-001',NULL);
INSERT INTO repair_jobs VALUES(1,1,'J1',NULL),(2,1,'J2',NULL),(3,1,'J3',NULL);
INSERT INTO invoices VALUES(1,1,'I1','partially_paid','2026-10-26 10:00','2026-10-26',10000,0,10000),(2,2,'I2','draft',NULL,'2026-10-26',90000,0,90000),(3,3,'I3','void',NULL,'2026-10-26',70000,0,70000);
INSERT INTO payments VALUES(1,1,'2026-10-26 10:01',4000,'cash',NULL,1),(2,1,'2026-10-27 10:01',1000,'cash',NULL,1);
INSERT INTO invoice_items VALUES(1,1,'000ABC');
''')
variables={}
for name in ['parents','paid','sales']:
 v=re.search(r'\$'+name+r'="([^"]*)"',source)[1]
 for key,value in variables.items():v=v.replace('$'+key,value)
 if name=='sales':v+=variables['parents']
 variables[name]=v
queries={}
for m in re.finditer(r"'([^']+\.csv)'=>\"([^\"]*)\"",source):
 sql=m[2]
 for key,value in variables.items():sql=sql.replace('$'+key,value)
 if m[1]=='sales_all_dates.csv':sql+=' ORDER BY i.issued_at,i.id'
 queries[m[1]]=sql
for m in re.finditer(r"'([^']+\.csv)'=>'([^']*)'",source):queries[m[1]]=m[2]
# sales query uses a variable, rather than a literal, in PHP.
queries['sales_all_dates.csv']=variables['sales']+' ORDER BY i.issued_at,i.id'
results={name:[dict(row) for row in c.execute(sql)] for name,sql in queries.items()}
assert len(queries)==9,len(queries)
assert len(results['sales_all_dates.csv'])==1
assert results['outstanding_dues.csv'][0]['outstanding_lkr']==5000
assert results['daily_sales_summary.csv'][0]['sales_lkr']==10000
assert results['daily_sales_summary.csv'][0]['received_to_date_lkr']==5000
assert [r['received_lkr'] for r in results['daily_payment_summary.csv']]==[4000,1000]
assert not {'password','remember_token'} & set(results['staff_directory.csv'][0])
# Dated folder, ZIP STORE and multilingual CSV round-trip.
with tempfile.TemporaryDirectory() as temp:
 path=Path(temp)/'reports.zip';buffer=io.StringIO(newline='');writer=csv.writer(buffer);writer.writerow(['customer','serial']);writer.writerow(['සිංහල தமிழ் Test','000ABC']);data=b'\xef\xbb\xbf'+buffer.getvalue().encode()
 with zipfile.ZipFile(path,'w',zipfile.ZIP_STORED) as z:z.writestr('Reports_2026-10-26_17-30-00/customers.csv',data)
 with zipfile.ZipFile(path) as z:
  assert z.testzip() is None;entry=z.infolist()[0];assert entry.compress_type==zipfile.ZIP_STORED
  rows=list(csv.reader(io.StringIO(z.read(entry.filename).decode('utf-8-sig'))));assert rows[1]==['සිංහල தமிழ் Test','000ABC']
print('Report SQL passed: no draft/void duplication, dated receipts, due 5000, safe staff columns; CSV/ZIP STORE Unicode and serial round-trip passed')
