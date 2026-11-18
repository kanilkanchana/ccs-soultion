<?php
namespace App\Services;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class CcsCareTransport {
    public function otp(string $code): string {
        if(!preg_match('/^[0-9]{6}$/D',$code))throw new RuntimeException('Invalid OTP shape.');
        return $this->send('otp',[$code],[['type'=>'button','sub_type'=>'url','index'=>'0','parameters'=>[['type'=>'text','text'=>$code]]]]);
    }
    public function update(string $kind,array $payload): string {
        $expected=['invoice'=>4,'payment'=>4,'status'=>3,'serial'=>6];
        if(($payload['v']??null)!==3||!isset($expected[$kind])||!is_array($payload['body']??null)||count($payload['body'])!==$expected[$kind])throw new RuntimeException('Old or unsupported notification format. Prepare a new message using v3.');
        if(!($payload['manual']??false)&&config('ccs_care_sandbox.notifications_enabled')!==true)throw new RuntimeException('Automatic notifications disabled.');
        $customer=app(CcsCareScope::class)->customer();
        if(($payload['customer_id']??0)!==(int)$customer->id)throw new RuntimeException('Message customer scope changed. Prepare a new message.');
        return $this->send($kind,$payload['body'],[], $kind==='invoice'?($payload['snapshot']??null):null);
    }
    private function send(string $kind,array $parameters,array $extras=[],?array $snapshot=null): string {
        app(CcsCareScope::class)->customer();
        $template=(string)config('ccs_care_sandbox.'.$kind.'_template');$lang=(string)config('ccs_care_sandbox.language');
        if(!preg_match('/^[a-z0-9_]{1,100}$/D',$template)||!preg_match('/^[a-z]{2}(?:_[A-Z]{2})?$/D',$lang))throw new RuntimeException('Configure the approved '.$kind.' template and exact language in WhatsApp Settings.');
        foreach($parameters as $p)if(!is_string($p)||strlen($p)>500||preg_match('/[\r\n\t]/',$p))throw new RuntimeException('Invalid template parameter.');
        $base='https://graph.facebook.com/'.config('ccs_whatsapp_test.graph_version').'/'.config('ccs_whatsapp_test.phone_number_id');
        $request=fn()=>Http::withToken((string)config('ccs_whatsapp_test.access_token'))->acceptJson()->connectTimeout(5)->timeout(25)->withoutRedirecting();
        try{
            $sender=$request()->get($base,['fields'=>'display_phone_number']);$this->check($sender);
            if(preg_replace('/\D/','',(string)$sender->json('display_phone_number'))!==CcsWhatsAppTest::SANDBOX_NUMBER)throw new RuntimeException('Blocked: not the CCS Meta sandbox sender.');
            $components=[];
            if($kind==='invoice'){
                if(!$snapshot)throw new RuntimeException('Invoice PDF snapshot is missing. Prepare a new message.');
                $bytes=app(CcsCarePdf::class)->bytes($snapshot);
                if(strlen($bytes)>10*1024*1024)throw new RuntimeException('Invoice PDF exceeds the local 10 MB limit.');
                $filename='CCS-invoice-'.preg_replace('/[^A-Za-z0-9_-]/','-',(string)$snapshot['invoice']['invoice_number']).'.pdf';
                $media=$request()->attach('file',$bytes,$filename,['Content-Type'=>'application/pdf'])->post($base.'/media',['messaging_product'=>'whatsapp','type'=>'application/pdf']);$this->check($media);
                $mediaId=$media->json('id');if(!is_string($mediaId)||!preg_match('/^[0-9]{3,40}$/D',$mediaId))throw new RuntimeException('Meta media upload did not return a usable ID. No invoice message was sent.');
                $components[]=['type'=>'header','parameters'=>[['type'=>'document','document'=>['id'=>$mediaId,'filename'=>$filename]]]];
            }
            $components[]=['type'=>'body','parameters'=>array_map(fn($p)=>['type'=>'text','text'=>$p],$parameters)];$components=array_merge($components,$extras);
            // Re-check the master switch after PDF preparation, immediately before sending.
            app(CcsCareFeatures::class)->api();
            $r=$request()->asJson()->post($base.'/messages',['messaging_product'=>'whatsapp','to'=>preg_replace('/\D/','',(string)config('ccs_whatsapp_test.recipient')),'type'=>'template','template'=>['name'=>$template,'language'=>['code'=>$lang],'components'=>$components]]);$this->check($r);
            $id=$r->json('messages.0.id');if(!is_string($id)||!preg_match('/^wamid\.[A-Za-z0-9_+=\/.-]{1,500}$/D',$id))throw new RuntimeException('UNKNOWN delivery: no usable message ID. Check Meta before retrying.');return $id;
        }catch(ConnectionException){throw new RuntimeException('UNKNOWN delivery: connection timed out. Check WhatsApp/Meta before retrying.');}
    }
    private function check($r): void {
        if($r->successful())return;$code=$r->json('error.code');
        throw new RuntimeException('Meta rejected request: HTTP '.$r->status().(is_numeric($code)?' / code '.(int)$code:'').'. Check token, template approval, language and test recipient.');
    }
}
