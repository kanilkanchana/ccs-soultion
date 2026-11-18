<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\CcsCareOutbox;
use RuntimeException;
class CcsCareNotifications extends Command {
    protected $signature='ccs:care-notifications {--init : Start at the current audit position; do not send old history} {--collect-only : Queue new test events without sending} {--status : Show safe configuration and outbox status without sending}';
    protected $description='Local or explicitly enabled hosted TEST-customer notifications only';
    public function handle(CcsCareOutbox $outbox): int {
        try{
            if($this->option('status')){
                $features=app(\App\Services\CcsCareFeatures::class)->values();
                $this->table(['Check','Value'],[
                    ['mode',app(\App\Services\CcsCareEnvironment::class)->label()],
                    ['environment allowed',app(\App\Services\CcsCareEnvironment::class)->allowed()?'YES':'NO'],
                    ['sandbox enabled',config('ccs_care_sandbox.enabled')===true?'YES':'NO'],
                    ['automatic notifications',config('ccs_care_sandbox.notifications_enabled')===true?'ON':'OFF'],
                    ['API master',$features['api_enabled']?'ON':'OFF'],
                    ['test customer',(string)config('ccs_care_sandbox.customer_id')],
                    ['baseline saved',\Illuminate\Support\Facades\DB::table('ccs_care_state')->where('key','baseline')->exists()?'YES':'NO'],
                ]);
                foreach(['invoice','payment','status','serial'] as $kind)$this->line($kind.': '.($features['auto_'.$kind]?'AUTO ON':'AUTO OFF'));
                foreach(\Illuminate\Support\Facades\DB::table('ccs_care_outbox')->selectRaw('state, COUNT(*) AS total')->groupBy('state')->get() as $row)$this->line($row->state.': '.$row->total);
                $this->info('Status only. No message sent.');return self::SUCCESS;
            }
            app(\App\Services\CcsCareScope::class)->customer();
            if(app()->isDownForMaintenance())throw new RuntimeException('Database maintenance: notification processing paused.');
            $lock=fopen(app(\App\Services\CcsSqliteBackup::class)->root().'/database.lock','c');
            if(!$lock||!flock($lock,LOCK_SH|LOCK_NB))throw new RuntimeException('Database maintenance lock is active.');
            if($this->option('init')){if(!$this->confirm('Baseline current audit history and cancel pending sandbox messages?',false))return self::SUCCESS;$outbox->initialize();$this->info('Baseline saved. Old history will not be sent.');return self::SUCCESS;}
            $n=$outbox->collect();$sent=$this->option('collect-only')?0:$outbox->dispatch();$this->info('New test events queued: '.$n.'. Meta accepted: '.$sent.'. Accepted does not mean delivered.');return self::SUCCESS;
        }catch(RuntimeException $e){$this->error($e->getMessage());return self::FAILURE;}finally{if(isset($lock)&&is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}}
    }
}
