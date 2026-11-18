<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
class CcsCustomerAccessController extends Controller
{
    public function index(){
        $customers=DB::table('customers')->whereNull('deleted_at')->orderBy('name')->get(['id','name','phone','customer_code']);
        $accounts=DB::table('customer_portal_accounts as a')->join('customers as c','c.id','=','a.customer_id')->whereNull('c.deleted_at')->select('a.id','a.customer_id','a.username','a.active','c.name','c.phone')->orderBy('c.name')->get();
        return view('ccs.customer-access',compact('customers','accounts'));
    }
    public function save(Request $request){
        $data=$request->validate([
            'customer_id'=>['required','integer',Rule::exists('customers','id')->whereNull('deleted_at')],
            'username'=>['required','string','min:3','max:80','regex:/^[a-z0-9._-]+$/'],
            'password'=>['required','string','min:12','max:200','confirmed'],
        ]);
        DB::transaction(function()use($data,$request){
            DB::table('customers')->where('id',$data['customer_id'])->lockForUpdate()->first();
            $account=DB::table('customer_portal_accounts')->where('customer_id',$data['customer_id'])->first();
            if(DB::table('customer_portal_accounts')->where('username',$data['username'])->where('customer_id','!=',$data['customer_id'])->exists()){
                throw \Illuminate\Validation\ValidationException::withMessages(['username'=>'This username is already in use.']);
            }
            $values=['username'=>$data['username'],'password'=>Hash::make($data['password']),'active'=>true,'access_version'=>($account->access_version??0)+1,'remember_token'=>null,'updated_at'=>now()];
            if($account)DB::table('customer_portal_accounts')->where('id',$account->id)->update($values);
            else DB::table('customer_portal_accounts')->insert(array_merge($values,['customer_id'=>$data['customer_id'],'created_at'=>now()]));
            DB::table('audit_logs')->insert(['user_id'=>$request->user('web')->id,'action'=>'customer_portal_access_saved','entity_type'=>'customer','entity_id'=>$data['customer_id'],'metadata'=>json_encode(['username'=>$data['username']]),'created_at'=>now(),'updated_at'=>now()]);
        },3);
        return back()->with('success','Customer access saved. Give the login details to the customer privately. Previous customer sessions have been signed out.');
    }
    public function revoke(Request $request,int $id){
        DB::transaction(function()use($id,$request){
            $account=DB::table('customer_portal_accounts')->where('id',$id)->lockForUpdate()->first();abort_unless($account,404);
            DB::table('customer_portal_accounts')->where('id',$id)->update(['active'=>false,'access_version'=>$account->access_version+1,'remember_token'=>null,'updated_at'=>now()]);
            DB::table('audit_logs')->insert(['user_id'=>$request->user('web')->id,'action'=>'customer_portal_access_revoked','entity_type'=>'customer','entity_id'=>$account->customer_id,'metadata'=>null,'created_at'=>now(),'updated_at'=>now()]);
        });
        return back()->with('success','Customer access stopped.');
    }
}
