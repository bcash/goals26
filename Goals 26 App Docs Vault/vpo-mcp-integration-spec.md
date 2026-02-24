# Virtual Project Office — MCP Integration Spec

> **Version:** 0.2.0
> **Date:** 2026-02-23
> **Status:** Revised after Alpine team review (v0.3.0 response)
> **Audience:** Solas Rún maintainer (this document) + Alpine team (for reference)

---

## 1. Overview

The **Virtual Project Office (VPO)** is an accounts data system that manages live client infrastructure: DigitalOcean servers, Cloudways hosting, Laravel Forge deployments, domain registrars (OpenSRS, GoDaddy), Cloudflare DNS, and client billing via Stripe and FreshBooks. It is a separate Laravel 12 application with PostgreSQL, running on its own infrastructure at `vpo.alp1n3.com`.

**Solas Rún** is a personal operating system for goal tracking, project management, and client meeting intelligence. It currently stores client references as plain strings (`client_name` on Project, DeferredItem, OpportunityPipeline, ClientMeeting, MeetingAgenda). These are disconnected from VPO's live account data.

This spec defines the **Solas Rún integration layer** — the client-side code that consumes the VPO MCP server. The VPO MCP server itself is owned and specified by the Alpine team (see their v0.3.0 spec).

### What Changed from v0.1.0

| v0.1.0 Assumption | v0.2.0 Reality |
|---|---|
| VPO's core entity is "Client" | Core entity is **Account** |
| VPO should copy Solas Rún's generic ModelRegistry architecture | VPO uses **hand-crafted tools** with custom relationship loading |
| 17 speculative models proposed | **65 total models**, 23 exposed in Phases 1-2 |
| 9 write tools proposed for Phase 1 | Write operations **deferred to Phase 5** (live infrastructure risk) |
| Single `Invoice` model | Billing split across **Stripe + FreshBooks** (6+ models) |
| VPO MCP server needs to be built from scratch | VPO MCP server **already exists** — 4 tools + 4 resources live |
| `vpo_client_id` column naming | Corrected to **`vpo_account_id`** |

### Why MCP?

MCP is already the integration backbone of Solas Rún. Using MCP for the VPO connection means:

- Claude Code sessions can query both systems simultaneously
- The VPO server is self-describing (inspect tools expose schema)
- No custom REST API design or versioning needed
- VPO's existing MCP server is already live and tested

---

## 2. System Landscape

```
┌─────────────────────────────────┐     ┌─────────────────────────────────┐
│         SOLAS RUN               │     │         VPO                     │
│   (Personal Operating System)   │     │   (Accounts Data System)        │
│                                 │     │                                 │
│  ┌───────────┐  ┌────────────┐  │     │  ┌────────────┐  ┌──────────┐  │
│  │ solas-run  │  │ VpoService │◄─┼─MCP─┼─►│ vpo-server │  │ 65       │  │
│  │ MCP server │  │ VpoResolver│  │     │  │ MCP server │  │ models   │  │
│  └───────────┘  └────────────┘  │     │  └────────────┘  └──────────┘  │
│                                 │     │                                 │
│  26 models      config/vpo.php  │     │  Accounts, Servers, Domains,   │
│  54 tools       VpoWidget       │     │  Websites, Tasks, Billing,     │
│                                 │     │  DNS, Forge, Cloudways, ...    │
└─────────────────────────────────┘     └─────────────────────────────────┘
                                  MCP/HTTP+SSE
```

**Transport:** HTTP+SSE only. VPO is a remote server at `https://vpo.alp1n3.com/api/mcp/vpo`. STDIO is used for VPO's local Claude Code sessions, not for Solas Rún consumption.

---

## 3. VPO MCP Server (Reference)

The VPO MCP server is owned by the Alpine team. This section summarises their v0.3.0 spec for Solas Rún integration purposes. Do not modify the VPO server — treat its tool signatures as a contract.

### 3.1 Stack

- Laravel 12, PHP 8.4, PostgreSQL, `laravel/mcp` v0
- Single-tenant, admin-only (no tenant scoping)
- Hand-crafted MCP tools (NOT generic ModelRegistry pattern)
- 65 total Eloquent models; 23 exposed in Phases 1-2

