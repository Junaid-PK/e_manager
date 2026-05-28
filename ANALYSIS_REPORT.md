# Excel vs Web App Analysis Report
## MON2014 Construcciones SL — Worker & Project Management Module

---

## 1. EXECUTIVE SUMMARY

Your Excel workbook uses a **bottom-up data flow**: you enter individual worker details per project (hours, days, rate, SS) and everything aggregates upward. Your web app currently uses a **top-down manual entry** approach where you enter pre-calculated totals into summary tables. This is fundamentally backwards and creates double work, data inconsistency, and misses the granular tracking that makes your Excel system work.

**Critical Finding**: The `worker_project_entries` table exists in your database but has **zero UI, no routes, no Livewire page, and an empty model**. This is the core of your Excel workflow (the MON83 sheet) and it's completely missing from the web app.

---

## 2. HOW YOUR EXCEL WORKS (3-Layer Architecture)

### Layer 1: Project Detail Sheets (e.g., MON83)
- **One sheet per project per period**
- Columns: NOMBRE | DNI/NIE | NOTA ESPECIAL | SS | HRS | DIAS | PRECIO | TOTAL | PAGADO
- Each worker has a row with individual calculations
- **TOTAL = SS + (HRS × PRECIO)**
- Summary footer: TOTAL NOMINAL, TOTAL SEGURO, TOTAL COMPRA, FACTURAS, RESTO

### Layer 2: Resumen (Worker Monthly Summary)
- **One sheet per period** aggregating ALL workers across ALL projects
- Columns: NOMBRE | NIE | CUENTA BANCARIA | TOTAL | PAGADO | HRS | NOMINA | ANTICIPO/CREDITO | BILLETE | DIFERENCIA | MES | DIFERENCIA FINAL
- Top summary bar: TOTAL HRS | TRABAJADORES | PAGADO | RESTO DE PAGAR
- Shows which workers are paid (checkmarks), who owes money (negative = red)

### Layer 3: TOTAL (Company Overview)
- **Master summary** of all clients/projects for the period
- Columns: MON Code | CLIENTE | ID DE OBRA | TOTAL NOMIN | TOTAL SS | GASTO | TOTAL FACTU | DIFF | HRS | ESTIMADO FACTUR
- Shows profitability per project, invoice estimates vs reality

### Period Navigation
- Excel tabs: mon69, mon70, ... mon84 for each period
- Instant context switch — everything is scoped to the active period

---

## 3. WHAT YOUR WEB APP CURRENTLY HAS

| Page | Purpose | Data Source |
|------|---------|-------------|
| Workers | CRUD worker master data | workers table |
| Monthly Periods | Period management | monthly_periods table |
| Project Months | Summary per client/project/period | project_months table |
| Project Invoices | Invoice tracking | project_invoices table |
| Project Expenses | Expense tracking | project_expenses table |
| Worker Monthly Summaries | Aggregated worker totals | worker_monthly_summaries table |
| Worker Payments | Payment records | worker_payments table |

### What's Missing from Navigation
- **Project Expenses** — not in top nav
- **Worker Payments** — not in top nav
- **Worker Project Entries** — doesn't exist as a page at all

---

## 4. CRITICAL GAPS & MISSING FEATURES

### 4.1 THE BIG ONE: No Worker Project Entries UI
**Severity: CRITICAL**

Your `worker_project_entries` table has these fields:
- `project_month_id`, `worker_id`, `special_note`, `social_security`, `hours`, `days`, `rate`, `total_amount`, `paid_amount`

But:
- Model has **no fillable array** (empty model!)
- **No Livewire page**
- **No route**
- **No blade view**
- **No menu item**

This means the **granular data entry layer** (your MON83 sheet) doesn't exist in the web app. Users cannot enter individual worker hours, rates, and SS per project.

### 4.2 Data Flow is Backwards

**Excel flow (correct):**
```
Worker Project Entries (MON83)
    ↓ (auto sum)
Worker Monthly Summary (Resumen)
    ↓ (auto sum)
Project Month Totals (TOTAL)
```

**Web app flow (wrong):**
```
Project Months ← manually enter totals
Worker Monthly Summaries ← manually enter totals
Worker Payments ← manually enter payments
```

You are manually entering numbers that should be **automatically calculated** from granular entries.

### 4.3 No Automatic Calculations

These should be auto-calculated but aren't:

