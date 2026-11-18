<?php
namespace App\Console\Commands;

use App\Models\CcsDeveloperAccount;
use App\Models\User;
use App\Services\CcsAdministration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ManageCcsDeveloper extends Command
{
    protected $signature = 'ccs:developer {--disable : Disable an existing developer login} {--team-admin : Grant team-account management}';
    protected $description = 'Create, update or disable developer access from the server terminal only';

    public function handle(): int
    {
        if (!$this->input->isInteractive()) {
            $this->error('Run this command interactively. Password arguments are not supported.');
            return self::FAILURE;
        }
        if (!Schema::hasTable('ccs_developer_accounts')) {
            $this->error('Run php artisan migrate first.');
            return self::FAILURE;
        }
        $email = mb_strtolower(trim((string) $this->ask('Developer login email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $this->error('Enter a valid email.');
            return self::FAILURE;
        }
        $account = CcsDeveloperAccount::where('email', $email)->first();
        if ($this->option('disable')) {
            if (!$account) { $this->error('Developer account not found.'); return self::FAILURE; }
            if (!$this->confirm('Disable this developer account and revoke its sessions?', false)) return self::FAILURE;
            DB::transaction(function () use ($account) {
                $current = CcsDeveloperAccount::lockForUpdate()->findOrFail($account->id);
                $current->active = false;
                $current->access_version++;
                $current->save();
                $this->record($current, 'terminal_access_disabled');
            });
            $this->info('Developer access disabled. Existing sessions are revoked.');
            return self::SUCCESS;
        }
        if ($account && !$this->confirm('Update this existing developer and revoke its sessions?', false)) return self::FAILURE;
        $ownerEmail = mb_strtolower(trim((string) $this->ask('Existing owner email (authorization record)')));
        $owner = User::whereRaw('LOWER(email) = ?', [$ownerEmail])->first();
        if (!$owner || !app(CcsAdministration::class)->owner($owner) || (isset($owner->ccs_active) && !$owner->ccs_active)) {
            $this->error('An active existing owner email is required.');
            return self::FAILURE;
        }
        $name = trim((string) $this->ask('Developer display name', $account?->name));
        $password = (string) $this->secret('New developer password (16–64 characters)', false);
        $repeat = (string) $this->secret('Repeat developer password', false);
        if ($name === '' || mb_strlen($name) > 150 || mb_strlen($password) < 16 || strlen($password) > 64 || !hash_equals($password, $repeat)) {
            $this->error('Provide a name up to 150 characters and matching passwords of 16–64 bytes.');
            return self::FAILURE;
        }
        $clear = $this->confirm('Allow this developer to clear business data?', false);
        if (!$this->confirm('Save and enable this developer login?', false)) return self::FAILURE;
        DB::transaction(function () use ($email, $name, $password, $owner, $clear) {
            $current = CcsDeveloperAccount::where('email', $email)->lockForUpdate()->first() ?? new CcsDeveloperAccount();
            $current->name = $name;
            $current->email = $email;
            $current->password = Hash::make($password);
            $current->active = true;
            if ($this->option('team-admin')) $current->can_manage_accounts = true;
            $current->can_clear_data = $clear;
            $current->authorized_by = $owner->id;
            $current->access_version = (int) ($current->access_version ?? 0) + 1;
            $current->save();
            $this->record($current, 'terminal_access_saved');
        });
        $this->info('Developer login saved. Sign in at /developer/login with the credentials you entered.');
        return self::SUCCESS;
    }

    private function record(CcsDeveloperAccount $account, string $action): void
    {
        DB::table('ccs_developer_events')->insert([
            'developer_account_id' => $account->id, 'action' => $action,
            'details' => json_encode(['source' => 'server_terminal', 'active' => $account->active, 'can_clear_data' => $account->can_clear_data]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(CcsAdministration::class)->audit(null, $action, 'developer_account', $account->id, ['source' => 'server_terminal']);
    }
}
