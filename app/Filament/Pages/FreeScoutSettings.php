<?php

namespace App\Filament\Pages;

use App\Models\FreeScoutMailbox;
use App\Services\FreeScoutApiClient;
use App\Services\FreeScoutSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class FreeScoutSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'FreeScout';

    protected static ?string $title = 'FreeScout Settings';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.freescout-settings';

    public bool $connected = false;

    public array $mailboxes = [];

    public function mount(): void
    {
        $this->connected = app(FreeScoutApiClient::class)->testConnection();

        if ($this->connected) {
            $this->loadMailboxes();
        }
    }

    public function loadMailboxes(): void
    {
        $this->mailboxes = FreeScoutMailbox::where('user_id', auth()->id())
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'sync_enabled' => $m->sync_enabled,
                'last_synced_at' => $m->last_synced_at?->diffForHumans(),
            ])
            ->toArray();
    }

    public function syncMailboxesAction(): Action
    {
        return Action::make('syncMailboxes')
            ->label('Sync Mailboxes')
            ->icon('heroicon-o-inbox-stack')
            ->visible(fn () => $this->connected)
            ->action(function () {
                $synced = app(FreeScoutSyncService::class)->syncMailboxes(auth()->user());
                $this->loadMailboxes();

                Notification::make()
                    ->title("Synced {$synced->count()} mailbox(es)")
                    ->success()
                    ->send();
            });
    }

    public function syncNowAction(): Action
    {
        return Action::make('syncNow')
            ->label('Sync Now')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn () => $this->connected)
            ->action(function () {
                $count = app(FreeScoutSyncService::class)->syncConversations(auth()->user());

                Notification::make()
                    ->title("Synced {$count} conversation(s)")
                    ->success()
                    ->send();
            });
    }

    public function toggleSync(int $mailboxId): void
    {
        $mailbox = FreeScoutMailbox::findOrFail($mailboxId);
        $mailbox->update(['sync_enabled' => ! $mailbox->sync_enabled]);
        $this->loadMailboxes();
    }
}
