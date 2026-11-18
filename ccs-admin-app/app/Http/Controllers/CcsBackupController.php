<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\CcsSqliteBackup;
class CcsBackupController extends Controller {
 public function index(Request $request,CcsSqliteBackup $backups){$supported=DB::connection()->getDriverName()==='sqlite'&&is_file((string)DB::connection()->getConfig('database'))&&class_exists(\SQLite3::class)&&class_exists(\ZipArchive::class);$files=$supported?$backups->listing():[];$reports=$supported?app(\App\Services\CcsReportExport::class)->listing():[];return view('ccs.backups',compact('supported','files','reports'));}
 private function sendZip(string $path,string $filename){
  clearstatcache(true,$path);$size=filesize($path);if(!$size||!is_readable($path))throw new \RuntimeException('ZIP file is empty or unreadable.');
  $testing=app()->runningUnitTests();if(!$testing&&headers_sent($source,$line))throw new \RuntimeException('PHP output was already sent from '.basename($source).':'.$line.'. Check the matching log entry.');
  // Pending whitespace/BOM/notices must not become a prefix of the ZIP payload.
  // Do not destroy PHPUnit's own capture buffers when testing streamed content.
  if(!$testing){
   $removed=0;
   while(ob_get_level()>0){$status=ob_get_status();$removed+=(int)($status['buffer_used']??0);if(($status['flags']&PHP_OUTPUT_HANDLER_REMOVABLE)!==0){if(!ob_end_clean())break;}else{if(($status['flags']&PHP_OUTPUT_HANDLER_CLEANABLE)!==0)ob_clean();if(ob_get_length())throw new \RuntimeException('A PHP output handler could not be cleared.');break;}}
   if($removed)\Illuminate\Support\Facades\Log::warning('CCS ZIP download removed pending PHP output',['bytes'=>$removed]);
  }
  @ini_set('zlib.output_compression','0');
  $headers=['Cache-Control'=>'no-store, private','Content-Type'=>'application/zip','Content-Length'=>(string)$size,'Content-Encoding'=>'identity','X-Content-Type-Options'=>'nosniff','X-CCS-Archive-SHA256'=>hash_file('sha256',$path)];
  return response()->streamDownload(function()use($path,$size){
   $input=fopen($path,'rb');if(!$input)throw new \RuntimeException('Could not open ZIP for download.');$sent=0;
   try{while(!feof($input)){$chunk=fread($input,1048576);if($chunk===false)throw new \RuntimeException('ZIP read failed during download.');if($chunk==='')break;$sent+=strlen($chunk);echo $chunk;}if($sent!==$size)throw new \RuntimeException('ZIP download byte count mismatch.');}finally{fclose($input);}
  },$filename,$headers);
 }
 private function downloadFailure(\Throwable $e): string {
  report($e);$reference=substr((string)\Illuminate\Support\Str::uuid(),0,8);\Illuminate\Support\Facades\Log::error('CCS ZIP download failed',['reference'=>$reference,'error'=>$e->getMessage(),'class'=>get_class($e)]);
  return 'ZIP download failed ['.$reference.']. '.($e instanceof \RuntimeException?mb_substr($e->getMessage(),0,220):'Download response error. Check the matching laravel.log entry.');
 }