### 3.2 Currently Live Tools (4 tools, 4 resources)

| Tool | Parameters | Returns |
|------|-----------|---------|
| `search-accounts` | `search?`, `status?`, `limit?` | Account list with relationships |
| `get-account-details` | `account_id` | Full account with servers, domains, websites, billing |
| `search-tasks` | `search?`, `status?`, `account_id?`, `limit?` | VPO team-level task list |
| `get-task-details` | `task_id` | Full VPO task with comments, assignments |

**Resources:** `account://{id}`, `task://{id}`, plus inspect-account, inspect-task.

### 3.3 Phase 1 Additions (6 tools)

| Tool | Parameters | Returns |
|------|-----------|---------|
| `search-servers` | `search?`, `account_id?`, `provider?`, `status?`, `limit?` | Server list |
| `get-server-details` | `server_id` | Server with provider data, sites, monitoring |
| `search-domains` | `search?`, `account_id?`, `registrar?`, `expiring_within_days?`, `limit?` | Domain list |
| `get-domain-details` | `domain_id` | Domain with DNS, registrar data, linked websites |
| `search-websites` | `search?`, `account_id?`, `server_id?`, `status?`, `limit?` | Website list |
| `get-website-details` | `website_id` | Website with server, domain, deployment config |

### 3.4 Phase 2 Additions (4 tools)

| Tool | Parameters | Returns |
|------|-----------|---------|
| `search-invoices` | `account_id?`, `status?`, `source?`, `limit?` | Normalised invoice list (Stripe + FreshBooks) |
| `get-invoice-details` | `invoice_id`, `source` | Full invoice with line items |
| `get-account-billing-summary` | `account_id` | MRR, outstanding balance, last payment, subscription status |
| `get-infrastructure-summary` | `account_id` | Servers, domains, websites, SSL, DNS status counts |

### 3.5 Conventions (VPO Side)

| Convention | Value |
|-----------|-------|
| Money | Decimal dollars (e.g., `5000.00`) — NOT cents |
| Dates | `YYYY-MM-DD` for dates, ISO 8601 for datetimes |
| Search | Case-insensitive (`ilike` on PostgreSQL) |
| Pagination | `limit` (max 100), response includes `total` |
| IDs | Integer primary keys |
| Statuses | Lowercase strings (`active`, `suspended`, `archived`) |
| Credentials | **Never exposed** via MCP — encrypted with Laravel `encrypted` cast |
| Auth | Bearer token required for HTTP transport |

### 3.6 Future Phases (VPO team roadmap)

- **Phase 3:** 18 additional models (DNS zones, Forge sites, Cloudways apps, SSL, monitoring)
- **Phase 4:** Cross-entity reporting tools (revenue per server, cost per client, etc.)
- **Phase 5:** Guarded write operations (with authorization model, dry-run support, audit logging)
- 24 models permanently internal (migrations, jobs, notifications, etc.)

---

## 4. Solas Rún Integration Layer

### 4.1 New Files to Create

| File | Type | Purpose |
|------|------|---------|
| `config/vpo.php` | Config | VPO connection settings |
| `app/Services/VpoService.php` | Service | MCP client wrapping VPO tool calls |
| `app/Services/VpoResolver.php` | Service | Resolves `client_name` strings to VPO account records |
| `app/Filament/Widgets/VpoStatusWidget.php` | Widget | Dashboard widget for VPO infrastructure summary |
| `app/Filament/Pages/VpoAccounts.php` | Page | Read-only Filament page browsing VPO accounts |
| `database/migrations/xxx_add_vpo_account_id.php` | Migration | Add `vpo_account_id` to 5 tables |

### 4.2 Configuration

```php
// config/vpo.php
return [
    'enabled'  => env('VPO_ENABLED', false),
    'url'      => env('VPO_MCP_URL', 'https://vpo.alp1n3.com/api/mcp/vpo'),
    'api_key'  => env('VPO_API_KEY'),
    'timeout'  => env('VPO_TIMEOUT', 30),
    'cache_ttl' => env('VPO_CACHE_TTL', 300), // 5 minutes
];
```

