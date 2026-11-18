<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use PDO;
use SQLite3;
use ZipArchive;
class CcsSqliteBackup {
 public function root(): string {$root=config('ccs_backups.root');if(!is_dir($root)&&!mkdir($root,0700,true)&&!is_dir($root))throw new RuntimeException('Cannot create the private backup directory.');return $root;}
 private function id(string $id): void {if(!Str::isUuid($id))throw new RuntimeException('Invalid backup request ID.');}
 private function sign(array $data): string {if(!config('app.key'))throw new RuntimeException('APP_KEY must be configured before using backups.');return hash_hmac('sha256',json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),(string)config('app.key'));}
 private function write(string $path,array $data): void {$data['signature']=$this->sign($data);if(file_put_contents($path,json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX)===false)throw new RuntimeException('Could not save backup metadata.');@chmod($path,0600);}
 private function signed(string $json): array {$data=json_decode($json,true,512,JSON_THROW_ON_ERROR);if(!is_array($data)||!isset($data['signature']))throw new RuntimeException('Invalid CCS backup metadata.');$signature=$data['signature'];unset($data['signature']);if(!is_string($signature)||!hash_equals($this->sign($data),$signature))throw new RuntimeException('This backup does not match this installation APP_KEY, or its metadata has changed.');return $data;}
 public function database(): string {if(DB::connection()->getDriverName()!=='sqlite')throw new RuntimeException('This backup tool currently supports file-based SQLite only.');$path=DB::connection()->getConfig('database');if(!$path||$path===':memory:'||!is_file($path))throw new RuntimeException('A real SQLite database file is required.');return realpath($path);}
 private function open(string $path): PDO {$pdo=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$pdo->exec('PRAGMA trusted_schema=OFF');$pdo->exec('PRAGMA query_only=ON');return $pdo;}
 private function schema(PDO $pdo): string {$rows=$pdo->query("SELECT type,name,tbl_name,sql FROM sqlite_master WHERE name NOT LIKE 'sqlite_%' ORDER BY type,name")->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($rows,JSON_THROW_ON_ERROR));}
 private function check(PDO $pdo): void {if($pdo->query('PRAGMA integrity_check')->fetchAll(PDO::FETCH_COLUMN)!==['ok'])throw new RuntimeException('SQLite integrity validation failed.');if($pdo->query('PRAGMA foreign_key_check')->fetch(PDO::FETCH_ASSOC))throw new RuntimeException('Database relationships failed validation.');}
 private function counts(PDO $pdo): array {$counts=[];foreach(['customers','vehicles','repair_jobs','invoices','payments','inventory_items','stock_units','warranties','warranty_claims'] as $table)$counts[$table]=(int)$pdo->query('SELECT COUNT(*) FROM "'.$table.'"')->fetchColumn();return $counts;}
 private function journal(string $action,array $details=[]): void {if(file_put_contents($this->root().'/operations.ndjson',json_encode(['at'=>gmdate('c'),'action'=>$action]+$details,JSON_THROW_ON_ERROR)."\n",FILE_APPEND|LOCK_EX)===false)throw new RuntimeException('Cannot write the backup operation journal.');}
 public function create(?int $actorId,string $reason='manual'): array {
  if(!class_exists(SQLite3::class)||!class_exists(ZipArchive::class))throw new RuntimeException('PHP sqlite3 and zip extensions are required.');
  $live=$this->database();$id=(string)Str::uuid();$dir=$this->root().'/'.$id;if(!mkdir($dir,0700))throw new RuntimeException('Cannot create a private backup folder.');$snapshot=$dir.'/database.sqlite';$archive=$dir.'/backup.zip';
  try{
   $source=new SQLite3($live,SQLITE3_OPEN_READONLY);$source->busyTimeout(5000);$target=new SQLite3($snapshot,SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE);$target->busyTimeout(5000);
   try{if(!$source->backup($target))throw new RuntimeException('SQLite snapshot failed. Try again when no long-running writes are active.');}finally{$target->close();$source->close();}
   @chmod($snapshot,0600);if(filesize($snapshot)>(int)config('ccs_backups.max_bytes'))throw new RuntimeException('Database exceeds this tool’s backup/restore size limit.');$pdo=$this->open($snapshot);$this->check($pdo);$manifest=['format'=>'ccs-sqlite-backup-v1','id'=>$id,'created_at'=>gmdate('c'),'reason'=>$reason,'bytes'=>filesize($snapshot),'sha256'=>hash_file('sha256',$snapshot),'schema'=>$this->schema($pdo),'migrations'=>$pdo->query('SELECT migration FROM migrations ORDER BY migration')->fetchAll(PDO::FETCH_COLUMN),'counts'=>$this->counts($pdo)];$pdo=null;
   $this->write($dir.'/manifest.json',$manifest);$zip=new ZipArchive();if($zip->open($archive,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Could not create backup ZIP.');if(!$zip->addFile($snapshot,'database.sqlite')||!$zip->addFile($dir.'/manifest.json','manifest.json'))throw new RuntimeException('Could not add backup files to ZIP.');$zip->setCompressionName('database.sqlite',ZipArchive::CM_STORE);$zip->setCompressionName('manifest.json',ZipArchive::CM_STORE);if(!$zip->close())throw new RuntimeException('Could not finish backup ZIP.');@chmod($archive,0600);clearstatcache(true,$archive);$this->archive($id);unlink($snapshot);
   $this->journal('backup_created',['id'=>$id,'reason'=>$reason]);if($actorId)app(CcsAdministration::class)->audit($actorId,'database_backup_created','backup',null,['backup_id'=>$id,'reason'=>$reason]);return $manifest;
  }catch(\Throwable $e){$pdo=null;@unlink($snapshot);@unlink($archive);@unlink($dir.'/manifest.json');@rmdir($dir);throw $e;}
 }
 public function archive(string $id): string {
  $this->id($id);$path=$this->root().'/'.$id.'/backup.zip';clearstatcache(true,$path);if(!is_file($path)||filesize($path)<100)throw new RuntimeException('Backup ZIP not found or empty.');$manifest=$this->signed(file_get_contents(dirname($path).'/manifest.json'));
  $zip=new ZipArchive();if($zip->open($path,ZipArchive::CHECKCONS)!==true)throw new RuntimeException('Stored backup ZIP is invalid. Create a new backup.');
  try{if($zip->numFiles!==2)throw new RuntimeException('Backup archive structure is invalid.');$inside=$this->signed($zip->getFromName('manifest.json'));if($inside!==$manifest)throw new RuntimeException('Backup metadata does not match.');$stream=$zip->getStream('database.sqlite');if(!$stream)throw new RuntimeException('Database missing inside backup ZIP.');$hash=hash_init('sha256');try{$bytes=hash_update_stream($hash,$stream);}finally{fclose($stream);}if($bytes!==(int)$manifest['bytes']||!hash_equals($manifest['sha256'],hash_final($hash)))throw new RuntimeException('Database ZIP checksum failed.');}finally{$zip->close();}
  return $path;
 }

 public function listing(?string $date=null): array {$rows=[];foreach(glob($this->root().'/*/manifest.json')?:[] as $file){try{$m=$this->signed(file_get_contents($file));if(is_file(dirname($file).'/backup.zip') && ($date===null || \Illuminate\Support\Carbon::parse($m['created_at'])->timezone('Asia/Colombo')->toDateString()===$date))$rows[]=$m;}catch(\Throwable $e){continue;}}usort($rows,fn($a,$b)=>strcmp($b['created_at'],$a['created_at']));return array_slice($rows,0,30);}
 public function stage(string $upload,string $ownerEmail): array {
  if(!class_exists(ZipArchive::class))throw new RuntimeException('PHP zip extension is required.');$this->database();$limit=(int)config('ccs_backups.max_bytes');if(filesize($upload)>$limit+1024*1024)throw new RuntimeException('Backup exceeds the supported upload limit.');
  $id=(string)Str::uuid();$dir=$this->root().'/restore-'.$id;if(!mkdir($dir,0700))throw new RuntimeException('Cannot stage restore.');$candidate=$dir.'/candidate.sqlite';$zip=new ZipArchive();
  try{
   if($zip->open($upload)!==true)throw new RuntimeException('Upload a CCS backup ZIP.');if($zip->locateName('database.sqlite')===false)throw new RuntimeException('Database missing. Select a CCS_Backup ZIP; reports ZIPs cannot be restored.');if($zip->numFiles!==2)throw new RuntimeException('Unexpected files in backup ZIP.');$names=[];for($i=0;$i<2;$i++)$names[]=$zip->getNameIndex($i);sort($names);if($names!==['database.sqlite','manifest.json'])throw new RuntimeException('Unexpected backup archive entries.');
   $meta=$zip->statName('manifest.json');$db=$zip->statName('database.sqlite');if(!$meta||$meta['size']>65536||!$db||$db['size']<100||$db['size']>$limit)throw new RuntimeException('Invalid backup size.');$manifest=$this->signed($zip->getFromName('manifest.json'));
   if(($manifest['format']??'')!=='ccs-sqlite-backup-v1'||(int)$manifest['bytes']!==(int)$db['size'])throw new RuntimeException('Invalid CCS backup format.');
   $input=$zip->getStream('database.sqlite');$output=fopen($candidate,'xb');if(!$input||!$output)throw new RuntimeException('Cannot read the backup database.');try{$bytes=stream_copy_to_stream($input,$output,$limit+1);}finally{fclose($input);fclose($output);}$zip->close();if($bytes!==(int)$manifest['bytes']||!hash_equals($manifest['sha256'],hash_file('sha256',$candidate)))throw new RuntimeException('Backup database checksum mismatch.');@chmod($candidate,0600);
   $pdo=$this->open($candidate);$this->check($pdo);$live=DB::connection()->getPdo();if($this->schema($pdo)!==$this->schema($live)||$manifest['schema']!==$this->schema($pdo))throw new RuntimeException('Backup schema differs from this app version. Use a matching application version.');if($manifest['migrations']!==$live->query('SELECT migration FROM migrations ORDER BY migration')->fetchAll(PDO::FETCH_COLUMN))throw new RuntimeException('Backup migration version differs.');
   $owner=$pdo->prepare('SELECT id,email,ccs_role,ccs_active FROM users WHERE LOWER(email)=?');$owner->execute([mb_strtolower($ownerEmail)]);$owner=$owner->fetchObject();if(!$owner||!$owner->ccs_active||!app(CcsAdministration::class)->owner($owner))throw new RuntimeException('The backup must contain this active owner login.');$counts=$this->counts($pdo);$pdo=null;
   $job=['id'=>$id,'state'=>'ready','created_at'=>time(),'expires_at'=>time()+60*(int)config('ccs_backups.request_minutes'),'owner_email'=>mb_strtolower($ownerEmail),'sha256'=>$manifest['sha256'],'schema'=>$manifest['schema'],'backup_id'=>$manifest['id'],'backup_date'=>$manifest['created_at'],'counts'=>$counts];$this->write($dir.'/job.json',$job);$this->journal('restore_staged',['request_id'=>$id,'backup_id'=>$manifest['id']]);return $job;
  }catch(\Throwable $e){$pdo=null;try{$zip->close();}catch(\Throwable $ignored){}@unlink($candidate);@unlink($dir.'/job.json');@rmdir($dir);throw $e;}
 }
 public function job(string $id): array {$this->id($id);$path=$this->root().'/restore-'.$id.'/job.json';if(!is_file($path))throw new RuntimeException('Restore request not found.');return $this->signed(file_get_contents($path));}
 public function epoch(): string {$file=$this->root().'/session-epoch';return is_file($file)?trim(file_get_contents($file)):'initial';}
 public function restore(string $id,$heldLock=null): array {
  $this->id($id);
  if(!app()->isDownForMaintenance())throw new RuntimeException('Run php artisan down before restoring.');if(!class_exists(SQLite3::class))throw new RuntimeException('PHP sqlite3 extension is required.');$live=$this->database();$ownsLock=$heldLock===null;$lock=$heldLock??fopen($this->root().'/database.lock','c');if(!$ownsLock&&(!is_resource($lock)||realpath(stream_get_meta_data($lock)['uri']??'')!==realpath($this->root().'/database.lock')))throw new RuntimeException('Restore lock is invalid.');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB)){if($ownsLock&&$lock)fclose($lock);throw new RuntimeException('Another system request is active. Wait a few seconds and retry Restore Now.');}
  $dir=$this->root().'/restore-'.$id;$safety=null;$written=false;
  try{
   $job=$this->job($id);if($job['state']!=='ready'||$job['expires_at']<time())throw new RuntimeException('Restore request expired or already used. Prepare it again.');$candidate=$dir.'/candidate.sqlite';if(!is_file($candidate)||!hash_equals($job['sha256'],hash_file('sha256',$candidate)))throw new RuntimeException('Staged database changed.');$pdo=$this->open($candidate);$this->check($pdo);if($this->schema($pdo)!==$this->schema(DB::connection()->getPdo()))throw new RuntimeException('Live schema changed after review. Prepare the restore again.');$pdo=null;
   $safety=$this->create(null,'before_restore');$job['state']='applying';$this->write($dir.'/job.json',$job);$this->journal('restore_started',['request_id'=>$id,'safety_backup_id'=>$safety['id']]);DB::purge();
   $source=new SQLite3($candidate,SQLITE3_OPEN_READONLY);$destination=new SQLite3($live,SQLITE3_OPEN_READWRITE);$destination->busyTimeout(5000);try{if(!$source->backup($destination))throw new RuntimeException('SQLite restore failed. The system remains in maintenance mode.');$written=true;}finally{$destination->close();$source->close();}
   DB::purge();$this->check(DB::connection()->getPdo());if($this->counts(DB::connection()->getPdo())!==$job['counts'])throw new RuntimeException('Restored record counts do not match the reviewed backup.');$owner=DB::table('users')->whereRaw('LOWER(email)=?',[$job['owner_email']])->first();if(!$owner||!$owner->ccs_active||!app(CcsAdministration::class)->owner($owner))throw new RuntimeException('Restored owner verification failed.');
   DB::table('users')->update(['remember_token'=>null]);if(file_put_contents($this->root().'/session-epoch',(string)Str::uuid(),LOCK_EX)===false)throw new RuntimeException('Could not revoke old staff sessions.');
   app(CcsAdministration::class)->audit($owner->id,'database_restored','backup',null,['request_id'=>$id,'source_backup_id'=>$job['backup_id'],'safety_backup_id'=>$safety['id']]);$job['state']='completed';$job['completed_at']=time();$job['safety_backup_id']=$safety['id'];$this->write($dir.'/job.json',$job);$this->journal('restore_completed',['request_id'=>$id,'safety_backup_id'=>$safety['id']]);@unlink($candidate);return $job;
  }catch(\Throwable $e){
   if($written&&$safety){
    // A post-restore check failed: recover automatically from the pre-restore snapshot.
    $recovery=$dir.'/recovery.sqlite';
    try{
     $zip=new ZipArchive();if($zip->open($this->archive($safety['id']))!==true)throw new RuntimeException('Safety archive unavailable.');
     $in=$zip->getStream('database.sqlite');$out=fopen($recovery,'wb');if(!$in||!$out)throw new RuntimeException('Safety snapshot unavailable.');
     try{if(stream_copy_to_stream($in,$out)!==(int)$safety['bytes'])throw new RuntimeException('Incomplete safety snapshot.');}finally{fclose($in);fclose($out);$zip->close();}
     if(!hash_equals($safety['sha256'],hash_file('sha256',$recovery)))throw new RuntimeException('Safety checksum mismatch.');
     DB::purge();$s=new SQLite3($recovery,SQLITE3_OPEN_READONLY);$d=new SQLite3($live,SQLITE3_OPEN_READWRITE);$d->busyTimeout(5000);try{$ok=$s->backup($d);}finally{$d->close();$s->close();}
     DB::purge();if(!$ok)throw new RuntimeException('Safety restore failed.');$this->check(DB::connection()->getPdo());@unlink($recovery);$this->journal('restore_recovered',['request_id'=>$id,'safety_backup_id'=>$safety['id']]);
    }catch(\Throwable $recoveryError){throw new RuntimeException('Automatic recovery failed. Keep maintenance mode on. Safety backup: '.$safety['id'],0,$recoveryError);}

   }
   if(isset($job)&&in_array($job['state']??'',['applying','completed'],true)){$job['state']='failed';$this->write($dir.'/job.json',$job);}$this->journal('restore_failed',['request_id'=>$id,'safety_backup_id'=>$safety['id']??null]);throw $e;
  }finally{if($ownsLock){flock($lock,LOCK_UN);fclose($lock);}}
 }
}
