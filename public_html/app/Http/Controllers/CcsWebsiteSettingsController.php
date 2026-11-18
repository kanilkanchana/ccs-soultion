<?php
namespace App\Http\Controllers;
use App\Services\{CcsWebsiteSettings,CcsAdministration};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,Validator};
class CcsWebsiteSettingsController extends Controller {
 private function owner(Request $r): void {abort_unless(app(CcsAdministration::class)->owner($r->user('web')),403);}
 public function index(Request $r,CcsWebsiteSettings $settings){$this->owner($r);return view('ccs.website-settings',['values'=>$settings->values(),'ready'=>$settings->ready()]);}
 public function save(Request $r,CcsWebsiteSettings $settings){
  $this->owner($r);abort_unless($settings->ready(),503,'Run the website migration first.');
  $v=Validator::make($r->all(),['current_password'=>'required|string|max:200','enabled'=>'nullable|boolean','origins'=>'required|string|max:3000']);
  if($v->fails()||!Hash::check((string)$r->input('current_password'),$r->user('web')->getAuthPassword()))return back()->withErrors(['settings'=>'Check the fields and current owner password.']);
  $origins=array_values(array_unique(array_filter(array_map('trim',preg_split('/[\r\n,]+/',$r->input('origins'))))));
  foreach($origins as $origin){$u=parse_url($origin);if(!$u||!isset($u['scheme'],$u['host'])||isset($u['user'])||isset($u['pass'])||isset($u['query'])||isset($u['fragment'])||isset($u['path'])||strlen($origin)>255||!in_array($u['scheme'],['http','https'],true)||($u['scheme']==='http'&&!app()->environment('local')))return back()->withErrors(['origins'=>'Use exact origins without paths or a trailing slash. Production origins must use HTTPS.']);}
  if(!$origins)return back()->withErrors(['origins'=>'Allow at least one website origin.']);
  DB::transaction(function()use($r,$settings,$origins){$settings->save(['enabled'=>$r->boolean('enabled'),'origins'=>$origins]);app(CcsAdministration::class)->audit($r->user('web')->id,'website_connection_saved','setting',null,['enabled'=>$r->boolean('enabled'),'origins'=>$origins]);});
  return back()->with('success','Website connection settings saved. No customer or business records changed.');
 }
 public function developer(Request $r,CcsWebsiteSettings $settings){$developer=$r->attributes->get('ccs_developer');abort_unless($developer,403);return view('developer.website',['developer'=>$developer,'values'=>$settings->values()]);}
 public function developerSave(Request $r,CcsWebsiteSettings $settings){
  $dev=$r->attributes->get('ccs_developer');abort_unless($dev&&$dev->active,403);$v=Validator::make($r->all(),['current_password'=>'required|string|max:128','lookup_mode'=>'required|in:disabled,plate_serial,plate_only']);
  if($v->fails()||!Hash::check((string)$r->input('current_password'),$dev->password))return back()->withErrors(['settings'=>'Check the current developer password and lookup mode.']);
  if($r->input('lookup_mode')!=='disabled'&&!$r->boolean('ack_public'))return back()->withErrors(['lookup_mode'=>'Confirm that this is an unverified public view of vehicle/serial/warranty/status records.']);
  DB::transaction(function()use($r,$settings,$dev){$settings->save(['lookup_mode'=>$r->input('lookup_mode')]);DB::table('ccs_developer_events')->insert(['developer_account_id'=>$dev->id,'action'=>'website_lookup_policy_saved','details'=>json_encode(['lookup_mode'=>$r->input('lookup_mode')]),'created_at'=>now(),'updated_at'=>now()]);});
  return back()->with('success','Public lookup policy saved. Private customer identity, contact details and invoice balances remain excluded.');
 }
}
