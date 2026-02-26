# High-Value VPO Integrations for Solas Run

Ranked by impact. Each integration builds on the corrected VPO client layer (14 tools, integer IDs, proper response parsing).

---

## 1. Pre-Meeting Client Brief (highest value)

Before a client meeting, auto-pull from VPO: billing summary (MRR, outstanding balance), open tasks, infrastructure status, expiring domains. Inject this into the meeting agenda as context. `MeetingAgenda` already has `HasVpoAccount` — this is a natural fit.

**VPO tools used:** `get-account-billing-summary`, `search-tasks`, `get-infrastructure-summary`, `search-domains`

**Integration points:**
- `AgendaService` — enrich agenda generation with VPO context
- `MeetingAgenda` model — store cached VPO snapshot at generation time
- `ClientMeeting` resource — show VPO brief in the view page

---

## 2. Project Dashboard Enrichment

When a Project has a `vpo_account_id`, show inline: infrastructure costs, server count, domain count, active VPO tasks, billing summary. This gives a single-pane view of what a client costs vs. what they pay.

**VPO tools used:** `get-infrastructure-summary`, `get-account-billing-summary`, `search-tasks`

**Integration points:**
- `ViewProject` page — new VPO section with live data
- `ProjectResource` — VPO account badge in table column
- New `VpoProjectContextWidget` on project view

---

## 3. Opportunity Pipeline x Billing Context

When evaluating whether to pursue a deferred item or opportunity, seeing the client's MRR, payment history, and outstanding balance helps price and prioritize. A "client health" indicator on the pipeline.

**VPO tools used:** `get-account-billing-summary`, `get-account-details`

**Integration points:**
- `OpportunityPipelineResource` — inline billing summary when `vpo_account_id` is set
- `OpportunityPipelineWidget` — color-code by client health (MRR tier, payment status)
- `DeferredItemResource` — show billing context during review

---

## 4. Domain Expiration Alerts on Dashboard

Replace or enhance the `VpoStatusWidget` with a "domains expiring in 30 days" alert. The `search-domains` tool has an `expiring_within_days` parameter built for exactly this.

**VPO tools used:** `search-domains` (with `expiring_within_days: 30`)

**Integration points:**
- `VpoStatusWidget` — replace generic account list with expiring domains alert
- Dashboard — high-visibility warning for domains needing renewal

---

## 5. Auto-Link Projects to VPO Accounts

Replace the plain `TextInput` for `vpo_account_id` with a searchable Select that queries `search-accounts` live. When linked, auto-fill `client_name` from VPO.

**VPO tools used:** `search-accounts`

**Integration points:**
- `ProjectResource` form — async VPO account search Select
- `ProjectResource` — auto-populate `client_name` on link
- Any model with `HasVpoAccount` — reusable VPO account picker component

---

## 6. Infrastructure Cost Tracking

Pull `get-infrastructure-summary` per account into project budget tracking. Compare actual VPO infrastructure costs against the project budget.

**VPO tools used:** `get-infrastructure-summary`, `get-account-billing-summary`

**Integration points:**
- `BudgetService` — compare VPO infra costs vs. project budget
- `CostEntriesRelationManager` — optional VPO cost import
- `ProjectBudget` view — VPO cost overlay

---

## Prerequisites: VPO Client Bug Fixes

Before implementing any of the above, the VPO client layer needs these fixes:

1. **`.env` URL** — Change `VPO_MCP_URL` from `https://vpo.alp1n3.com` to `https://vpo.alp1n3.com/api/mcp/vpo`
2. **VpoService tool names** — Replace 4 non-existent tools (`list-account-contacts`, `list-account-projects`, `list-account-invoices`, `list-account-tickets`) with real VPO tools
3. **Response parsing** — `search-accounts` returns `{query, count, accounts: [...]}` — extract the `accounts` array
4. **Account ID types** — VPO uses integer IDs, not strings
5. **Invoice cents conversion** — VPO returns amounts in cents; divide by 100 for display
6. **Expose all 14 tools** — Add methods for servers, domains, websites, tasks, billing summary, infrastructure summary
