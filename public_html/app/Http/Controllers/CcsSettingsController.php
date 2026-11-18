<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\CcsAdministration;
class CcsSettingsController extends Controller {
 public function uiMode(Request $request){
  $data=$request->validate(['ui_mode'=>'required|in:desktop,touch']);$admin=app(CcsAdministration::class);$prefs=$admin->preferences($request->user()->id);$prefs['touch_mode']=$data['ui_mode']==='touch';
  DB::table('ccs_staff_preferences')->updateOrInsert(['user_id'=>$request->user()->id],['preferences'=>json_encode($prefs),'created_at'=>now(),'updated_at'=>now()]);
  $admin->audit($request->user()->id,'staff_ui_mode_saved','user',$request->user()->id,['ui_mode'=>$data['ui_mode']]);
  return redirect()->to(url($data['ui_mode']==='touch'?'/t/settings':'/ccs/settings'))->with('success','UI mode saved.');
 }
 public function index(Request $request){$admin=app(CcsAdministration::class);$preferences=$admin->preferences($request->user()->id);$owner=$admin->owner($request->user());$idle=$admin->setting('idle_minutes',60);$staffUsers=null;$staffStats=null;if($owner){$staffUsers=DB::table('users')->select('id','name','email','ccs_role','ccs_active')->orderBy('name')->paginate(15,['*'],'staff_page');$staffStats=DB::table('users')->selectRaw('COUNT(*) AS total, SUM(CASE WHEN ccs_active = 1 THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN ccs_active = 0 THEN 1 ELSE 0 END) AS disabled')->first();}return view('ccs.settings',compact('preferences','owner','idle','staffUsers','staffStats'));}
 public function language(Request $request){$data=$request->validate(['language'=>['required',Rule::in(array_keys(config('ccs_admin.locales')))]]);$request->session()->put('ccs_language',$data['language']);$response=$request->expectsJson()?response()->json(['language'=>$data['language']]):back();return $response->cookie('ccs_language',$data['language'],525600,null,null,$request->isSecure(),true);}
 public function theme(Request $request){
  $data=$request->validate(['custom_accent'=>'nullable|boolean','accent_color'=>['nullable','regex:/^#[0-9a-fA-F]{6}$/'],'accent_brightness'=>'nullable|integer|min:40|max:100','theme'=>['required',Rule::in(['default','default2','light','dark','skyline','vegan','neon','eggplant','nikita','desert','aqua'])]]);
  DB::transaction(function()use($request,$data){$row=DB::table('ccs_staff_preferences')->where('user_id',$request->user()->id)->lockForUpdate()->first();$prefs=$row?(json_decode($row->preferences,true)?:[]):[];$prefs['theme']=$data['theme'];foreach(['custom_accent','accent_color','accent_brightness'] as $k){if(array_key_exists($k,$data))$prefs[$k]=$k==='custom_accent'?(bool)$data[$k]:$data[$k];}DB::table('ccs_staff_preferences')->updateOrInsert(['user_id'=>$request->user()->id],['preferences'=>json_encode($prefs),'created_at'=>$row->created_at??now(),'updated_at'=>now()]);});
  app(CcsAdministration::class)->audit($request->user()->id,'staff_theme_saved','user',$request->user()->id,['theme'=>$data['theme']]);
  return redirect()->route('ccs.settings')->with('success',__('Theme saved.'));
 }
 public function preferences(Request $request){
  $data=$request->validate(['touch_mode'=>'nullable|boolean','scanner'=>'required|in:camera,reader,manual','paper'=>'required|in:a4,80mm,58mm','invoice_locale'=>['required',Rule::in(array_keys(config('ccs_admin.locales')))],'label_width'=>'required|integer|min:25|max:100','label_height'=>'required|integer|min:15|max:100','notify_jobs'=>'nullable|boolean','notify_stock'=>'nullable|boolean','notify_warranty'=>'nullable|boolean','notify_claims'=>'nullable|boolean']);
  foreach(['notify_jobs','notify_stock','notify_warranty','notify_claims'] as $key)$data[$key]=$request->boolean($key);
  $data=array_merge(app(CcsAdministration::class)->preferences($request->user()->id),$data);
  DB::table('ccs_staff_preferences')->updateOrInsert(['user_id'=>$request->user()->id],['preferences'=>json_encode($data),'created_at'=>now(),'updated_at'=>now()]);app(CcsAdministration::class)->audit($request->user()->id,'staff_preferences_saved','user',$request->user()->id);return back()->with('success','Preferences saved.');
 }
 public function inputMethod(Request $request){
  $data=$request->validate(['input_method'=>'required|in:english,phonetic,wijesekara']);
  DB::transaction(function()use($request,$data){
   $row=DB::table('ccs_staff_preferences')->where('user_id',$request->user()->id)->lockForUpdate()->first();
   $prefs=$row?(json_decode($row->preferences,true)?:[]):[];$prefs['input_method']=$data['input_method'];
   DB::table('ccs_staff_preferences')->updateOrInsert(['user_id'=>$request->user()->id],['preferences'=>json_encode($prefs),'created_at'=>$row->created_at??now(),'updated_at'=>now()]);
  });
  if($request->expectsJson())return response()->json(['input_method'=>$data['input_method']])->header('Cache-Control','no-store');
  return back()->with('success','Keyboard input method saved.');
 }
 public function security(Request $request){$data=$request->validate(['idle_minutes'=>'required|integer|min:15|max:240']);DB::table('ccs_system_settings')->updateOrInsert(['key'=>'idle_minutes'],['value'=>json_encode((int)$data['idle_minutes']),'created_at'=>now(),'updated_at'=>now()]);app(CcsAdministration::class)->audit($request->user()->id,'security_settings_saved','setting',null,$data);return back()->with('success','Session timeout saved.');}
 public function users(){return view('ccs.staff-users',['users'=>DB::table('users')->orderBy('name')->paginate(25)]);}
 public function saveUser(Request $request){
  $request->merge(['email'=>mb_strtolower(trim((string)$request->input('email')))]);
  $data=$request->validate(['id'=>'nullable|integer|exists:users,id','name'=>'required|string|max:150','email'=>['required','email','max:255',Rule::unique('users','email')->ignore($request->input('id'))],'role'=>'required|in:owner,manager,cashier,technician,legacy','active'=>'nullable|boolean','password'=>[$request->filled('id')?'nullable':'required','string','min:12','max:128','confirmed'],'stock_permissions_present'=>'nullable|in:1','permissions'=>'nullable|array:stock_create,stock_update,stock_receive,stock_serial_manage','permissions.*'=>'boolean']);
  DB::transaction(function()use($request,$data){$users=DB::table('users')->orderBy('id')->lockForUpdate()->get();$existing=isset($data['id'])?$users->firstWhere('id',(int)$data['id']):null;foreach($users as $u)if((!$existing||(int)$u->id!==(int)$existing->id)&&mb_strtolower($u->email)===$data['email'])throw ValidationException::withMessages(['email'=>'This staff email is already registered.']);$admin=app(CcsAdministration::class);
   if($existing&&(int)$existing->id===(int)$request->user()->id&&(!$request->boolean('active')||$data['role']!=='owner'))throw ValidationException::withMessages(['role'=>'Keep your own owner account active. Use another owner to change it.']);
   $changes=['name'=>$data['name'],'email'=>strtolower($data['email']),'ccs_role'=>$data['role'],'ccs_active'=>$request->boolean('active'),'updated_at'=>now()];
   if(!empty($data['password']))$changes['password']=Hash::make($data['password']);
   if($existing){$changes['ccs_session_version']=(int)$existing->ccs_session_version+1;$changes['remember_token']=null;DB::table('users')->where('id',$existing->id)->update($changes);$id=$existing->id;if($id==$request->user()->id){$request->session()->put('ccs_staff_version',$changes['ccs_session_version']);if(!empty($changes['password']))$request->session()->put('ccs_password_fingerprint',hash('sha256',$changes['password']));}}
   else{$changes['created_at']=now();$id=DB::table('users')->insertGetId($changes);}
   if($request->input('stock_permissions_present')==='1'){
    $prefRow=DB::table('ccs_staff_preferences')->where('user_id',$id)->lockForUpdate()->first();$prefs=$prefRow?(json_decode($prefRow->preferences,true)?:[]):[];$allowed=[];
    foreach(['stock_create','stock_update','stock_receive','stock_serial_manage'] as $permission)$allowed[$permission]=$data['role']==='cashier' && $request->boolean('permissions.'.$permission);
    $prefs['owner_permissions']=$allowed;DB::table('ccs_staff_preferences')->updateOrInsert(['user_id'=>$id],['preferences'=>json_encode($prefs),'created_at'=>$prefRow->created_at??now(),'updated_at'=>now()]);
    $admin->audit($request->user()->id,'staff_stock_permissions_updated','user',$id,['permissions'=>$allowed]);
   }
   $admin->audit($request->user()->id,$existing?'staff_account_updated':'staff_account_created','user',$id,['role'=>$data['role'],'active'=>$request->boolean('active'),'password_reset'=>!empty($data['password'])]);
  });return back()->with('success','Staff account saved. Previous sessions are revoked after an account update.');
 }
 public function audit(Request $request){$request->validate(['q'=>'nullable|string|max:100']);$q=trim((string)$request->input('q',''));$logs=DB::table('audit_logs as a')->leftJoin('users as u','u.id','=','a.user_id')->when($q!=='',fn($x)=>$x->where(fn($w)=>$w->where('a.action','like','%'.$q.'%')->orWhere('u.name','like','%'.$q.'%')->orWhere('a.entity_type','like','%'.$q.'%')))->orderByDesc('a.id')->paginate(40,['a.id','a.action','a.entity_type','a.entity_id','a.created_at','u.name as staff_name'])->withQueryString();return view('ccs.audit',compact('logs','q'));}
 public function printView(Request $request,int $id){$data=app(CcsAdminController::class)->invoice($id)->getData();$invoice=$data['invoice'];$prefs=app(CcsAdministration::class)->preferences($request->user()->id);$locale=$invoice->print_locale?:$prefs['invoice_locale'];abort_unless(array_key_exists($locale,config('ccs_admin.locales')),422);app()->setLocale($locale);$payments=DB::table('payments')->where('invoice_id',$id)->orderBy('paid_at')->orderBy('id')->get();$paid=$payments->reduce(fn($sum,$p)=>round($sum+(float)$p->amount_lkr,2),0);$paper=$invoice->print_paper?:$prefs['paper'];return response()->view('ccs.invoice-print',array_merge($data,compact('payments','paid','paper')))->header('Cache-Control','no-store, private');}
 public function invoicePrint(Request $request,int $id){$data=$request->validate(['print_locale'=>['required',Rule::in(array_keys(config('ccs_admin.locales')))],'print_paper'=>'required|in:a4,80mm,58mm']);abort_unless(DB::table('invoices')->where('id',$id)->exists(),404);DB::table('invoices')->where('id',$id)->update($data+['updated_at'=>now()]);app(CcsAdministration::class)->audit($request->user()->id,'invoice_print_preferences_saved','invoice',$id,$data);return back()->with('success','Invoice print preferences saved.');}
}
