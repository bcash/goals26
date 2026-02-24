<?php

namespace App\Filament\Pages;

use App\Services\VpoService;
use Filament\Pages\Page;

class VpoAccounts extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Clients & Pipeline';

    protected static ?string $navigationLabel = 'VPO Accounts';

    protected static ?string $title = 'VPO Accounts';

    protected static ?int $navigationSort = 15;

    protected static string $view = 'filament.pages.vpo-accounts';

    public string $search = '';

    public array $accounts = [];

    public ?array $selectedAccount = null;

    public array $contacts = [];

    public array $projects = [];

    public array $invoices = [];

    public array $tickets = [];

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
        $this->selectedAccount = null;
        $this->contacts = [];
        $this->projects = [];
        $this->invoices = [];
        $this->tickets = [];
    }

    public function viewAccount(string $accountId): void
    {
        $service = app(VpoService::class);

        $this->selectedAccount = $service->getAccount($accountId);
        $this->contacts = $service->getContacts($accountId);
        $this->projects = $service->getProjects($accountId);
        $this->invoices = $service->getInvoices($accountId);
        $this->tickets = $service->getTickets($accountId);
    }

    public function clearSelection(): void
    {
        $this->selectedAccount = null;
        $this->contacts = [];
        $this->projects = [];
        $this->invoices = [];
        $this->tickets = [];
    }
}
