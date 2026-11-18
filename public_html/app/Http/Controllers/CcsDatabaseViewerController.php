<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
class CcsDatabaseViewerController extends Controller {
 public static function sensitive(string $name): bool {return (bool)preg_match('/password|token|secret|payload|cookie|credential|private_key|api_key|authorization|otp|session|^value$|^data$/i',$name);}
 public function index(Request $r){
  abort_unless($r->attributes->get('ccs_developer')?->active,403);
  $d=$r->validate(['table'=>'nullable|string|max:180','column'=>'nullable|string|max:180','q'=>'nullable|string|max:150','sort'=>'nullable|string|max:180','direction'=>'nullable|in:asc,desc','page'=>'nullable|integer|min:1|max:10000']);
  $tables=collect(Schema::getTables())->pluck('name')->filter(fn($n)=>!str_starts_with($n,'sqlite_'))->sort()->values();
  $table=$d['table']??$tables->first();$columns=[];$rows=null;$count=0;$sort=null;
  if($table){abort_unless($tables->containsStrict($table),404);$columns=Schema::getColumns($table);$names=collect($columns)->pluck('name');$visible=$names->reject(fn($n)=>self::sensitive($n))->values();
   $query=DB::table($table);$column=$d['column']??'';
   if(($d['q']??'')!==''){abort_unless($visible->containsStrict($column),422,'Select a non-sensitive column to filter.');$query->where($column,'like','%'.$d['q'].'%');}
   $count=(clone $query)->count();$sort=$d['sort']??($visible->contains('id')?'id':$visible->first());if($sort){abort_unless($visible->containsStrict($sort),422);$query->orderBy($sort,$d['direction']??'asc');}
   // Never fetch credential/session blobs, even if the developer inspects HTML.
   if($visible->isNotEmpty())$rows=$query->select($visible->all())->paginate(50)->withQueryString();
  }
  $changes=app(\App\Services\CcsDeveloperDatabaseChanges::class);$permissions=$changes->grant($r->attributes->get('ccs_developer'));$controlsReady=$changes->ready();
  return response()->view('developer.database-viewer',compact('tables','table','columns','rows','count','sort','permissions','controlsReady')+['developer'=>$r->attributes->get('ccs_developer'),'driver'=>DB::connection()->getDriverName()])->header('Cache-Control','no-store, private');
 }
}
