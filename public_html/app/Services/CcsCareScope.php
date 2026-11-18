<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use RuntimeException;
class CcsCareScope {
    public function customer(bool $sending = true): object {
        if (!app(CcsCareEnvironment::class)->allowed() || config('ccs_care_sandbox.enabled') !== true) throw new RuntimeException('Customer Care requires local mode or explicit hosted-test enablement, plus sandbox enablement.');
        if($sending){app(CcsCareFeatures::class)->api();app(CcsWhatsAppTest::class)->preview();}
        $id = (int) config('ccs_care_sandbox.customer_id');
        $c = DB::table('customers')->where('id',$id)->whereNull('deleted_at')->first();
        $destination = preg_replace('/\D/', '', (string) config('ccs_whatsapp_test.recipient'));
        $savedWhatsApp=trim((string)($c->whatsapp??''));
        $phone = $this->phone($savedWhatsApp!==''?$savedWhatsApp:(string)($c->phone??''));
        if (!$c || ($sending && (!$phone || !hash_equals($destination,$phone)))) throw new RuntimeException('Use one TEST customer whose saved WhatsApp/phone equals the verified Meta test recipient. No phone redirect is allowed.');
        return $c;
    }
    public function phone(string $s): string {
        $s=preg_replace('/\D/','',$s);
        if(preg_match('/^0[0-9]{9}$/D',$s)) $s='94'.substr($s,1);
        return $s;
    }
    public function epoch(): string { return app(CcsSqliteBackup::class)->epoch(); }
    public function plate(string $s): string { return mb_strtoupper(preg_replace('/[\s-]/u','',trim($s))); }
}
