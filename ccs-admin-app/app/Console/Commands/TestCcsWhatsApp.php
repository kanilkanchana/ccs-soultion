<?php

namespace App\Console\Commands;

use App\Services\CcsWhatsAppTest;
use Illuminate\Console\Command;
use RuntimeException;

class TestCcsWhatsApp extends Command
{
    protected $signature = 'ccs:whatsapp-test {--send : Explicitly send the sandbox sample after confirmation}';

    protected $description = 'Preview or send one Meta sandbox WhatsApp sample to the configured test recipient';

    public function handle(CcsWhatsAppTest $whatsapp): int
    {
        try {
            $preview = $whatsapp->preview();
            $this->table(['Setting', 'Value'], collect($preview)->map(fn ($value, $key) => [$key, $value])->values()->all());
            if (!$this->option('send')) {
                $this->info('Preview only: no API calls made and no message sent. Use --send for an explicit test.');

                return self::SUCCESS;
            }
            if (!$this->confirm('Send ONE hello_world sample using the Meta sandbox sender to this configured test recipient?', false)) {
                $this->info('Cancelled. No message sent.');

                return self::SUCCESS;
            }

            $id = $whatsapp->send();
            $this->info('Meta accepted the test request. Message ID: '.$id);
            $this->warn('Accepted is not delivered. Check the recipient WhatsApp app or Meta test webhook status.');
            $this->line('No customer records, logins, invoices, jobs or warranties were changed.');

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
