<?php

namespace App\Filament\Widgets;

use App\Services\VpoService;

class VpoStatusWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 1;

    protected static string $view = 'filament.widgets.vpo-status-widget';

    public static function canView(): bool
    {
        return app(VpoService::class)->isAvailable();
    }

    public function getViewData(): array
    {
        $service = app(VpoService::class);

        $accounts = [];
        $connected = false;

        try {
            $accounts = $service->searchAccounts('', 5);
            $connected = true;
        } catch (\Throwable) {
            $connected = false;
        }

        return [
            'connected' => $connected,
            'accounts' => $accounts,
            'accountCount' => count($accounts),
        ];
    }
}
