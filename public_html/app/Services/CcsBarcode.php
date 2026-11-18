<?php
namespace App\Services;
use Illuminate\Validation\ValidationException;
class CcsBarcode {
 public function svg(string $code): string {
  if(!preg_match('/^[\x20-\x7e]{1,100}$/D',$code))throw ValidationException::withMessages(['barcode'=>'Code128 labels need 1–100 printable ASCII characters. Use the actual ASCII SKU/barcode or a supported actual serial.']);
  $patterns=explode(' ','212222 222122 222221 121223 121322 131222 122213 122312 132212 221213 221312 231212 112232 122132 122231 113222 123122 123221 223211 221132 221231 213212 223112 312131 311222 321122 321221 312212 322112 322211 212123 212321 232121 111323 131123 131321 112313 132113 132311 211313 231113 231311 112133 112331 132131 113123 113321 133121 313121 211331 231131 213113 213311 213131 311123 311321 331121 312113 312311 332111 314111 221411 431111 111224 111422 121124 121421 141122 141221 112214 112412 122114 122411 142112 142211 241211 221114 413111 241112 134111 111242 121142 121241 114212 124112 124211 411212 421112 421211 212141 214121 412121 111143 111341 131141 114113 114311 411113 411311 113141 114131 311141 411131 211412 211214 211232 2331112');
  $symbols=[104];$checksum=104;foreach(str_split($code) as $i=>$char){$v=ord($char)-32;$symbols[]=$v;$checksum+=$v*($i+1);}$symbols[]=$checksum%103;$symbols[]=106;
  $x=10;$bars='';foreach($symbols as $symbol){foreach(str_split($patterns[$symbol]) as $i=>$width){$w=(int)$width;if($i%2===0)$bars.='<rect x="'.$x.'" y="0" width="'.$w.'" height="50"/>';$x+=$w;}}
  return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.($x+10).' 50" role="img" aria-label="'.htmlspecialchars($code,ENT_QUOTES,'UTF-8').'" preserveAspectRatio="none"><rect width="100%" height="100%" fill="white"/><g fill="black">'.$bars.'</g></svg>';
 }
}
