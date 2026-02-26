<?php

namespace App\Console\Commands;

use App\Models\EmailConversation;
use App\Services\EmailIntelligenceService;
use Illuminate\Console\Command;

class AnalyzeEmails extends Command
{
    protected $signature = 'freescout:analyze
                            {--limit=20 : Maximum conversations to analyze}';

    protected $description = 'Run AI analysis on unprocessed email conversations';

    public function handle(EmailIntelligenceService $intelligence): int
    {
        $limit = (int) $this->option('limit');

        // Find conversations needing analysis
        $conversations = EmailConversation::where('analysis_status', 'pending')
            ->whereHas('threads')
            ->orderBy('last_message_at', 'desc')
            ->limit($limit)
            ->get();

        if ($conversations->isEmpty()) {
            $this->info('No conversations need analysis.');

            return self::SUCCESS;
        }

        $this->info("Analyzing {$conversations->count()} conversations...");

        $success = 0;
        $failed = 0;

        foreach ($conversations as $conversation) {
            try {
                $intelligence->analyzeConversation($conversation);
                $this->line("  ✓ [{$conversation->id}] {$conversation->subject}");
                $success++;
            } catch (\Exception $e) {
                $this->warn("  ✗ [{$conversation->id}] {$conversation->subject}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Analysis complete: {$success} succeeded, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
