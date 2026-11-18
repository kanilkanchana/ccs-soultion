<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CcsVehicleSearchController extends Controller
{
    public function index(Request $request){
        $data=$request->validate(['q'=>['required','string','max:150']]);$q=trim($data['q']);
        $base=DB::table('vehicles as v')->join('customers as c','c.id','=','v.customer_id')->whereNull('v.deleted_at')->whereNull('c.deleted_at')
            ->select('v.id','v.registration_number','v.make','v.model','v.manufacture_year','v.current_mileage_km','c.name as customer_name','c.customer_code','c.phone');
        $plate=strtoupper(str_replace([' ','-'],'',$q));$phones=[];
        if(preg_match('/^[+0-9\s()-]+$/',$q)){
            $digits=preg_replace('/\D/','',$q);if($digits!=='')$phones[]=$digits;
            if(strlen($digits)===9||(strlen($digits)===10&&str_starts_with($digits,'0'))||(strlen($digits)===11&&str_starts_with($digits,'94'))){$tail=substr($digits,-9);$phones=array_unique(array_merge($phones,[$tail,'0'.$tail,'94'.$tail]));}
        }
        $exact=(clone $base)->where(function($x)use($q,$plate,$phones){
            $x->whereRaw('UPPER(c.customer_code) = ?',[strtoupper($q)])
                ->orWhereRaw("UPPER(REPLACE(REPLACE(v.registration_number, ' ', ''), '-', '')) = ?",[$plate]);
            if(ctype_digit($q)&&strlen($q)<16)$x->orWhere('c.id',(int)$q);
            if($phones)$x->orWhereIn("c.phone",$phones)->orWhereIn(DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')"),$phones);
        })->orderBy('v.registration_number')->limit(51)->get();
        $mode='exact';$rows=$exact;
        if($rows->isEmpty()){
            $mode='model';$model=clone $base;
            foreach(preg_split('/\s+/',$q) as $term)$model->where(fn($x)=>$x->where('v.make','like','%'.$term.'%')->orWhere('v.model','like','%'.$term.'%'));
            $rows=$model->orderBy('v.registration_number')->limit(51)->get();
            if($rows->isEmpty()){$mode='name';$rows=(clone $base)->where('c.name','like','%'.$q.'%')->orderBy('v.registration_number')->limit(51)->get();}
        }
        $hasMore=$rows->count()>50;
        return response()->json(['data'=>$rows->take(50)->values(),'mode'=>$mode,'auto_select'=>$mode!=='model'&&$rows->count()===1,'has_more'=>$hasMore])->header('Cache-Control','no-store, private');
    }
}