 private function backupName(string $id,string $date): string {return 'CCS_Backup_'.\Illuminate\Support\Carbon::parse($date)->timezone('Asia/Colombo')->format('Y-m-d_H-i-s').'_'.substr($id,0,8).'.zip';}
 public function reports(Request $request){try{$meta=app(\App\Services\CcsReportExport::class)->create($request->user()->id);$file=app(\App\Services\CcsReportExport::class)->file($meta['id']);return $this->sendZip($file['path'],$meta['filename']);}catch(\Throwable $e){throw ValidationException::withMessages(['reports'=>$this->downloadFailure($e)]);}}
 public function reportDownload(Request $request,string $id){try{$file=app(\App\Services\CcsReportExport::class)->file($id);app(\App\Services\CcsAdministration::class)->audit($request->user()->id,'reports_zip_downloaded','report_export',null,['export_id'=>$id]);return $this->sendZip($file['path'],$file['meta']['filename']);}catch(\Throwable $e){throw ValidationException::withMessages(['reports'=>$this->downloadFailure($e)]);}}
 public function create(Request $request,CcsSqliteBackup $backups){try{$m=$backups->create($request->user()->id);return $this->sendZip($backups->archive($m['id']),$this->backupName($m['id'],$m['created_at']));}catch(\Throwable $e){throw ValidationException::withMessages(['backup'=>$this->downloadFailure($e)]);}}
 public function download(Request $request,string $id,CcsSqliteBackup $backups){try{$path=$backups->archive($id);app(\App\Services\CcsAdministration::class)->audit($request->user()->id,'database_backup_downloaded','backup',null,['backup_id'=>$id]);$meta=json_decode(file_get_contents(dirname($path).'/manifest.json'),true);return $this->sendZip($path,$this->backupName($id,$meta['created_at']));}catch(\Throwable $e){throw ValidationException::withMessages(['backup'=>$this->downloadFailure($e)]);}}
 public function stage(Request $request,CcsSqliteBackup $backups){$request->validate(['password'=>['required','string'],'confirmation'=>['required','accepted']]);
  if(!Hash::check($request->password,$request->user()->password))throw ValidationException::withMessages(['password'=>'Owner password is incorrect. Enter the password you use to log in to this owner account.']);
  $file=$request->file('backup_file');
  if($file&&!$file->isValid()){
   $message=match($file->getError()){
    UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE=>'Backup ZIP exceeds the web server upload limit (upload_max_filesize: '.ini_get('upload_max_filesize').'; post_max_size: '.ini_get('post_max_size').'). Restart the PHP service after changing its settings.',
    UPLOAD_ERR_PARTIAL=>'Backup upload was incomplete. Select the ZIP again and retry.',
    UPLOAD_ERR_NO_TMP_DIR=>'PHP upload temporary folder is missing. Configure a writable upload_tmp_dir and restart PHP.',
    UPLOAD_ERR_CANT_WRITE=>'PHP could not write the uploaded ZIP. Check the upload temporary folder permissions and free disk space.',
    UPLOAD_ERR_EXTENSION=>'A PHP extension stopped the backup upload. Check the PHP server log.',
    default=>'No usable backup ZIP was uploaded. Select the database backup ZIP again.',
   };
   throw ValidationException::withMessages(['backup_file'=>$message]);
  }
  $request->validate(['backup_file'=>['required','file','max:308224']],['backup_file.required'=>'Select the database backup ZIP before continuing.','backup_file.uploaded'=>'Backup upload failed before validation. Check the web PHP upload limits and temporary folder shown on this page.']);
  try{$job=$backups->stage($request->file('backup_file')->getRealPath(),$request->user()->email);app(\App\Services\CcsAdministration::class)->audit($request->user()->id,'database_restore_prepared','backup',null,['request_id'=>$job['id'],'backup_id'=>$job['backup_id']]);if($request->expectsJson())return response()->json(['review_url'=>route('ccs.settings.backups.review',$job['id'])]);return redirect()->route('ccs.settings.backups.review',$job['id']);}catch(\Throwable $e){report($e);throw ValidationException::withMessages(['backup_file'=>$e instanceof \RuntimeException?$e->getMessage():'Backup could not be validated. The live database was not changed.']);}}
 public function apply(Request $request,string $id,CcsSqliteBackup $backups){
  abort_unless(app(\App\Services\CcsAdministration::class)->owner($request->user()),403);
  $request->validate(['password'=>['required','string'],'confirmation'=>['required','accepted']]);
  if(!Hash::check($request->password,$request->user()->password))throw ValidationException::withMessages(['password'=>'Owner password is incorrect. Enter your current login password.']);
  try{$job=$backups->job($id);}catch(\Throwable $e){throw ValidationException::withMessages(['restore'=>'Restore request not found. Select and validate the backup again.']);}
  abort_unless($job['owner_email']===mb_strtolower($request->user()->email),403);
  if($job['state']!=='ready'||$job['expires_at']<time())throw ValidationException::withMessages(['restore'=>'This request expired or was already used. Select and validate the backup again.']);
  // Laravel workers normally pause during maintenance; do not overwrite beneath a running job.
  if(\Illuminate\Support\Facades\Schema::hasTable('jobs')&&DB::table('jobs')->whereNotNull('reserved_at')->exists())throw ValidationException::withMessages(['restore'=>'A background job is running. Wait for it to finish, then retry Restore Now.']);
  $lock=$request->attributes->get('ccs_database_lock');
  if(!is_resource($lock))throw ValidationException::withMessages(['restore'=>'Restore locking is unavailable. Refresh and retry.']);
  if(!flock($lock,LOCK_EX|LOCK_NB))throw ValidationException::withMessages(['restore'=>'Another system request is active. Wait a few seconds and retry Restore Now.']);
  $maintenance=app(\Illuminate\Contracts\Foundation\MaintenanceMode::class);
  if($maintenance->active())throw ValidationException::withMessages(['restore'=>'The system is already under maintenance. Please retry after maintenance ends.']);
  @set_time_limit(0);ignore_user_abort(true);$completed=false;
  try{
   $maintenance->activate(['time'=>time(),'status'=>503,'retry'=>15,'refresh'=>15]);
   if(\Illuminate\Support\Facades\Schema::hasTable('jobs')&&DB::table('jobs')->whereNotNull('reserved_at')->exists())throw new \RuntimeException('A background job is running. Wait for it to finish, then retry Restore Now.');
   $result=$backups->restore($id,$lock);$completed=true;
   \Illuminate\Support\Facades\Auth::guard('web')->forgetUser();$request->session()->invalidate();$request->session()->regenerateToken();
   // Clear cached business/configuration values while all other web requests remain excluded.
   \Illuminate\Support\Facades\Artisan::call('optimize:clear');
   $maintenance->deactivate();
   return response()->view('ccs.backup-result',['result'=>$result,'failed'=>false],200)->header('Cache-Control','no-store, private');
  }catch(\Throwable $e){
   report($e);
   // Pre-write failures are safe to retry in the browser. Uncertain recovery stays closed.
   $state=null;try{$state=$backups->job($id)['state'];}catch(\Throwable $ignored){}
   if(!$completed&&$state==='ready'){$maintenance->deactivate();throw ValidationException::withMessages(['restore'=>$e instanceof \RuntimeException?$e->getMessage():'Restore did not start. Please retry.']);}
   return response()->view('ccs.backup-result',['result'=>null,'failed'=>true],503)->header('Cache-Control','no-store, private');
  }
 }
 public function review(Request $request,string $id,CcsSqliteBackup $backups){try{$job=$backups->job($id);}catch(\Throwable $e){abort(404,'Restore request not found.');}abort_unless($job['owner_email']===mb_strtolower($request->user()->email),403);return view('ccs.backup-review',compact('job'));}
}
