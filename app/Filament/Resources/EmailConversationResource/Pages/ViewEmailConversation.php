<?php

namespace App\Filament\Resources\EmailConversationResource\Pages;

use App\Filament\Resources\EmailConversationResource;
use App\Models\EmailContact;
use App\Models\EmailThread;
use App\Services\FreeScoutApiClient;
use App\Services\FreeScoutSyncService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Collection;

class ViewEmailConversation extends ViewRecord
{
    protected static string $resource = EmailConversationResource::class;

    protected string $view = 'filament.resources.email-conversation-resource.pages.view-email-conversation';

    public string $replyBody = '';

    public string $replyStatus = '';

    public bool $showNoteForm = false;

    public string $noteBody = '';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->replyStatus = $this->record->status ?? 'active';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    /**
     * @return Collection<int, EmailThread>
     */
    public function getThreads(): Collection
    {
        return $this->record->threads()
            ->orderBy('message_at', 'desc')
            ->get();
    }

    public function getContact(): ?EmailContact
    {
        return $this->record->contact;
    }

    public function sendReply(): void
    {
        $body = trim($this->replyBody);

        if ($body === '') {
            Notification::make()
                ->title('Reply body cannot be empty')
                ->danger()
                ->send();

            return;
        }

        $freescoutId = $this->record->freescout_conversation_id;

        if (! $freescoutId) {
            Notification::make()
                ->title('No FreeScout conversation linked')
                ->danger()
                ->send();

            return;
        }

        $apiClient = app(FreeScoutApiClient::class);

        // Send the reply
        $result = $apiClient->createReply($freescoutId, $body);

        if (empty($result)) {
            Notification::make()
                ->title('Failed to send reply')
                ->body('Check the FreeScout API connection.')
                ->danger()
                ->send();

            return;
        }

        // Update status if changed
        if ($this->replyStatus !== $this->record->status) {
            $apiClient->updateConversation($freescoutId, [
                'status' => $this->replyStatus,
            ]);
        }

        // Re-sync the conversation to pull in the new thread
        $this->resyncConversation();

        $this->replyBody = '';

        Notification::make()
            ->title('Reply sent')
            ->success()
            ->send();
    }

    public function addNote(): void
    {
        $body = trim($this->noteBody);

        if ($body === '') {
            Notification::make()
                ->title('Note body cannot be empty')
                ->danger()
                ->send();

            return;
        }

        $freescoutId = $this->record->freescout_conversation_id;

        if (! $freescoutId) {
            Notification::make()
                ->title('No FreeScout conversation linked')
                ->danger()
                ->send();

            return;
        }

        $result = app(FreeScoutApiClient::class)->createNote($freescoutId, $body);

        if (empty($result)) {
            Notification::make()
                ->title('Failed to add note')
                ->danger()
                ->send();

            return;
        }

        $this->resyncConversation();

        $this->noteBody = '';
        $this->showNoteForm = false;

        Notification::make()
            ->title('Note added')
            ->success()
            ->send();
    }

    public function toggleNoteForm(): void
    {
        $this->showNoteForm = ! $this->showNoteForm;
    }

    /**
     * Re-sync the conversation from FreeScout to pick up new threads.
     */
    private function resyncConversation(): void
    {
        $apiClient = app(FreeScoutApiClient::class);
        $syncService = app(FreeScoutSyncService::class);

        $data = $apiClient->getConversation($this->record->freescout_conversation_id);

        if (! empty($data)) {
            $syncService->importConversation(auth()->user(), $data);
            $this->record->refresh();
        }
    }
}
