<?php

namespace App\Filament\Pages;

use App\Services\VpoService;
use Filament\Pages\Page;

class VpoAccounts extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'Clients & Pipeline';

    protected static ?string $navigationLabel = 'VPO Accounts';

    protected static ?string $title = 'VPO Accounts';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.vpo-accounts';

    public string $search = '';

    public array $accounts = [];

    public ?array $selectedAccount = null;

    public array $tasks = [];

    public array $invoices = [];

    public static function shouldRegisterNavigation(): bool
    {
        return app(VpoService::class)->isAvailable();
    }

    public function mount(): void
    {
        $service = app(VpoService::class);

        if ($service->isAvailable()) {
            $this->accounts = $service->searchAccounts('', 25);
        }
    }

    public function updatedSearch(): void
    {
        $service = app(VpoService::class);
        $this->accounts = $service->searchAccounts($this->search, 25);
        $this->clearSelection();
    }

    public function viewAccount(string $accountId): void
    {
        $service = app(VpoService::class);

        $this->selectedAccount = $service->getAccount($accountId);
        $this->tasks = $service->getTasks($accountId);
        $this->invoices = $service->getInvoices($accountId);
    }

    public function clearSelection(): void
    {
        $this->selectedAccount = null;
        $this->tasks = [];
        $this->invoices = [];
    }
}
