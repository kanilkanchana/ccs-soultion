<?php
namespace App\Services;
class CcsCareEnvironment {
 public function allowed():bool {return app()->environment('local')||config('ccs_care_sandbox.hosted_test_enabled')===true;}
 public function label():string {return app()->environment('local')?'LOCAL TEST MODE':'HOSTED TEST MODE — ONE VERIFIED RECIPIENT';}
}
