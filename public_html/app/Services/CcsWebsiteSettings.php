<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema};
class CcsWebsiteSettings {
 public function values(): array {
  $out=['enabled'=>(bool)config('ccs_website.enabled',false),'origins'=>array_values(config('ccs_website.origins',[])),'lookup_mode'=>'disabled'];
  if(!Schema::hasTable('ccs_website_settings'))return array_merge($out,['enabled'=>false]);
  foreach(DB::table('ccs_website_settings')->get(['key','value']) as $row){if(array_key_exists($row->key,$out)){$v=json_decode($row->value,true);$out[$row->key]=$v;}}
  if(!is_bool($out['enabled'])||!is_array($out['origins'])||!in_array($out['lookup_mode'],['disabled','plate_serial','plate_only'],true))return ['enabled'=>false,'origins'=>[],'lookup_mode'=>'disabled'];
  return $out;
 }
 public function save(array $values): void {foreach($values as $key=>$value)DB::table('ccs_website_settings')->updateOrInsert(['key'=>$key],['value'=>json_encode($value),'created_at'=>now(),'updated_at'=>now()]);}
 public function ready(): bool {return Schema::hasTable('ccs_website_requests')&&Schema::hasTable('ccs_website_settings');}
}
