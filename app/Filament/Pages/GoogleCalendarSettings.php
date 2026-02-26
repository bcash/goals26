<?php

namespace App\Filament\Pages;

use App\Models\GoogleCalendarConfig;
use App\Services\GoogleCalendarOAuthService;
use App\Services\GoogleCalendarSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GoogleCalendarSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Google Calendar';

    protected static ?string $title = 'Google Calendar Settings';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.google-calendar-settings';

    public bool $isConnected = false;

    public array $calendars = [];

    public function mount(): void
    {
        $oauth = app(GoogleCalendarOAuthService::class);
        $this->isConnected = $oauth->isConnected(auth()->user());

        if ($this->isConnected) {
            $this->loadCalendars();
        }
    }

    public function loadCalendars(): void
    {
        $configs = GoogleCalendarConfig::where('user_id', auth()->id())->get();
        $this->calendars = $configs->map(fn ($c) => [
            'id' => $c->id,
            'google_calendar_id' => $c->google_calendar_id,
            'calendar_name' => $c->calendar_name,
            'sync_enabled' => $c->sync_enabled,
            'only_with_attendees' => $c->only_with_attendees,
            'life_area_id' => $c->life_area_id,
        ])->toArray();
    }

    public function connectAction(): Action
    {
        return Action::make('connect')
            ->label('Connect Google Calendar')
            ->icon('heroicon-o-link')
            ->url(route('google.redirect'))
            ->visible(fn () => ! $this->isConnected);
    }

    public function disconnectAction(): Action
    {
        return Action::make('disconnect')
            ->label('Disconnect')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn () => $this->isConnected)
            ->action(function () {
                app(GoogleCalendarOAuthService::class)->revokeToken(auth()->user());
                $this->isConnected = false;
                $this->calendars = [];
                Notification::make()->title('Google Calendar disconnected')->success()->send();
            });
    }

    public function syncNowAction(): Action
    {
        return Action::make('syncNow')
            ->label('Sync Now')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn () => $this->isConnected)
            ->action(function () {
                $synced = app(GoogleCalendarSyncService::class)->syncEvents(auth()->user());
                Notification::make()
                    ->title("Synced {$synced->count()} events")
                    ->success()
                    ->send();
            });
    }

    public function refreshCalendarsAction(): Action
    {
        return Action::make('refreshCalendars')
            ->label('Refresh Calendar List')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn () => $this->isConnected)
            ->action(function () {
                $service = app(GoogleCalendarSyncService::class);
                $googleCalendars = $service->fetchCalendars(auth()->user());

                foreach ($googleCalendars as $cal) {
                    GoogleCalendarConfig::updateOrCreate(
                        [
                            'user_id' => auth()->id(),
                            'google_calendar_id' => $cal['id'],
                        ],
                        [
                            'calendar_name' => $cal['summary'],
                        ]
                    );
                }

                $this->loadCalendars();
                Notification::make()->title('Calendar list refreshed')->success()->send();
            });
    }

    public function toggleSync(int $configId): void
    {
        $config = GoogleCalendarConfig::findOrFail($configId);
        $config->update(['sync_enabled' => ! $config->sync_enabled]);
        $this->loadCalendars();
    }

    public function toggleAttendeesOnly(int $configId): void
    {
        $config = GoogleCalendarConfig::findOrFail($configId);
        $config->update(['only_with_attendees' => ! $config->only_with_attendees]);
        $this->loadCalendars();
    }
}
