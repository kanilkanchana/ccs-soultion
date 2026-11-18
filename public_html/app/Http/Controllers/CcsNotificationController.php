<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CcsStaffNotifications;
class CcsNotificationController extends Controller {
 public function index(Request $request){return view('ccs.notifications',['items'=>app(CcsStaffNotifications::class)->items($request->user())]);}
 public function count(Request $request){$items=app(CcsStaffNotifications::class)->items($request->user());return response()->json(['unread'=>count(array_filter($items,fn($i)=>!$i['read']))])->header('Cache-Control','no-store, private');}
 public function read(Request $request){$request->validate(['key'=>'nullable|string|max:160']);$items=app(CcsStaffNotifications::class)->items($request->user());foreach($items as $item)if(!$request->filled('key')||$request->key===$item['key'])DB::table('ccs_notification_reads')->insertOrIgnore(['user_id'=>$request->user()->id,'notification_key'=>$item['key'],'read_at'=>now()]);return back()->with('success','Notifications marked as read.');}
}
