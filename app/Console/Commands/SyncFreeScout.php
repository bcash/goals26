<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FreeScoutSyncService;
use Illuminate\Console\Command;

class SyncFreeScout extends Command
{
    protected $signature = 'freescout:sync
                            {--days=7 : Number of days back to sync conversations}
                            {--mailboxes : Only sync mailboxes}
                            {--contacts : Only sync contacts}
                            {--conversations : Only sync conversations}
                            {--analyze : Run AI analysis on new conversations (slow)}
                            {--all : Sync everything (default)}';

    protected $description = 'Sync conversations, contacts, and mailboxes from FreeScout';

    public function handle(FreeScoutSyncService $sync): int
    {
        if (! config('services.freescout.api_key')) {
            $this->error('FreeScout API key not configured. Set FREESCOUT_API_KEY in .env');

            return self::FAILURE;
        }

        $user = User::first();

        if (! $user) {
            $this->error('No user found to sync for.');

            return self::FAILURE;
        }

        $syncAll = $this->option('all')
            || (! $this->option('mailboxes') && ! $this->option('contacts') && ! $this->option('conversations'));

        $days = (int) $this->option('days');
        $analyze = (bool) $this->option('analyze');

        if ($syncAll || $this->option('mailboxes')) {
            $this->info('Syncing mailboxes...');
            $mailboxes = $sync->syncMailboxes($user);
            $this->info("  → {$mailboxes->count()} mailboxes synced");
        }

        if ($syncAll || $this->option('contacts')) {
            $this->info('Syncing contacts...');
            $count = $sync->syncContacts($user);
            $this->info("  → {$count} contacts synced");
        }

        if ($syncAll || $this->option('conversations')) {
            $this->info("Syncing conversations (last {$days} days)...");

            $count = $sync->syncConversations(
                $user,
                $days,
                $analyze,
                fn (int $n, string $subject) => $this->output->write("\r  → {$n} conversations synced")
            );

            // Clear the \r line and print final count
            $this->newLine();
            $this->info("  → {$count} conversations synced");

            if (! $analyze) {
                $this->comment('  Tip: Run `php artisan freescout:analyze` to process AI analysis separately');
            }
        }

        $this->info('FreeScout sync complete.');

        return self::SUCCESS;
    }
}
