<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\CcsSerialLookupService;
class CcsCustomerWarrantyPageController extends Controller {
 public function customer(Request $request){return $this->page($request,(int)$request->attributes->get('portal_account')->customer_id,false);}
 public function preview(Request $request){$data=$request->validate(['customer_id'=>'required|integer|min:1']);return $this->page($request,(int)$data['customer_id'],true);}
 private function page(Request $request,int $customerId,bool $preview){
  $request->validate(['vehicle_id'=>'nullable|integer|min:1','serial'=>'nullable|string|max:100','page'=>'nullable|integer|min:1']);
  $customer=DB::table('customers')->where('id',$customerId)->whereNull('deleted_at')->first(['id','name','customer_code']);abort_unless($customer,404);
  $vehicles=DB::table('vehicles')->where('customer_id',$customerId)->whereNull('deleted_at')->orderBy('registration_number')->get(['id','registration_number','make','model']);
  $vehicleId=(int)$request->input('vehicle_id',0);if($vehicleId)abort_unless($vehicles->contains('id',$vehicleId),404);
  $serial=trim((string)$request->input('serial',''));$cards=[];$lookup=app(CcsSerialLookupService::class);
  foreach($vehicles as $vehicle){if($vehicleId&&$vehicle->id!=$vehicleId)continue;foreach($lookup->warranties($customerId,$vehicle->id) as $card){if($serial!==''&&mb_strtoupper((string)$card['covered_serial'])!==mb_strtoupper($serial))continue;$card['vehicle']=$vehicle;$cards[]=$card;}}
  usort($cards,fn($a,$b)=>$b['id']<=>$a['id']);$page=(int)$request->input('page',1);$cards=new LengthAwarePaginator(array_slice($cards,($page-1)*12,12),count($cards),12,$page,['path'=>$request->url(),'query'=>$request->query()]);
  return response()->view('customer.warranties',compact('customer','vehicles','cards','preview','vehicleId','serial'))->header('Cache-Control','no-store, private')->header('X-Robots-Tag','noindex, nofollow');
 }
}
