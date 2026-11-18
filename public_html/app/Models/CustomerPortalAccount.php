<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
class CustomerPortalAccount extends Authenticatable
{
    protected $table='customer_portal_accounts';
    protected $guarded=['id'];
    protected $hidden=['password','remember_token'];
    protected function casts(): array {
        return ['password'=>'hashed','active'=>'boolean','access_version'=>'integer'];
    }
}
