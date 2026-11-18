<?php
namespace App\Services;
class CcsCarePdf {
    public function bytes(array $snapshot): string {
        $autoload=base_path('ccs-care-pdf/vendor/autoload.php');
        if(!is_file($autoload))throw new \RuntimeException('The bundled PDF component is missing. Extract ccs-care-pdf into the project root.');
        require_once $autoload;
        $dir=storage_path('app/private/ccs-care-pdf');if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new \RuntimeException('Cannot create the private PDF cache.');
        $pdf=new \Mpdf\Mpdf(['tempDir'=>$dir,'format'=>'A4','margin_left'=>14,'margin_right'=>14,'default_font'=>'freeserif','autoScriptToLang'=>true,'autoLangToFont'=>true]);
        $pdf->SetTitle('CCS Solutions Invoice');$pdf->SetAuthor('CCS Solutions');
        $oldLocale=app()->getLocale();$locale=$snapshot['invoice']['print_locale']??$oldLocale;
        if(in_array($locale,['en','si','si_LK','ta','ta_LK'],true))app()->setLocale($locale);
        try{$pdf->WriteHTML(view('ccs.care-invoice-pdf',['s'=>$snapshot])->render());return $pdf->Output('','S');}finally{app()->setLocale($oldLocale);}
    }
}
