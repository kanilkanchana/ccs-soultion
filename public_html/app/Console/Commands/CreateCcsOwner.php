<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCcsOwner extends Command
{
    protected $signature = 'ccs:create-owner';
    protected $description = 'Create the first local CCS owner account using interactive prompts';

    public function handle(): int
    {
        if (!Schema::hasTable('users')) {
            $this->error('Run php artisan migrate first.');
            return self::FAILURE;
        }
        $name = $this->ask('Owner display name');
        $email = $this->ask('Owner login email');
        $password = $this->secret('Password (minimum 12 characters)');
        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string)$password) < 12) {
            $this->error('Provide a name, valid email, and password at least 12 characters.');
            return self::FAILURE;
        }
        if (DB::table('users')->where('email', $email)->exists()) {
            $this->error('This email already exists.');
            return self::FAILURE;
        }
        DB::table('users')->insert(['name'=>$name,'email'=>$email,'password'=>Hash::make($password),'created_at'=>now(),'updated_at'=>now()]);
        $this->info('Owner login created. Keep credentials private. This MVP does not yet implement staff roles.');
        return self::SUCCESS;
    }
}
