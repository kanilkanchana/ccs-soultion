<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema};
class CcsCareFeatures {
    public const DEFAULTS=['api_enabled'=>true,'manual_enabled'=>true,'direct_lookup'=>false,'auto_invoice'=>true,'auto_payment'=>true,'auto_status'=>true,'auto_serial'=>false];
    public function values(): array {
        if(!app()->bound('db'))return self::DEFAULTS;
        if(!Schema::hasTable('ccs_care_state'))return self::DEFAULTS;
        $raw=DB::table('ccs_care_state')->where('key','developer_features_v3')->value('value');
        if($raw===null)return self::DEFAULTS;
        $data=json_decode($raw,true);
        if(!is_array($data))return array_fill_keys(array_keys(self::DEFAULTS),false);
        $out=[];foreach(self::DEFAULTS as $k=>$v)$out[$k]=($data[$k]??false)===true;return $out;
    }
    public function on(string $key): bool {return $this->values()[$key]??false;}
    public function api(): void {if(!$this->on('api_enabled'))throw new \RuntimeException('WhatsApp API is disabled by the developer. No API message was sent.');}
    public function fingerprint(): string {return hash('sha256',json_encode($this->values()));}
    public function save(array $flags): void {
        $data=[];foreach(self::DEFAULTS as $key=>$v)$data[$key]=($flags[$key]??false)===true;
        DB::table('ccs_care_state')->updateOrInsert(['key'=>'developer_features_v3'],['value'=>json_encode($data),'created_at'=>now(),'updated_at'=>now()]);
        // Never replay queued business messages after changing a feature policy.
        DB::table('ccs_care_outbox')->where('state','pending')->update(['state'=>'cancelled','updated_at'=>now()]);
        DB::table('ccs_care_challenges')->whereIn('state',['pending','sent'])->update(['state'=>'replaced','otp_hash'=>null,'updated_at'=>now()]);
        $baseline=DB::table('ccs_care_state')->where('key','baseline')->value('value');
        if($baseline!==null){$state=json_decode($baseline,true);if(is_array($state)){$state['audit_id']=(int)DB::table('audit_logs')->max('id');DB::table('ccs_care_state')->where('key','baseline')->update(['value'=>json_encode($state),'updated_at'=>now()]);}}
    }
}
