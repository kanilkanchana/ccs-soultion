<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CcsWebsiteContent {
 const KINDS=['ads'=>'Advertisements','models'=>'Car Models','videos'=>'Videos','offers'=>'Offers'];
 public function authorize($user): void { $a=app(CcsAdministration::class);abort_unless($user&&$user->ccs_active&&($a->owner($user)||in_array($a->role($user),['cashier','staff','manager','legacy'],true)),403); }
 public function mediaRoot(): string { $web=dirname(base_path()).'/public_html/admin';return is_file($web.'/index.php')?$web.'/website-media':public_path('website-media'); }
 public function mediaUrl(string $path): string {return url('/website-media/'.$path);}
 public function rows(string $kind,bool $public=false){return DB::table('ccs_website_content')->where('kind',$kind)->when($public,fn($q)=>$q->where('active',true))->orderBy('sort_order')->orderBy('id')->get()->map(function($r){$r->data=json_decode($r->data,true,32,JSON_THROW_ON_ERROR);return $r;});}
 public function publicData(): array {$out=[];foreach(self::KINDS as $kind=>$label)$out[$kind]=$this->rows($kind,true)->map(fn($r)=>array_merge($r->data,['id'=>$r->slug,'title'=>$r->title]))->values()->all();return $out;}
 public function safeUrl(?string $value,bool $required=false): string {$value=trim((string)$value);if($value===''){if($required)throw ValidationException::withMessages(['url'=>'Enter a URL.']);return '';}$p=parse_url($value);if(!$p||($p['scheme']??'')!=='https'||empty($p['host'])||isset($p['user'])||isset($p['pass']))throw ValidationException::withMessages(['url'=>'Use a complete HTTPS URL.']);return $value;}
 public function embed(string $platform,string $url): string {
  $url=$this->safeUrl($url,true);$p=parse_url($url);$host=strtolower($p['host']);$path=$p['path']??'';
  if($platform==='video'){if(!preg_match('/\.(mp4|webm)(?:$|\?)/i',$url))throw ValidationException::withMessages(['url'=>'Direct video URLs must end in .mp4 or .webm.']);return '';}
  if($platform==='facebook'&&in_array($host,['facebook.com','www.facebook.com','m.facebook.com','fb.watch'],true))return 'https://www.facebook.com/plugins/video.php?href='.rawurlencode($url).'&show_text=false&width=560';
  if($platform==='instagram'&&in_array($host,['instagram.com','www.instagram.com'],true)&&preg_match('~^/(?:p|reel|reels)/([A-Za-z0-9_-]+)/?~',$path,$m))return 'https://www.instagram.com/'.(str_starts_with($path,'/p/')?'p':'reel').'/'.$m[1].'/embed/';
  if($platform==='tiktok'&&in_array($host,['tiktok.com','www.tiktok.com'],true)&&preg_match('~/video/(\d+)~',$path,$m))return 'https://www.tiktok.com/player/v1/'.$m[1];
  if($platform==='youtube'&&in_array($host,['youtube.com','www.youtube.com','youtu.be'],true)){parse_str($p['query']??'',$q);$id=$host==='youtu.be'?trim($path,'/'):($q['v']??'');if(preg_match('~^/(?:shorts|embed)/([\w-]+)~',$path,$m))$id=$m[1];if(preg_match('/^[A-Za-z0-9_-]{11}$/',$id))return 'https://www.youtube-nocookie.com/embed/'.$id;}
  throw ValidationException::withMessages(['url'=>'Use a valid original video/reel URL for the selected platform.']);
 }
}
