<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema,Route};
class CcsWebsiteNotifications {
 public function items($user): array {
  if(!Schema::hasTable('ccs_website_requests')||!Route::has('ccs.website.index')||!in_array(app(CcsAdministration::class)->role($user),['owner','manager','legacy','cashier'],true))return [];
  return DB::table('ccs_website_requests')->where('status','new')->orderByDesc('id')->limit(20)->get()->map(fn($i)=>['key'=>'website:'.$i->id,'type'=>'website','title'=>'New website '.$i->kind,'text'=>$i->public_reference.' · '.$i->name,'url'=>route('ccs.website.show',$i->id),'date'=>$i->created_at])->all();
 }
}