| Field | Should Calculate From | Currently |
|-------|----------------------|-----------|
| `project_months.total_nominal` | SUM(worker_project_entries.total_amount) | Manual entry |
| `project_months.total_social_security` | SUM(worker_project_entries.social_security) | Manual entry |
| `project_months.total_hours` | SUM(worker_project_entries.hours) | Manual entry |
| `worker_monthly_summaries.total_amount` | SUM(worker_project_entries.total_amount) by worker | Manual entry |
| `worker_monthly_summaries.total_hours` | SUM(worker_project_entries.hours) by worker | Manual entry |
| `worker_monthly_summaries.payroll_amount` | SUM(worker_project_entries.social_security) by worker | Manual entry |
| `worker_monthly_summaries.difference` | total_amount - paid_amount | Auto (good!) |
| `worker_monthly_summaries.final_difference` | difference - payroll - advance - credit - ticket | Auto (good!) |

### 4.4 Missing Fields in UI

| Field | Exists in DB | Has UI | Notes |
|-------|-------------|--------|-------|
| `worker_project_entries.days` | Yes | No | Days worked — in Excel DIAS column |
| `worker_project_entries.rate` | Yes | No | Hourly rate — in Excel PRECIO column |
| `worker_project_entries.special_note` | Yes | No | Per-worker per-project notes |
| `worker_project_entries.paid_amount` | Yes | No | Track partial payment per entry |
| `worker_monthly_summaries.ticket_amount` | Yes | Partial | Field exists but no clear input |

### 4.5 Missing Visual Indicators

**Excel uses extensive color coding:**
- Red background = negative difference, problem worker
- Green checkmark = paid
- Blue highlighted rows = selected/filtered
- Yellow cells = warnings/low hours
- Gray rows = totals/summary

**Web app has:**
- Minimal color coding (green/red for margin only)
- No visual "paid" indicator on worker rows
- No row highlighting for problematic entries
- No checkmark icons for payment status

### 4.6 No Period-Contextual Navigation

**Excel:**
- Click tab "mon83" → instantly see March 2026 data
- Every sheet is scoped to one period
- No need to select filters

**Web app:**
- Must select period from dropdown filter every time
- No persistent "active period" context
- Easy to enter data in wrong period

### 4.7 No "Resumen" Equivalent Dashboard

Your Excel Resumen sheet shows:
- All workers for the period at a glance
- Instant totals: HRS, TRABAJADORES, PAGADO, RESTO DE PAGAR
- Visual scan of who is paid, who owes money
- One-page decision board

Your web app has **no equivalent single-screen overview** for worker monthly status.

### 4.8 No "TOTAL" Sheet Equivalent

Your Excel TOTAL sheet shows:
- All clients and projects for the period
- Financial summary per project
- Invoice estimates vs actuals
- Hours totals
- Profitability at a glance

The Project Months page shows raw data but **lacks the executive summary view** with visual profit/loss indicators per project.

### 4.9 Missing Bulk/Spreadsheet-Style Entry

**Excel:**
- Enter 20 workers in 2 minutes
- Tab between cells
- Copy/paste rows
- Formula auto-fill

**Web app:**
- Click "New Row" → fill modal → save → repeat
- ~2 minutes per entry
- No bulk paste
- No rapid data entry mode

### 4.10 Payment Tracking is Disconnected

**Excel:**
- PAGADO column on each worker entry
- Checkmark = fully paid for that project
- Payment is contextual to the work

**Web app:**
- Worker Payments are separate records
- No linkage to which worker_project_entries were paid
- Can't see "paid status" on the worker entry itself
- `paid_amount` on worker_project_entries is never updated

---

## 5. UX ISSUES

### 5.1 Navigation Inconsistency
- Top nav shows only 6 items but routes exist for 8+ pages
- Project Expenses and Worker Payments are hidden (no nav links)
- User has to know URLs or use browser back button

### 5.2 Form Modals are Slow for Data Entry
- Every creation requires opening a modal
- 7+ fields to fill per project month
- No inline spreadsheet-style editing for new rows
- Inline editing exists only for updates, not creation

### 5.3 Filter Reset Required Constantly
- Switching between periods requires clearing and re-setting filters
- No "remember my last filter" functionality
- No default "current month" filter

### 5.4 Missing Worker Profile/Detail View
- Excel: click a worker name in Resumen, you know their full context
- Web app: workers table has no drill-down to see their monthly history, payments, projects

### 5.5 No Excel-Style Summary Row
- Excel has frozen footer rows with totals
- Web app has totals in tfoot but they're not sticky/frozen
- When scrolling, you lose sight of totals

---

## 6. WHAT SHOULD BE AUTOMATED

### 6.1 Auto-Calculate Project Month Totals
When worker_project_entries are created/updated for a project_month:
```php
// Auto-calculate in ProjectMonth model
$projectMonth->total_nominal = $entries->sum('total_amount');
$projectMonth->total_social_security = $entries->sum('social_security');
$projectMonth->total_hours = $entries->sum('hours');
```

### 6.2 Auto-Calculate Worker Monthly Summaries
When worker_project_entries change:
```php
// Auto-calculate per worker per period
$summary->total_amount = $entries->sum('total_amount');
$summary->total_hours = $entries->sum('hours');
$summary->payroll_amount = $entries->sum('social_security');
```

