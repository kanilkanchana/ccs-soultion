<?php
namespace App\Services;
use Illuminate\Http\Request;
class CcsInvoiceInput{public function hash(Request $r):string{return hash('sha256',json_encode($r->only(['repair_job_id','description','quantity','unit_price','inventory_item_id','stock_unit_id','manual_serial','expected_warranty_rule_id','discount','initial_received','initial_method','initial_reference']),JSON_UNESCAPED_UNICODE));}}
