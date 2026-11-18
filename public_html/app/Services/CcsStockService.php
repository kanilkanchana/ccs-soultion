<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CcsStockService
{
    public function enabled(): bool {return (bool)DB::table('ccs_feature_settings')->where('key','stock_pos')->value('enabled');}
    private function fail(string $message): never {throw ValidationException::withMessages(['stock'=>$message]);}
    public function prepareLines(array $lines,array $data): array {
        $used=[];
        foreach($lines as $index=>&$line){
            $partId=$data['inventory_item_id'][$index]??null;$unitId=$data['stock_unit_id'][$index]??null;
            if(!$partId){if($unitId)$this->fail('Choose the part for this serial.');continue;}
            if(!$this->enabled())$this->fail('Stock POS trial is disabled. Use manual entry or enable it in Parts & Stock.');
            $part=DB::table('inventory_items')->where('id',$partId)->where('active',true)->first();
            if(!$part)$this->fail('A selected part is unavailable. Search the stock again.');
            if((int)round($line['unit_price_lkr']*100)!==(int)round((float)$part->selling_price_lkr*100))$this->fail('Stock price changed. Search and add the part again.');
            if($line['quantity']!=floor($line['quantity']))$this->fail('Stock quantities must be whole numbers.');
            $serial=null;
            if($part->has_serial&&($part->serial_on_sale??false)&&!$unitId){
                $serial=trim((string)($data['manual_serial'][$index]??''));
                if($line['quantity']!=1||$serial===''||mb_strlen($serial)>100)$this->fail('Open the sealed pack and enter its actual serial. Add one unit per row.');
                $existing=DB::table('stock_units')->where('serial_key',mb_strtoupper($serial))->first();
                if($existing&&((int)$existing->inventory_item_id!==(int)$partId||$existing->status!=='available'))$this->fail('This serial is already used or belongs to another part.');
                if($existing){$unitId=$existing->id;$serial=$existing->serial_number;}elseif((int)$part->quantity_on_hand<=DB::table('stock_units')->where('inventory_item_id',$partId)->where('status','available')->count())$this->fail('All available packs already have serials. Select an existing unit.');
            }elseif($part->has_serial){
                if(!$unitId||$line['quantity']!=1)$this->fail('Select one actual serial for each serialized part. Add another serial as another item.');
                if(isset($used[$unitId]))$this->fail('The same serial cannot be used twice in one invoice.');$used[$unitId]=true;
                $unit=DB::table('stock_units')->where('id',$unitId)->where('inventory_item_id',$partId)->where('status','available')->first();
                if(!$unit)$this->fail('This serial is sold or reserved. Select an available serial.');$serial=$unit->serial_number;
            }elseif($unitId)$this->fail('This part does not use serial numbers.');
            if(array_key_exists($index,$data['expected_warranty_rule_id']??[]) && (int)($data['expected_warranty_rule_id'][$index]??0)!==(int)($part->default_warranty_rule_id??0))$this->fail('The part warranty package changed. Reload the POS and add the part again.');
            if($part->default_warranty_rule_id && $line['quantity']!=1)$this->fail('Add warranty-covered parts as separate rows with quantity 1 so each receives its own card.');
            $warranty=app(CcsStockWarrantyService::class)->snapshot($part->default_warranty_rule_id);
            $line=array_merge($line,['requires_serial'=>(bool)$part->has_serial,'is_battery_module'=>(bool)$part->is_battery_module,'sale_warranty_rule_id'=>$part->default_warranty_rule_id,'sale_warranty_snapshot'=>$warranty?json_encode($warranty,JSON_UNESCAPED_UNICODE):null]);
            $line=array_merge($line,['inventory_item_id'=>$part->id,'stock_unit_id'=>$unitId?:null,'serial_number'=>$serial,'stock_managed'=>true,'item_origin'=>'stock',
                'description'=>$part->name,'unit_price_lkr'=>(float)$part->selling_price_lkr,'line_total_lkr'=>round($line['quantity']*(float)$part->selling_price_lkr,2),
                'item_year'=>$part->item_year,'item_condition'=>$part->condition,'item_supplier'=>$part->supplier]);
        }unset($line);return $lines;
    }
    public function reserve(object $invoice,array $lines,int $userId): array {
        $managed=array_values(array_filter($lines,fn($l)=>!empty($l['stock_managed'])));
        if(!$managed)return $lines;
        // Re-check the feature flag and prices under the transaction before changing stock.
        if(!$this->enabled())$this->fail('Stock POS trial was disabled. Reload the POS page.');
        usort($managed,fn($a,$b)=>$a['inventory_item_id']<=>$b['inventory_item_id']);
        foreach($managed as $line){
            $part=DB::table('inventory_items')->where('id',$line['inventory_item_id'])->lockForUpdate()->first();
            if(!$part||!$part->active)$this->fail('A selected part is no longer active.');
            if((bool)$part->is_battery_module!==(bool)($line['is_battery_module']??false)||(bool)$part->has_serial!==(bool)($line['requires_serial']??false))$this->fail('Part serial requirements changed. Reload and add the part again.');
            if((int)round((float)$part->selling_price_lkr*100)!==(int)round($line['unit_price_lkr']*100))$this->fail('A stock price changed. Reload the POS page before saving.');
            if((int)($line['sale_warranty_rule_id']??0)!==(int)($part->default_warranty_rule_id??0))$this->fail('The part warranty package changed. Reload and add the part again.');
            $currentWarranty=app(CcsStockWarrantyService::class)->snapshot($part->default_warranty_rule_id);
            if(($line['sale_warranty_snapshot']??null)!==($currentWarranty?json_encode($currentWarranty,JSON_UNESCAPED_UNICODE):null))$this->fail('Warranty conditions changed. Reload and add the part again.');
            $qty=(int)$line['quantity'];
            if($qty<=0||$qty>$part->quantity_on_hand)$this->fail('Not enough available stock for '.$part->name.'.');
            if($part->has_serial&&($part->serial_on_sale??false)&&empty($line['stock_unit_id'])){
                $serial=trim((string)($line['serial_number']??''));
                if($qty!==1||$serial===''||mb_strlen($serial)>100)$this->fail('Enter the actual sealed-pack serial before saving.');
                if(DB::table('stock_units')->where('serial_key',mb_strtoupper($serial))->exists())$this->fail('This serial was already registered. Reload and try again.');
                if((int)$part->quantity_on_hand<=DB::table('stock_units')->where('inventory_item_id',$part->id)->where('status','available')->count())$this->fail('No unopened packs remain. Select an existing serial.');
                $newUnit=DB::table('stock_units')->insertGetId(['inventory_item_id'=>$part->id,'serial_number'=>$serial,'serial_key'=>mb_strtoupper($serial),'status'=>'available','created_at'=>now(),'updated_at'=>now()]);
                foreach($lines as &$savedLine){if((int)($savedLine['inventory_item_id']??0)===(int)$part->id&&($savedLine['serial_number']??null)===$serial&&empty($savedLine['stock_unit_id']))$savedLine['stock_unit_id']=$newUnit;}unset($savedLine);
                $line['stock_unit_id']=$newUnit;
            }
            if($part->has_serial){
                $unit=DB::table('stock_units')->where('id',$line['stock_unit_id'])->where('inventory_item_id',$part->id)->lockForUpdate()->first();
                if(!$unit||$unit->status!=='available')$this->fail('A selected serial was just reserved or sold.');
                $changed=DB::table('stock_units')->where('id',$unit->id)->where('status','available')->update(['status'=>$invoice->status==='draft'?'reserved':'sold','invoice_id'=>$invoice->id,'updated_at'=>now()]);
                if($changed!==1)$this->fail('A selected serial is no longer available.');
            }
            $changed=DB::table('inventory_items')->where('id',$part->id)->where('quantity_on_hand','>=',$qty)->decrement('quantity_on_hand',$qty,['updated_at'=>now()]);
            if($changed!==1)$this->fail('Available stock changed. Reload and try again.');
            DB::table('stock_movements')->insert(['inventory_item_id'=>$part->id,'repair_job_id'=>$invoice->repair_job_id,'recorded_by'=>$userId,
                'quantity_change'=>-$qty,'reason'=>$invoice->status==='draft'?'pos_reserved':'pos_sale','notes'=>'Invoice '.$invoice->invoice_number.($line['serial_number']?' / Serial '.$line['serial_number']:''),'created_at'=>now(),'updated_at'=>now()]);
        }
        return $lines;
    }
    public function finalize(int $invoiceId): void {
        DB::table('stock_units')->where('invoice_id',$invoiceId)->where('status','reserved')->update(['status'=>'sold','updated_at'=>now()]);
    }
    public function releaseDraft(object $invoice,int $userId): void {
        $lines=DB::table('invoice_items')->where('invoice_id',$invoice->id)->where('stock_managed',true)->orderBy('inventory_item_id')->get();
        foreach($lines as $line){
            $part=DB::table('inventory_items')->where('id',$line->inventory_item_id)->lockForUpdate()->first();if(!$part)$this->fail('The saved stock part is missing.');
            if($line->stock_unit_id){
                $changed=DB::table('stock_units')->where('id',$line->stock_unit_id)->where('invoice_id',$invoice->id)->where('status','reserved')->update(['status'=>'available','invoice_id'=>null,'updated_at'=>now()]);
                if($changed!==1)$this->fail('This serial cannot be released from this draft.');
            }
            DB::table('inventory_items')->where('id',$part->id)->increment('quantity_on_hand',(int)$line->quantity,['updated_at'=>now()]);
            DB::table('stock_movements')->insert(['inventory_item_id'=>$part->id,'repair_job_id'=>$invoice->repair_job_id,'recorded_by'=>$userId,'quantity_change'=>(int)$line->quantity,'reason'=>'draft_released','notes'=>'Cancelled draft '.$invoice->invoice_number,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
}