### 4.3 VpoService

Central service for all VPO MCP calls. Methods map directly to VPO tool names. All return arrays (decoded MCP JSON responses). Connection failures are handled gracefully — methods return empty arrays or `null`, never throw to callers.

```php
namespace App\Services;

class VpoService
{
    // ── Account Operations ──────────────────────────────────────
    public function searchAccounts(?string $search = null, ?string $status = null, int $limit = 25): array;
    public function getAccountDetails(int $accountId): ?array;

    // ── Infrastructure Queries (Phase 1) ────────────────────────
    public function searchServers(?int $accountId = null, ?string $provider = null, ?string $status = null, int $limit = 25): array;
    public function getServerDetails(int $serverId): ?array;
    public function searchDomains(?int $accountId = null, ?string $registrar = null, ?int $expiringWithinDays = null, int $limit = 25): array;
    public function getDomainDetails(int $domainId): ?array;
    public function searchWebsites(?int $accountId = null, ?int $serverId = null, ?string $status = null, int $limit = 25): array;
    public function getWebsiteDetails(int $websiteId): ?array;

    // ── VPO Tasks ───────────────────────────────────────────────
    public function searchTasks(?int $accountId = null, ?string $status = null, ?string $search = null, int $limit = 25): array;
    public function getTaskDetails(int $taskId): ?array;

    // ── Financial Queries (Phase 2) ─────────────────────────────
    public function searchInvoices(?int $accountId = null, ?string $status = null, ?string $source = null, int $limit = 25): array;
    public function getInvoiceDetails(int $invoiceId, string $source): ?array;
    public function getAccountBillingSummary(int $accountId): ?array;
    public function getInfrastructureSummary(int $accountId): ?array;

    // ── Connection ──────────────────────────────────────────────
    public function ping(): bool;
    public function isEnabled(): bool;

    // ── Generic Pass-Through (for future tools) ─────────────────
    public function callTool(string $tool, array $params = []): array;
}
```

### 4.4 VpoResolver

Bridges the current string-based `client_name` fields to real VPO account records.

```php
namespace App\Services;

class VpoResolver
{
    /**
     * Resolve a client_name string to a VPO account record.
     * Uses searchAccounts with the name as search term.
     */
    public function resolve(string $clientName): ?array;

    /**
     * Batch-resolve multiple client names (for list views).
     * Returns a map of client_name => vpo_account_id (or null).
     * Uses in-memory cache to avoid duplicate lookups.
     */
    public function resolveMany(array $clientNames): array;

    /**
     * Link a Solas Rún model to a VPO account by setting vpo_account_id.
     * Works for Project, DeferredItem, OpportunityPipeline, ClientMeeting, MeetingAgenda.
     */
    public function link(Model $model, int $vpoAccountId): void;

    /**
     * Get full VPO account data for a linked model.
     * Cached per request to avoid repeat MCP calls.
     */
    public function getLinkedAccount(Model $model): ?array;

    /**
     * Get infrastructure summary for a linked account.
     * Returns servers, domains, websites, and their statuses.
     */
    public function getInfrastructureSummary(int $vpoAccountId): ?array;
}
```

### 4.5 Database Migration

Single migration adding nullable `vpo_account_id` to all models that reference clients:

```php
// database/migrations/xxx_add_vpo_account_id.php

// projects table
$table->unsignedBigInteger('vpo_account_id')->nullable()->after('client_name');
$table->index('vpo_account_id');

// deferred_items table
$table->unsignedBigInteger('vpo_account_id')->nullable()->after('client_name');

// opportunity_pipeline table
$table->unsignedBigInteger('vpo_account_id')->nullable()->after('client_name');

// client_meetings table
$table->unsignedBigInteger('vpo_account_id')->nullable()->after('client_type');

// meeting_agendas table
$table->unsignedBigInteger('vpo_account_id')->nullable()->after('client_name');
```

This is NOT a foreign key constraint — VPO is a separate database. It is a soft reference resolved at runtime via VpoService.

