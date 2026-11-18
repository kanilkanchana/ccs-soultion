<?php
namespace App\Services;
use Illuminate\Support\Facades\{Crypt,DB,Schema};
use RuntimeException;
class CcsMessagingSettings {
    private const KEY='owner_whatsapp_settings';
    public function load(): ?array {
        if(!Schema::hasTable('ccs_care_state'))return null;
        $value=DB::table('ccs_care_state')->where('key',self::KEY)->value('value');
        if($value===null)return null;
        try{$data=json_decode(Crypt::decryptString($value),true,32,JSON_THROW_ON_ERROR);if(!is_array($data))throw new RuntimeException;return $data;}
        catch(\Throwable){throw new RuntimeException('Saved WhatsApp settings cannot be decrypted. Re-enter them as owner; sending is stopped.');}
    }
    public function apply(): void {
        try{$s=$this->load();}catch(\Throwable){config(['ccs_care_sandbox.enabled'=>false,'ccs_care_sandbox.notifications_enabled'=>false,'ccs_whatsapp_test.enabled'=>false]);return;}
        if($s===null)return;
        config(['ccs_whatsapp_test.enabled'=>(bool)$s['enabled'],'ccs_whatsapp_test.access_token'=>$s['access_token'],'ccs_whatsapp_test.phone_number_id'=>$s['phone_number_id'],'ccs_whatsapp_test.recipient'=>$s['recipient'],'ccs_whatsapp_test.graph_version'=>$s['graph_version'],
         'ccs_care_sandbox.enabled'=>(bool)$s['enabled'],'ccs_care_sandbox.customer_id'=>(int)$s['customer_id'],'ccs_care_sandbox.language'=>$s['language'],'ccs_care_sandbox.notifications_enabled'=>(bool)$s['notifications_enabled'],
         'ccs_care_sandbox.otp_template'=>$s['otp_template'],'ccs_care_sandbox.invoice_template'=>$s['invoice_template'],'ccs_care_sandbox.payment_template'=>$s['payment_template'],'ccs_care_sandbox.status_template'=>$s['status_template'],'ccs_care_sandbox.serial_template'=>$s['serial_template']??'']);
    }
    public function save(array $settings): void {
        DB::table('ccs_care_state')->updateOrInsert(['key'=>self::KEY],['value'=>Crypt::encryptString(json_encode($settings,JSON_THROW_ON_ERROR)),'created_at'=>now(),'updated_at'=>now()]);$this->apply();
    }
    public function publicValues(): array {
        $error=null;try{$saved=$this->load();}catch(RuntimeException $e){$saved=null;$error=$e->getMessage();}
        $values=$saved??['enabled'=>config('ccs_care_sandbox.enabled'),'phone_number_id'=>config('ccs_whatsapp_test.phone_number_id'),'recipient'=>config('ccs_whatsapp_test.recipient'),'graph_version'=>config('ccs_whatsapp_test.graph_version'),'customer_id'=>config('ccs_care_sandbox.customer_id'),'language'=>config('ccs_care_sandbox.language'),'notifications_enabled'=>config('ccs_care_sandbox.notifications_enabled'),'otp_template'=>config('ccs_care_sandbox.otp_template'),'invoice_template'=>config('ccs_care_sandbox.invoice_template'),'payment_template'=>config('ccs_care_sandbox.payment_template'),'status_template'=>config('ccs_care_sandbox.status_template')];
        $values['serial_template']=$saved['serial_template']??config('ccs_care_sandbox.serial_template','');
        $values['has_token']=trim((string)($saved['access_token']??config('ccs_whatsapp_test.access_token'))) !=='';
        unset($values['access_token']);$values['read_error']=$error;return $values;
    }
}
