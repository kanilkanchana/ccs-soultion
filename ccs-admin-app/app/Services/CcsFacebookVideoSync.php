<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Http,Crypt};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
class CcsFacebookVideoSync {
 private const FIELDS='id,title,description,permalink_url,picture,created_time,published,privacy,from';
 public function owner($user): void {abort_unless($user&&$user->ccs_active&&app(CcsAdministration::class)->owner($user),403);}
 public function status(): array {
  $r=DB::table('ccs_facebook_sync')->find(1);if(!$r)return ['connected'=>false];
  return ['connected'=>!empty($r->verified_at),'page_id'=>$r->page_id,'page_name'=>$r->page_name,'api_version'=>$r->api_version,'auto_sync'=>(bool)$r->auto_sync,'publish_new'=>(bool)$r->publish_new,'include_reels'=>(bool)$r->include_reels,'interval_minutes'=>$r->interval_minutes,'revision'=>$r->revision,'last_run_at'=>$r->last_run_at,'last_success_at'=>$r->last_success_at,'last_scheduler_at'=>$r->last_scheduler_at,'next_run_at'=>$r->next_run_at,'last_status'=>$r->last_status,'last_message'=>$r->last_message];
 }
 private function graph(string $version,string $path,string $token,array $query): array {
  if(!preg_match('/^v[0-9]{1,3}\.0$/',$version)||!preg_match('~^(?:me|[0-9]+(?:/(?:videos|video_reels))?)$~',$path))throw new CcsFacebookSyncException('Invalid Facebook connection configuration.');
  try{$response=Http::acceptJson()->withToken($token)->withoutRedirecting()->connectTimeout(4)->timeout(8)->get('https://graph.facebook.com/'.$version.'/'.$path,$query);}catch(\Throwable $e){throw new CcsFacebookSyncException('Facebook could not be reached. Try again later.');}
  $body=$response->json();if(!$response->successful()||!is_array($body)||isset($body['error'])){
   $code=(int)($body['error']['code']??0);
   if($code===190)throw new CcsFacebookSyncException('Facebook token expired or was revoked. Owner: save a valid Page access token.');
   if(in_array($code,[10,200],true))throw new CcsFacebookSyncException('Facebook denied video access. Owner: check the Page token and Meta app read permissions.');
   if(in_array($code,[4,17,32,613],true)||$response->status()===429)throw new CcsFacebookSyncException('Facebook rate limit reached. Try again later.');
   throw new CcsFacebookSyncException('Facebook request failed. Check the configured Graph API version, Page ID and app access.');
  }return $body;
 }
 public function configure(array $v,$user): void {
  $this->owner($user);$old=DB::table('ccs_facebook_sync')->find(1);$revision=(int)($v['revision']??0);if($old&&(int)$old->revision!==$revision)throw ValidationException::withMessages(['page_id'=>'Settings changed. Reload before saving.']);
  $token=trim($v['password']??'');if($token===''){if(!$old||$old->page_id!==$v['page_id'])throw ValidationException::withMessages(['password'=>'Enter a Page access token for this Page.']);try{$token=Crypt::decryptString($old->token_encrypted);}catch(\Throwable $e){throw new CcsFacebookSyncException('Stored connection cannot be read. Owner: enter a new Page token.');}}
  $page=$this->graph($v['api_version'],'me',$token,['fields'=>'id,name,category']);if((string)($page['id']??'')!==$v['page_id']||!is_string($page['category']??null)||trim($page['category'])==='')throw ValidationException::withMessages(['page_id'=>'This token does not belong to the selected Facebook Page. Use a Page access token, not a personal User token.']);
  $this->graph($v['api_version'],$v['page_id'].'/videos',$token,['fields'=>self::FIELDS,'type'=>'uploaded','limit'=>1]);
  DB::transaction(function()use($v,$user,$old,$revision,$page,$token){$current=DB::table('ccs_facebook_sync')->lockForUpdate()->find(1);if(($current&&(int)$current->revision!==$revision)||(!$current&&$old))throw ValidationException::withMessages(['page_id'=>'Settings changed. Reload before saving.']);if($current&&$current->lease_until&&now()->lt($current->lease_until))throw new CcsFacebookSyncException('A sync is running. Wait before changing the connection.');
   $record=['page_id'=>$v['page_id'],'page_name'=>Str::limit((string)($page['name']??'Facebook Page'),175,''),'api_version'=>$v['api_version'],'token_encrypted'=>Crypt::encryptString($token),'auto_sync'=>false,'publish_new'=>(bool)($v['publish_new']??true),'include_reels'=>(bool)($v['include_reels']??true),'interval_minutes'=>(int)($v['interval_minutes']??60),'revision'=>$revision+1,'verified_at'=>now(),'last_status'=>'ready','last_message'=>'Page verified. Run Sync Facebook Videos, then enable Auto Sync if wanted.','next_run_at'=>null,'updated_at'=>now()];if(!$current)$record+=['id'=>1,'created_at'=>now()];DB::table('ccs_facebook_sync')->updateOrInsert(['id'=>1],$record);app(CcsAdministration::class)->audit($user->id,'facebook_connection_saved','facebook_page',null,['page_id'=>$v['page_id']]);
  });
 }
 public function settings(array $v,$user): void {
  $this->owner($user);DB::transaction(function()use($v,$user){$r=DB::table('ccs_facebook_sync')->lockForUpdate()->find(1);if(!$r||!$r->verified_at)throw new CcsFacebookSyncException('Save and verify a Facebook Page connection first.');if((int)$r->revision!==(int)$v['revision'])throw ValidationException::withMessages(['auto_sync'=>'Settings changed. Reload before saving.']);if($r->lease_until&&now()->lt($r->lease_until))throw new CcsFacebookSyncException('A sync is running. Wait before changing settings.');DB::table('ccs_facebook_sync')->where('id',1)->update(['auto_sync'=>(bool)$v['auto_sync'],'publish_new'=>(bool)$v['publish_new'],'include_reels'=>(bool)$v['include_reels'],'interval_minutes'=>(int)$v['interval_minutes'],'revision'=>$r->revision+1,'next_run_at'=>$v['auto_sync']?now():null,'updated_at'=>now()]);app(CcsAdministration::class)->audit($user->id,'facebook_sync_settings_saved','facebook_page',null,['auto_sync'=>(bool)$v['auto_sync']]);});
 }
 public function runFromConsole(bool $dueOnly=false): array {
  $path=app(CcsSqliteBackup::class)->root().'/database.lock';$lock=fopen($path,'c');
  if(!$lock||!flock($lock,LOCK_SH|LOCK_NB)){if($lock)fclose($lock);throw new CcsFacebookSyncException('Database maintenance is in progress. Sync will retry later.');}
  try{return $dueOnly?$this->scheduler():$this->run();}finally{flock($lock,LOCK_UN);fclose($lock);}
 }
 public function scheduler(): array {DB::table('ccs_facebook_sync')->where('id',1)->update(['last_scheduler_at'=>now()]);return $this->run(null,true);}
 private function collect($r,string $edge,string $token): array {
  $rows=[];$after=null;$visited=[];$truncated=false;
  for($page=0;$page<2;$page++){$query=['fields'=>self::FIELDS,'limit'=>25];if($edge==='videos')$query['type']='uploaded';if($after)$query['after']=$after;$body=$this->graph($r->api_version,$r->page_id.'/'.$edge,$token,$query);if(!isset($body['data'])||!is_array($body['data']))throw new CcsFacebookSyncException('Facebook returned an unexpected video response.');foreach(array_slice($body['data'],0,25) as $item)if(is_array($item))$rows[]=$item;$cursor=$body['paging']['cursors']['after']??null;if(empty($body['paging']['next'])||!is_string($cursor)||strlen($cursor)>2048||isset($visited[$cursor]))break;$visited[$cursor]=true;$after=$cursor;$truncated=$page===1;}
  return [$rows,$truncated];
 }
 public function videoId(string $url): ?string {
  $p=parse_url($url);$host=strtolower($p['host']??'');if(!in_array($host,['facebook.com','www.facebook.com','m.facebook.com'],true))return null;$path=$p['path']??'';if(preg_match('~/(?:reel|videos)/(?:[^/]+/)?([0-9]+)(?:/|$)~',$path,$m))return $m[1];parse_str($p['query']??'',$q);$id=$q['v']??null;return is_string($id)&&preg_match('/^[0-9]+$/',$id)?$id:null;
 }
 private function thumbnail($value): string {
  if(!is_string($value)||strlen($value)>2048)return '';$p=parse_url($value);$host=strtolower($p['host']??'');if(($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||!(str_ends_with($host,'.fbcdn.net')||str_ends_with($host,'.fbsbx.com')))return '';parse_str($p['query']??'',$q);if(isset($q['access_token']))return '';return $value;
 }
 public function run($actor=null,bool $dueOnly=false): array {
  if($actor)app(CcsWebsiteContent::class)->authorize($actor);
  $run=Str::random(40);$r=DB::transaction(function()use($dueOnly,$run){$r=DB::table('ccs_facebook_sync')->lockForUpdate()->find(1);if(!$r||!$r->verified_at)return null;if($dueOnly&&(!$r->auto_sync||($r->next_run_at&&now()->lt($r->next_run_at))))return null;if($r->lease_until&&now()->lt($r->lease_until))throw new CcsFacebookSyncException('A Facebook sync is already running.');DB::table('ccs_facebook_sync')->where('id',1)->update(['run_token'=>$run,'lease_until'=>now()->addMinutes(2),'last_run_at'=>now(),'last_status'=>'running','last_message'=>null]);return $r;});
  if(!$r){if($dueOnly)return ['status'=>'skipped','message'=>'Auto Sync is off, not due, or not connected.'];throw new CcsFacebookSyncException('Owner: save and verify a Facebook Page connection first.');}
  try{
   try{$token=Crypt::decryptString($r->token_encrypted);}catch(\Throwable $e){throw new CcsFacebookSyncException('Stored Facebook token cannot be read. Owner: save the Page token again.');}
   [$videos,$truncated]=$this->collect($r,'videos',$token);$warning='';if($r->include_reels){try{[$reels,$more]=$this->collect($r,'video_reels',$token);$videos=array_merge($videos,$reels);$truncated=$truncated||$more;}catch(RuntimeException $e){$warning='Standard videos synced; Reels were not available. Check Page/App access for Reels.';}}
   $counts=DB::transaction(function()use($r,$run,$videos,$actor){$current=DB::table('ccs_facebook_sync')->lockForUpdate()->find(1);if(!$current||$current->run_token!==$run||(int)$current->revision!==(int)$r->revision)throw new CcsFacebookSyncException('Connection changed during sync. Retry.');$counts=['added'=>0,'existing'=>0,'skipped'=>0,'unpublished'=>0];$known=[];foreach(DB::table('ccs_website_content')->where('kind','videos')->get() as $card){$data=json_decode($card->data,true)?:[];if(strtolower($data['platform']??'')==='facebook'&&($id=$this->videoId($data['url']??'')))$known[$id]=$card;}
    $seen=[];foreach($videos as $v){$id=(string)($v['id']??'');if(!preg_match('/^[0-9]{1,80}$/',$id)||isset($seen[$id]))continue;$seen[$id]=true;$source=DB::table('ccs_facebook_video_sources')->where('page_id',$r->page_id)->where('video_id',$id)->first();$card=$source?DB::table('ccs_website_content')->find($source->content_id):($known[$id]??null);
     $public=($v['published']??false)===true&&strtoupper((string)($v['privacy']['value']??''))==='EVERYONE'&&(string)($v['from']['id']??'')===$r->page_id;
     if(!$public){$counts['skipped']++;if($source&&$card&&$card->active){DB::table('ccs_website_content')->where('id',$card->id)->update(['active'=>false,'revision'=>$card->revision+1,'updated_at'=>now()]);$counts['unpublished']++;}continue;}
     $url=(string)($v['permalink_url']??'');if(str_starts_with($url,'/'))$url='https://www.facebook.com'.$url;if($this->videoId($url)!==$id||parse_url($url,PHP_URL_SCHEME)!=='https'){$counts['skipped']++;continue;}$image=$this->thumbnail($v['picture']??'');
     if($card){$counts['existing']++;if($source&&$source->thumbnail_url&&$image){$data=json_decode($card->data,true)?:[];if(($data['image']??'')===$source->thumbnail_url){$data['image']=$data['asset']=$image;DB::table('ccs_website_content')->where('id',$card->id)->where('revision',$card->revision)->update(['data'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'revision'=>$card->revision+1,'updated_at'=>now()]);}}$contentId=$card->id;}
     else{$title=Str::limit(trim(strip_tags((string)($v['title']??$v['description']??''))),175,'');if($title==='')$title='Facebook video '.$id;$data=['platform'=>'facebook','url'=>$url,'embed_url'=>app(CcsWebsiteContent::class)->embed('facebook',$url),'image'=>$image,'asset'=>$image,'description'=>Str::limit(trim(strip_tags((string)($v['description']??''))),3900,''),'alt'=>$title];$contentId=DB::table('ccs_website_content')->insertGetId(['kind'=>'videos','slug'=>'facebook-'.$id,'title'=>$title,'active'=>(bool)$r->publish_new,'sort_order'=>0,'data'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'revision'=>1,'updated_by'=>$actor?->id,'created_at'=>now(),'updated_at'=>now()]);$counts['added']++;}
     DB::table('ccs_facebook_video_sources')->updateOrInsert(['page_id'=>$r->page_id,'video_id'=>$id],['content_id'=>$contentId,'thumbnail_url'=>$image,'updated_at'=>now(),'created_at'=>$source?->created_at??now()]);
    }return $counts;
   });
   $message=$counts['added'].' new; '.$counts['existing'].' already present; '.$counts['skipped'].' not eligible; '.$counts['unpublished'].' unpublished.';if($truncated)$message.=' Checked the most recent 50 items per video type.';if($warning)$message.=' '.$warning;
   DB::table('ccs_facebook_sync')->where('id',1)->where('run_token',$run)->update(['last_success_at'=>now(),'last_status'=>$warning?'partial':'success','last_message'=>$message,'next_run_at'=>now()->addMinutes($r->interval_minutes),'run_token'=>null,'lease_until'=>null,'updated_at'=>now()]);if($actor)app(CcsAdministration::class)->audit($actor->id,'facebook_videos_synced','facebook_page',null,$counts);return ['status'=>$warning?'partial':'success','message'=>$message]+$counts;
  }catch(\Throwable $e){$message=$e instanceof CcsFacebookSyncException?$e->getMessage():'Sync stopped safely. No incomplete video batch was saved.';DB::table('ccs_facebook_sync')->where('id',1)->where('run_token',$run)->update(['last_status'=>'error','last_message'=>$message,'next_run_at'=>now()->addMinutes(max(15,$r->interval_minutes)),'run_token'=>null,'lease_until'=>null,'updated_at'=>now()]);throw new CcsFacebookSyncException($message);}
 }
}