### 4.6 Model Updates

Each model that gains `vpo_account_id` gets:

```php
// Add to $fillable
'vpo_account_id',

// Add accessor with per-request caching
public function vpoAccount(): ?array
{
    if (! $this->vpo_account_id || ! app(VpoService::class)->isEnabled()) {
        return null;
    }

    return once(fn () => app(VpoService::class)->getAccountDetails($this->vpo_account_id));
}
```

**Models affected:** Project, DeferredItem, OpportunityPipeline, ClientMeeting, MeetingAgenda.

### 4.7 Dashboard Widget

```
┌──────────────────────────────────────────────────┐
│ VPO Status                               [Refresh]│
├──────────────────────────────────────────────────┤
│ Accounts: 12 active  │  Servers: 18 running       │
│ Domains: 24 managed  │  Websites: 31 deployed      │
│ MRR: $4,200          │  Overdue: 3 invoices        │
│                                                    │
│ Expiring Domains:                                  │
│  - example.com → expires Mar 15                    │
│  - clientsite.io → expires Apr 02                  │
│                                                    │
│ Overdue Invoices:                                  │
│  - Acme Corp — $2,400 (14 days overdue)           │
│  - Beta LLC — $800 (7 days overdue)               │
└──────────────────────────────────────────────────┘
```

Widget data fetched via VpoService with cache (`vpo.cache_ttl`, default 5 minutes). Shows "VPO Offline" gracefully when connection fails. Only renders when `config('vpo.enabled')` is true.

### 4.8 Filament Page: VPO Accounts

A read-only Filament page (not a full resource — data lives externally) that:

- Lists all VPO accounts with status, server count, domain count, website count
- Shows account detail with infrastructure summary (servers, domains, websites)
- Provides a "Link to Project" action that sets `vpo_account_id` on a Solas Rún project
- Shows linked Solas Rún projects, opportunities, and meetings for each account
- Displays billing summary (MRR, outstanding invoices, subscription status) once Phase 2 tools are available

### 4.9 Filament Resource Enhancements

Existing resources that show `client_name` gain VPO integration:

**ProjectResource:**
- Add `vpo_account_id` Select field (populated via VpoService::searchAccounts)
- Show infrastructure summary infolist when a VPO account is linked
- Display a "VPO" badge on projects with linked accounts

**DeferredItemResource:**
- Show VPO account data in the detail view when `vpo_account_id` is set
- Auto-suggest VPO account link based on `client_name` search

**OpportunityPipelineResource:**
- Show billing summary from VPO alongside opportunity data

**ClientMeetingResource:**
- Show linked account infrastructure context for meeting preparation

### 4.10 MCP Server Registration

Register VPO as a second MCP server in Claude Code's configuration:

```json
{
  "mcpServers": {
    "solas-run": {
      "command": "php",
      "args": ["artisan", "mcp:start", "solas-run"],
      "cwd": "/Users/briancash/Herd/goals26"
    },
    "vpo": {
      "type": "http",
      "url": "https://vpo.alp1n3.com/api/mcp/vpo",
      "headers": {
        "Authorization": "Bearer ${VPO_API_KEY}"
      }
    }
  }
}
```

This gives Claude Code direct access to both `solas-run` and `vpo` tool namespaces simultaneously.

---

## 5. Data Flow Patterns

### 5.1 Account Resolution Flow

```
User creates Project in Solas Rún
  → sets client_name = "Acme Corp"
  → VpoResolver::resolve("Acme Corp")
    → VpoService::searchAccounts(search: "Acme Corp")
      → MCP call: search-accounts { search: "Acme Corp" }
    → Returns match: { id: 42, name: "Acme Corporation", status: "active" }
  → User confirms link
  → Project.vpo_account_id = 42
```

### 5.2 Meeting Intelligence + VPO Context

```
ClientMeeting transcript analyzed
  → MeetingScopeItem extracted (out-of-scope: "client needs a new staging server")
  → DeferredItem created with client_name from meeting
  → VpoResolver auto-links to VPO account
  → VpoService::getInfrastructureSummary(42) enriches the deferred item
    → "Account currently has 2 servers (prod + dev), 3 websites, no staging"
  → AI opportunity analysis includes real infrastructure context
```

