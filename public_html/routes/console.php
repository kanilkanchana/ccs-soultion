<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ccs:restore {id : Validated restore request ID}', function () {
    $id=(string)$this->argument('id');$backups=app(\App\Services\CcsSqliteBackup::class);
    try {
        if(!app()->isDownForMaintenance()){$this->error('First stop workers/other Artisan writes, then run php artisan down.');return 1;}
        $job=$backups->job($id);
        if($job['state']!=='ready'||$job['expires_at']<time()){$this->error('Restore request expired or already used. Prepare a new request in Settings.');return 1;}
        $this->warn('This replaces current business data with backup '.$job['backup_id'].' from '.$job['backup_date'].'.');
        $this->line('A safety backup will be created first. Stop all queue workers and other database-writing commands.');
        if($this->ask('Type RESTORE to confirm')!=='RESTORE'){$this->info('Cancelled. Database unchanged. Site remains in maintenance mode.');return 1;}
        $result=$backups->restore($id);
        $this->info('Restore completed and checked. Safety backup: '.$result['safety_backup_id']);
        $this->line('Run php artisan optimize:clear then php artisan up. Sign in using the owner credentials stored in the restored backup.');
        return 0;
    } catch (\Throwable $e) {
        report($e);$this->error($e->getMessage());$this->warn('Site remains in maintenance mode. Do not bring it up until recovery is verified.');return 1;
    }
})->purpose('Restore a validated CCS SQLite backup during maintenance, with a safety snapshot');