### 6.3 Auto-Calculate Worker Project Entry Total
```php
// In WorkerProjectEntry model
$entry->total_amount = $entry->social_security + ($entry->hours * $entry->rate);
```

### 6.4 Auto-Update Paid Status
When a WorkerPayment is created:
- Distribute payment across worker_project_entries (oldest first)
- Update `paid_amount` on each entry
- Update `paid_amount` on worker_monthly_summaries

### 6.5 Auto-Create Missing Summaries
When entries exist but no summary record:
- Auto-create WorkerMonthlySummary for that worker/period
- Auto-create ProjectMonth totals from entries

### 6.6 Period Default
- Default all pages to current/open period
- Show period selector as a global header, not per-page filter

---

## 7. RECOMMENDED PRIORITY ACTIONS

### P0 — Critical (Do First)
1. **Build Worker Project Entries page** — This is the foundation everything else depends on
2. **Add fillable fields to WorkerProjectEntry model** and create relationships
3. **Add auto-calculation logic** to WorkerProjectEntry (total_amount = SS + hours×rate)
4. **Add route and navigation** for Worker Project Entries
5. **Add Project Expenses and Worker Payments to top navigation**

### P1 — High Priority
6. **Auto-calculate ProjectMonth totals** from worker_project_entries
7. **Auto-calculate WorkerMonthlySummary** from worker_project_entries
8. **Create "Resumen" dashboard view** — single page showing all workers for selected period with totals
9. **Create "TOTAL" dashboard view** — single page showing all projects for selected period with profit/loss
10. **Add global period selector** in page header (like Excel tabs)

### P2 — Medium Priority
11. **Add bulk entry mode** — spreadsheet-style inline row creation
12. **Add visual indicators** — paid checkmarks, red for negative, color coding
13. **Add worker drill-down** — click worker to see their project history, payments, monthly summaries
14. **Connect payments to entries** — update paid_amount on worker_project_entries when payment recorded
15. **Add "days" field to UI** — exists in DB but not exposed

### P3 — Nice to Have
16. **Excel import for worker_project_entries** (import the MON83 sheets directly)
17. **Copy period feature** — copy all entries from previous month as starting point
18. **Rate templates** — save default rates per worker for quick entry
19. **Mobile-friendly entry** — simplified view for field data entry

---

## 8. DATA MODEL CORRECTIONS NEEDED

### WorkerProjectEntry Model (Currently Empty)
```php
// Currently:
class WorkerProjectEntry extends Model { }

// Should be:
class WorkerProjectEntry extends Model
{
    protected $fillable = [
        'project_month_id', 'worker_id', 'special_note',
        'social_security', 'hours', 'days', 'rate',
        'total_amount', 'paid_amount'
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        static::saving(function ($entry) {
            $entry->total_amount = (float)$entry->social_security 
                + ((float)$entry->hours * (float)$entry->rate);
        });
    }
    
    public function projectMonth() { return $this->belongsTo(ProjectMonth::class); }
    public function worker() { return $this->belongsTo(Worker::class); }
}
```

### ProjectMonth Model — Add Auto-Calc
```php
// Add method to recalculate from entries
public function recalculateTotals(): void
{
    $entries = $this->workerProjectEntries;
    $this->total_nominal = $entries->sum('total_amount');
    $this->total_social_security = $entries->sum('social_security');
    $this->total_hours = $entries->sum('hours');
    $this->save();
}
```

### WorkerMonthlySummary Model — Add Auto-Calc
```php
// Currently auto-calcs difference/final_difference (good)
// But should also auto-calc from entries:
public function recalculateFromEntries(): void
{
    $entries = WorkerProjectEntry::where('worker_id', $this->worker_id)
        ->whereHas('projectMonth', fn($q) => 
            $q->where('monthly_period_id', $this->monthly_period_id))
        ->get();
    
    $this->total_amount = $entries->sum('total_amount');
    $this->total_hours = $entries->sum('hours');
    $this->payroll_amount = $entries->sum('social_security');
    $this->save(); // difference/final_difference auto-calcs in boot()
}
```

---

## 9. CONCLUSION

Your web app has the **database structure** mostly correct but is missing the **granular data entry layer** that makes your Excel system work. The core issue is that you've built summary tables without building the detail table's UI.

**The path forward:**
1. Build the Worker Project Entries page (your MON83 sheet)
2. Wire up automatic calculations so summaries populate themselves
3. Add period-scoped dashboard views (Resumen + TOTAL equivalents)
4. Improve navigation and visual indicators

Without the granular entry layer, your team will keep using Excel because the web app requires them to manually calculate and enter totals — which is more work, not less.
