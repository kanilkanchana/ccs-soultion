<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
class CcsDeveloperAccount extends Authenticatable {
 protected $table='ccs_developer_accounts';protected $guarded=['id'];protected $hidden=['password'];
 protected function casts(): array {return ['can_manage_accounts'=>'boolean','active'=>'boolean','can_clear_data'=>'boolean','access_version'=>'integer'];}
}