### 5.3 Dashboard Aggregation

```
Dashboard loads
  → DayThemeWidget, MorningChecklist, etc. (Solas Rún data, instant)
  → VpoStatusWidget (VPO data, cached 5 min)
    → VpoService::searchAccounts(status: "active") → count
    → VpoService::searchServers() → count + status
    → VpoService::searchDomains(expiringWithinDays: 30) → expiring list
    → VpoService::searchWebsites() → count
    → VpoService::searchInvoices(status: "overdue") → amounts (Phase 2)
```

### 5.4 Claude Code Cross-System Query

```
User: "What servers does Acme Corp have and are there any outstanding tasks for them?"

Claude Code:
  → vpo: search-servers { search: "Acme" }         // VPO MCP
  → vpo: search-tasks { search: "Acme" }            // VPO team tasks
  → solas-run: list-project { search: "Acme" }      // Solas Rún MCP
  → solas-run: list-task { search: "Acme" }          // Solas Rún personal tasks
  → Synthesizes answer from both systems
```

---

## 6. Conventions (Shared)

Both systems must agree on these conventions for clean integration:

| Convention | Solas Rún | VPO | Notes |
|-----------|-----------|-----|-------|
| Money | Integer cents (new) + decimal dollars (legacy) | Decimal dollars | VPO data displayed as-is; no conversion needed |
| Dates | `YYYY-MM-DD`, Carbon | `YYYY-MM-DD`, Carbon | Aligned |
| Search | `ilike` (PostgreSQL) | `ilike` (PostgreSQL) | Aligned |
| Pagination | `page` + `per_page` (max 100) | `limit` (max 100) + `total` | VpoService uses `limit` when calling VPO |
| IDs | Integer auto-increment | Integer auto-increment | Aligned |
| Statuses | Lowercase strings | Lowercase strings | Aligned |
| Soft references | `vpo_account_id` — NOT a foreign key | N/A | Runtime-resolved via VpoService |
| Error handling | MCP standard errors | MCP standard errors | VpoService wraps and handles gracefully |
| Null handling | `null`, not empty strings | `null`, not empty strings | Aligned |

---

## 7. Phase Plan (Solas Rún Side)

### Phase 1: Client Layer (after VPO Phase 1 tools ship)

- Create `config/vpo.php` configuration
- Build `VpoService` — account + infrastructure tool wrappers
- Build `VpoResolver` for account name resolution
- Add `vpo_account_id` migration (5 tables)
- Update 5 models with new field + `vpoAccount()` accessor
- Write tests (VpoService with mocked MCP responses, VpoResolver)

### Phase 2: Filament Integration

- Build `VpoStatusWidget` for dashboard
- Build `VpoAccounts` read-only Filament page
- Enhance ProjectResource with VPO account selector
- Enhance DeferredItemResource with VPO context
- Add VPO badges and infrastructure summaries

### Phase 3: Financial Integration (after VPO Phase 2 tools ship)

- Add billing methods to VpoService (invoices, billing summary)
- Update VpoStatusWidget with MRR and invoice data
- Enhance OpportunityPipelineResource with billing context
- Add VpoAccounts page billing summary tab

### Phase 4: Claude Code Multi-System

- Register VPO as second MCP server in `.claude/mcp_servers.json`
- Test cross-system queries
- Add Claude Code hooks for VPO context injection
- Document common cross-system query patterns

### Phase 5: Write Operations (after VPO Phase 5)

- Add write method wrappers to VpoService
- Build Filament actions for guarded write operations
- Implement confirmation flows for destructive actions
- Write operation audit logging on Solas Rún side

---

## 8. Risks

