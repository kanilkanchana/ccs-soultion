<?php
namespace App\Http\Controllers;
use App\Services\CcsCareFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,Validator};
class CcsCareDeveloperController extends Controller {
    public function index(Request $r,CcsCareFeatures $features){$developer=$r->attributes->get('ccs_developer');abort_unless($developer,403);return view('developer.messaging',['developer'=>$developer,'flags'=>$features->values()]);}
    public function save(Request $r,CcsCareFeatures $features){
        $developer=$r->attributes->get('ccs_developer');abort_unless($developer&&$developer->active,403);
        $v=Validator::make($r->all(),['current_password'=>'required|string|max:128','ack_direct'=>'nullable|accepted']);
        if($v->fails()||!Hash::check((string)$r->input('current_password'),$developer->password))return back()->withErrors(['current_password'=>'Check the current developer password and confirmation.']);
        if($r->boolean('direct_lookup')&&!$features->on('direct_lookup')&&!$r->boolean('ack_direct'))return back()->withErrors(['direct_lookup'=>'Confirm that direct lookup reveals the configured TEST customer records without OTP.']);
        $flags=[];foreach(CcsCareFeatures::DEFAULTS as $key=>$value)$flags[$key]=$r->boolean($key);
        DB::transaction(function()use($flags,$features,$developer){$features->save($flags);DB::table('ccs_developer_events')->insert(['developer_account_id'=>$developer->id,'action'=>'messaging_features_saved','details'=>json_encode($flags),'created_at'=>now(),'updated_at'=>now()]);});
        return back()->with('success','Messaging controls saved. Pending messages cancelled; customer sessions must start again. Local TEST-customer scope remains enforced.');
    }
}
