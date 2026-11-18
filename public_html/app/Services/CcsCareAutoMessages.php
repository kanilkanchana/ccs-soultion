<?php
namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CcsCareAutoMessages {
    public function afterRequest(Request $request): void {
        if(!app(CcsCareFeatures::class)->on('api_enabled')||!app(CcsCareEnvironment::class)->allowed()||config('ccs_care_sandbox.enabled')!==true||config('ccs_care_sandbox.notifications_enabled')!==true||!$request->isMethod('POST'))return;
        if(!in_array($request->route()?->getName(),['ccs.invoices.finalize','ccs.invoices.payments.store','ccs.jobs.status','ccs.jobs.save','ccs.warranties.assign','ccs.warranties.approve'],true))return;
        $lock=null;
        try{
            if(app()->isDownForMaintenance())return;
            $lock=fopen(app(CcsSqliteBackup::class)->root().'/database.lock','c');if(!$lock||!flock($lock,LOCK_SH|LOCK_NB))return;
            app(CcsCareOutbox::class)->collect();app(CcsCareOutbox::class)->dispatch();
        }catch(\Throwable){
            // Never break a completed checkout or log credential-bearing HTTP exceptions.
            try{DB::table('ccs_care_state')->updateOrInsert(['key'=>'last_worker_error'],['value'=>'Notification processing paused. Check message states, approved templates, token validity and the local baseline.','created_at'=>now(),'updated_at'=>now()]);}catch(\Throwable){}
        }finally{if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}}
    }
}