| Risk | Mitigation |
|------|------------|
| VPO server downtime | VpoService returns empty/null gracefully; VPO widget shows "Offline"; Solas Rún remains fully functional |
| Network latency on dashboard | Cache VPO responses (`vpo.cache_ttl`, default 5 minutes); show stale indicator |
| Account name mismatch | VpoResolver uses `searchAccounts` with name as search term; manual linking via `vpo_account_id` is fallback |
| VPO tool signature changes | Pin to known tool versions; VpoService wraps calls so changes are isolated to one file |
| VPO "Task" vs Solas Rún "Task" confusion | VPO tasks are team-level work items (assigned, commented); Solas Rún tasks are personal hierarchical tree nodes. Claude Code context should clarify which system's tasks are meant |
| Money convention mismatch | VPO uses decimal dollars; Solas Rún new fields use integer cents. VPO data is displayed as-is (no conversion) since it stays as read-only VPO data |
| Billing data split across Stripe + FreshBooks | VPO's `get-account-billing-summary` normalises both; Solas Rún consumes the normalised response |

---

## 9. Resolved Questions (from v0.1.0)

All 10 original open questions are now answered:

| # | Question | Answer |
|---|----------|--------|
| 1 | VPO's Laravel version? | **Laravel 12**, PHP 8.4 |
| 2 | PostgreSQL or MySQL? | **PostgreSQL** (same as Solas Rún) |
| 3 | Single-tenant or multi-tenant? | **Single-tenant**, admin-only |
| 4 | Credential vault? | Laravel `encrypted` cast + `IntegrationSettingsService` |
| 5 | Existing API? | **The MCP server IS the API** — live at `https://vpo.alp1n3.com/api/mcp/vpo` |
| 6 | DigitalOcean integration? | DigitalOcean + **Cloudways** + **Laravel Forge** |
| 7 | Domain registrars? | **OpenSRS** + **GoDaddy**; DNS via **Cloudflare** |
| 8 | Real-time monitoring? | External **Alpine Uptime** service; not exposed via MCP yet |
| 9 | Credential rotation auth? | **Deferred to Phase 5** — requires authorization model spec |
| 10 | Expected model count? | **65 total** — 23 exposed Phases 1-2, 18 eligible future, 24 permanently internal |

---

## Appendix A: Current Client References in Solas Rún

These are the existing string-based client fields that the VPO integration enriches:

| Model | Field | Current Type | VPO Enhancement |
|-------|-------|-------------|-----------------|
| Project | `client_name` | string | Resolve to VPO account; show infrastructure |
| DeferredItem | `client_name` | string | Resolve to VPO account; show billing context |
| DeferredItem | `client_type` | string | Cross-reference with VPO account status |
| OpportunityPipeline | `client_name` | string | Resolve to VPO account; show billing history |
| OpportunityPipeline | `client_email` | string | Validate against VPO account contacts |
| ClientMeeting | `client_type` | string | Resolve to VPO account when external meeting |
| MeetingAgenda | `client_name` | string | Resolve to VPO account for agenda context |
| MeetingAgenda | `client_type` | string | Cross-reference with VPO account records |

All `client_name` string fields remain unchanged — `vpo_account_id` is added alongside as an optional link. The string field serves as the human-readable label; the ID provides the machine-resolvable reference to VPO.

---

## Appendix B: VPO Model Inventory (from Alpine v0.3.0)

### Phase 1 — Core (6 models exposed)

Account, Server, Domain, Website, VpoTask, VpoTaskComment

### Phase 2 — Financial + Infrastructure (17 additional models)

StripeCustomer, StripeSubscription, StripeInvoice, StripeInvoiceItem, StripePayment, StripeRefund, FreshbooksClient, FreshbooksInvoice, FreshbooksPayment, DnsZone, DnsRecord, CloudwaysServer, CloudwaysApp, ForgeSite, ForgeCertificate, MonitoringEndpoint, MonitoringIncident

### Phase 3+ — Eligible Future (18 models)

ServerMetric, DomainTransfer, SslCertificate, BackupSchedule, MaintenanceWindow, ClientNote, IntegrationLog, WebhookEndpoint, + 10 more

### Permanently Internal (24 models)

User, Role, Permission, Migration, Job, FailedJob, Notification, PersonalAccessToken, ActivityLog, Setting, IntegrationSetting, + 13 more
