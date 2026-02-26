<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EmailConversationResource;
use App\Models\EmailConversation;
use App\Models\FreeScoutMailbox;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Mailboxes extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 29;

    protected string $view = 'filament.pages.mailboxes';

    protected static ?string $title = 'Mailboxes';

    /** @var array<int, array{id: int, name: string, email: string, freescout_mailbox_id: int, last_synced_at: ?string, folders: array}> */
    public array $mailboxes = [];

    public function mount(): void
    {
        $userId = auth()->id();
        $userEmail = auth()->user()->email;

        $rawMailboxes = FreeScoutMailbox::query()
            ->where('user_id', $userId)
            ->where('sync_enabled', true)
            ->orderBy('name')
            ->get();

        if ($rawMailboxes->isEmpty()) {
            return;
        }

        // Get folder counts for all mailboxes in a single query
        $counts = EmailConversation::query()
            ->where('user_id', $userId)
            ->whereIn('freescout_mailbox_id', $rawMailboxes->pluck('freescout_mailbox_id'))
            ->groupBy('freescout_mailbox_id')
            ->select([
                'freescout_mailbox_id',
                DB::raw('count(*) as total'),
                DB::raw("count(*) filter (where status = 'active' and assigned_to_name is null) as unassigned"),
                DB::raw("count(*) filter (where status = 'active' and assigned_to_email = ".DB::connection()->getPdo()->quote($userEmail).') as mine'),
                DB::raw("count(*) filter (where status = 'active' and assigned_to_name is not null) as assigned"),
                DB::raw("count(*) filter (where status = 'closed') as closed"),
                DB::raw("count(*) filter (where status = 'spam') as spam"),
                DB::raw("max(last_message_at) filter (where status = 'active' and assigned_to_email = ".DB::connection()->getPdo()->quote($userEmail).') as mine_latest'),
            ])
            ->get()
            ->keyBy('freescout_mailbox_id');

        $this->mailboxes = $rawMailboxes->map(function (FreeScoutMailbox $mailbox) use ($counts) {
            $stats = $counts->get($mailbox->freescout_mailbox_id);

            return [
                'id' => $mailbox->id,
                'name' => $mailbox->name,
                'email' => $mailbox->email,
                'freescout_mailbox_id' => $mailbox->freescout_mailbox_id,
                'last_synced_at' => $mailbox->last_synced_at?->diffForHumans(),
                'folders' => [
                    'unassigned' => $stats->unassigned ?? 0,
                    'mine' => $stats->mine ?? 0,
                    'assigned' => $stats->assigned ?? 0,
                    'closed' => $stats->closed ?? 0,
                    'spam' => $stats->spam ?? 0,
                ],
                'total' => $stats->total ?? 0,
                'mine_latest' => $stats->mine_latest
                    ? \Carbon\Carbon::parse($stats->mine_latest)->diffForHumans()
                    : null,
            ];
        })->toArray();
    }

    /**
     * Generate the URL to the conversation list pre-filtered by mailbox.
     */
    public function getMailboxListUrl(int $freescoutMailboxId): string
    {
        return EmailConversationResource::getUrl('index', [
            'tableFilters' => [
                'freescout_mailbox_id' => ['value' => $freescoutMailboxId],
            ],
        ]);
    }
}
