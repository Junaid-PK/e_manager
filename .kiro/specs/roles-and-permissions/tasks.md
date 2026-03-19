# Implementation Plan: Roles and Permissions

## Overview

Implement a custom RBAC system on top of the existing Laravel 11 + Livewire 3 application. Tasks are ordered so each step builds on the previous: schema first, then models and traits, then authorization infrastructure, then UI components, then route/component enforcement, and finally property-based tests woven in close to each implementation step.

## Tasks

- [x] 1. Database migrations
  - [x] 1.1 Create migration for `roles` table
    - `id`, `name` (unique), `timestamps`
    - _Requirements: 1.1, 1.2, 8.1_

  - [x] 1.2 Create migration for `permissions` table
    - `id`, `name` (unique, e.g. `invoices.edit`), `timestamps`
    - _Requirements: 2.1, 2.3, 8.1_

  - [x] 1.3 Create migration for `role_user` pivot table
    - `role_id` FK → `roles`, `user_id` FK → `users`, composite PK, cascade on delete
    - _Requirements: 4.1, 4.2_

  - [x] 1.4 Create migration for `role_permission` pivot table
    - `role_id` FK → `roles`, `permission_id` FK → `permissions`, composite PK, cascade on delete
    - _Requirements: 2.2, 1.6_

- [x] 2. Role and Permission models
  - [x] 2.1 Create `app/Models/Role.php`
    - `$fillable = ['name']`
    - `permissions(): BelongsToMany` → `Permission`
    - `users(): BelongsToMany` → `User`
    - _Requirements: 1.1, 1.4, 2.1_

  - [x] 2.2 Create `app/Models/Permission.php`
    - `$fillable = ['name']`
    - `roles(): BelongsToMany` → `Role`
    - _Requirements: 2.1, 2.3_

  - [x] 2.3 Create `app/Models/Concerns/HasRoles.php` trait
    - `roles(): BelongsToMany`
    - `hasRole(string $role): bool`
    - `hasPermission(string $permission): bool` — checks union of all assigned roles
    - `isAdmin(): bool` — shorthand for `hasRole('admin')`
    - _Requirements: 4.4, 5.3_

  - [x] 2.4 Add `HasRoles` trait to `app/Models/User.php`
    - Add `use App\Models\Concerns\HasRoles;` and include in `use` statement
    - _Requirements: 4.2, 4.3_

  - [x] 2.5 Write property test for `HasRoles` — Property 6: Union of role permissions
    - **Property 6: Union of role permissions**
    - Assign random sets of roles with random permissions to a user; assert `hasPermission` returns true iff the permission appears in at least one role
    - **Validates: Requirements 4.4**

  - [ ]* 2.6 Write property test for `HasRoles` — Property 5: No-role user denied
    - **Property 5: No-role user is denied all modules**
    - Create users with no roles; assert `hasPermission` returns false for any permission string
    - **Validates: Requirements 4.5**

  - [ ]* 2.7 Write property test for `HasRoles` — Property 7: View-less module implies all actions denied
    - **Property 7: View-less module implies all actions denied**
    - Assign a role that lacks `{module}.view`; assert `hasPermission` returns false for all actions on that module
    - **Validates: Requirements 2.5**

- [x] 3. Gate registration in AppServiceProvider
  - [x] 3.1 Update `app/Providers/AppServiceProvider.php` `boot()` method
    - Load all `Permission` records via `Cache::remember('permissions', 300, fn() => Permission::all())`
    - Register `Gate::before(fn(User $user) => $user->isAdmin() ? true : null)`
    - For each permission, register `Gate::define($permission->name, fn(User $user) => $user->hasPermission($permission->name))`
    - Wrap in try/catch; on DB failure fall back to deny-all
    - _Requirements: 5.2, 5.3_

  - [ ]* 3.2 Write property test for Gate — Property 4: Admin implicit allow
    - **Property 4: Admin implicit allow**
    - Generate random permission strings; assert `Gate::allows($perm, $adminUser)` returns true regardless of stored permissions
    - **Validates: Requirements 5.3**

  - [ ]* 3.3 Write property test for Gate — Property 5: No-role user denied (Gate level)
    - **Property 5: No-role user denied (Gate level)**
    - Assert `Gate::allows($perm, $noRoleUser)` returns false for any permission string
    - **Validates: Requirements 4.5**

- [x] 4. CheckPermission middleware
  - [x] 4.1 Create `app/Http/Middleware/CheckPermission.php`
    - Accept permission string parameter: `permission:invoices.view`
    - Unauthenticated → redirect to login
    - Authenticated but `Gate::denies($permission)` → redirect to `dashboard` with `session()->flash('error', ...)`
    - _Requirements: 5.1, 5.2, 5.4_

  - [x] 4.2 Register `CheckPermission` as `permission` alias in `bootstrap/app.php`
    - Add inside `->withMiddleware(...)` using `$middleware->alias(['permission' => \App\Http\Middleware\CheckPermission::class])`
    - _Requirements: 5.2_

- [x] 5. Checkpoint — core authorization infrastructure
  - Ensure migrations run cleanly, models resolve relationships, Gate registers without errors, and middleware alias is available. Ask the user if questions arise.

- [x] 6. RolesAndPermissionsSeeder
  - [x] 6.1 Create `database/seeders/RolesAndPermissionsSeeder.php`
    - Define the full module/action matrix (see design §Permission Naming Convention)
    - `Permission::firstOrCreate(['name' => "$module.$action"])` for every pair
    - `Role::firstOrCreate(['name' => 'admin'])` → `syncPermissions(all)`
    - `Role::firstOrCreate(['name' => 'viewer'])` → `syncPermissions(*.view only)`
    - If no user holds `admin` role, assign it to `User::first()`
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

  - [x] 6.2 Register `RolesAndPermissionsSeeder` in `database/seeders/DatabaseSeeder.php`
    - Call `$this->call(RolesAndPermissionsSeeder::class)`
    - _Requirements: 8.1_

  - [ ]* 6.3 Write property test for seeder — Property 12: Seeder idempotency
    - **Property 12: Seeder idempotency**
    - Run `RolesAndPermissionsSeeder` N times (≥ 5); assert role count and permission count are identical after each run
    - **Validates: Requirements 8.3**

- [x] 7. UsersManager Livewire component
  - [x] 7.1 Create `app/Livewire/Settings/UsersManager.php`
    - Public properties: `$users` (paginated), `$editingUserId`, `$name`, `$email`, `$password`, `$selectedRoles[]`, `$confirmingDeleteId`, `$showFormModal`, `$showDeleteModal`
    - `createUser()` — validate (name required, email unique, password min:8), create user, sync roles
    - `editUser(int $id)` — load user into form fields
    - `updateUser()` — validate (name required, email unique ignoring self), update; skip password if blank
    - `deleteUser(int $id)` — check sole-admin guard (Req 3.8, 7.1), then delete with role detach
    - `assignRoles(int $userId, array $roleIds)` — sync pivot
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 4.1, 4.2, 4.3_

  - [x] 7.2 Create `resources/views/livewire/settings/users-manager.blade.php`
    - Paginated user table: name, email, roles badges, created_at
    - Create/edit modal with name, email, password, role checkboxes
    - Delete confirmation modal
    - _Requirements: 3.1, 4.1_

  - [ ]* 7.3 Write property test — Property 8: Sole-admin deletion prevention
    - **Property 8: Sole-admin deletion prevention**
    - Set up state with exactly one admin user; attempt `deleteUser`; assert user still exists and error is present
    - **Validates: Requirements 3.8, 7.1**

  - [ ]* 7.4 Write property test — Property 10: Duplicate email rejected
    - **Property 10: Duplicate email rejected**
    - Generate existing emails; call `createUser` with same email; assert no new user record and validation error present
    - **Validates: Requirements 3.3**

  - [ ]* 7.5 Write property test — Property 11: Blank password preserves hash
    - **Property 11: Blank password preserves hash**
    - Call `updateUser` with blank password field; assert stored password hash is unchanged
    - **Validates: Requirements 3.6**

- [x] 8. RolesManager Livewire component
  - [x] 8.1 Create `app/Livewire/Settings/RolesManager.php`
    - Public properties: `$roles`, `$editingRoleId`, `$roleName`, `$rolePermissions[]`, `$confirmingDeleteId`, `$showFormModal`, `$showDeleteModal`
    - `createRole()` — validate unique name, create role
    - `editRole(int $id)` — load role + permissions into form
    - `saveRole()` — update name (guard against renaming `admin`), sync permissions via pivot wrapped in `DB::transaction()`
    - `deleteRole(int $id)` — guard: `admin` role undeletable (Req 1.7), users assigned (Req 1.5)
    - `getPermissionMatrix()` — return structured `modules × actions` array for the view
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 2.1, 2.2, 2.3, 2.4_

  - [x] 8.2 Create `resources/views/livewire/settings/roles-manager.blade.php`
    - Roles list table: name, user count, edit/delete actions
    - Create/edit modal with role name input and permission matrix (modules as rows, actions as columns, checkboxes)
    - Admin role row: all checkboxes disabled
    - Delete confirmation modal
    - _Requirements: 1.1, 2.1, 2.4_

  - [ ]* 8.3 Write property test — Property 1: Admin role is undeletable
    - **Property 1: Admin role is undeletable**
    - Attempt `deleteRole` on the `admin` role N times with random state; assert `admin` role still exists after each attempt
    - **Validates: Requirements 1.7, 7.1**

  - [ ]* 8.4 Write property test — Property 2: Role deletion blocked when users assigned
    - **Property 2: Role deletion blocked when users assigned**
    - Create roles with random numbers of assigned users (> 0); attempt `deleteRole`; assert role and assignments unchanged
    - **Validates: Requirements 1.5**

  - [ ]* 8.5 Write property test — Property 3: Permission sync round-trip
    - **Property 3: Permission sync round-trip**
    - Generate random subsets of permission names; call `saveRole`; reload role permissions; assert sets are equal
    - **Validates: Requirements 2.2**

  - [ ]* 8.6 Write property test — Property 9: Duplicate role name rejected
    - **Property 9: Duplicate role name rejected**
    - Call `createRole` with an already-existing name; assert no new role record and validation error present
    - **Validates: Requirements 1.3**

- [ ] 9. Checkpoint — Livewire components
  - Ensure `UsersManager` and `RolesManager` render without errors, CRUD operations work, and all guards fire correctly. Ask the user if questions arise.

- [ ] 10. Settings page integration
  - [ ] 10.1 Update `app/Livewire/Settings/SettingsPage.php`
    - Add `'users'` and `'roles'` to the section switch logic (no extra properties needed; sections are rendered conditionally in the view)
    - _Requirements: 9.1, 9.2, 9.3_

  - [ ] 10.2 Update `resources/views/livewire/settings/settings-page.blade.php`
    - Add "Users" and "Roles & Permissions" nav buttons inside `@if(auth()->user()->isAdmin())` guard
    - Add `@if ($activeSection === 'users') <livewire:settings.users-manager /> @endif`
    - Add `@if ($activeSection === 'roles') <livewire:settings.roles-manager /> @endif`
    - _Requirements: 9.1, 9.2_

- [ ] 11. Route protection
  - [ ] 11.1 Update `routes/web.php` — apply `permission` middleware to all module routes
    - `dashboard` → `permission:dashboard.view`
    - `invoices` → `permission:invoices.view`
    - `bank-accounts` → `permission:bank_accounts.view`
    - `movements` → `permission:movements.view`
    - `movement-config` → `permission:movements.view`
    - `expenses` → `permission:expenses.view`
    - `companies-clients` → `permission:companies_clients.view`
    - `credit-lines` → `permission:credit_lines.view`
    - `reminders` → `permission:reminders.view`
    - `reports` → `permission:reports.view`
    - `settings` → `permission:settings.view`
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [ ] 12. Livewire component enforcement (Gate checks in existing components)
  - [ ] 12.1 Add Gate checks to `InvoicePage.php` mutating methods
    - `save()` → `Gate::authorize('invoices.create')` / `Gate::authorize('invoices.edit')`
    - `delete()` / `deleteSelected()` → `Gate::authorize('invoices.delete')`
    - `exportToExcel()` → `Gate::authorize('invoices.export')`
    - _Requirements: 6.1, 6.2_

  - [ ] 12.2 Add Gate checks to `MovementPage.php` mutating methods
    - `save()` → `Gate::authorize('movements.create')` / `Gate::authorize('movements.edit')`
    - `delete()` / `deleteSelected()` → `Gate::authorize('movements.delete')`
    - `exportToExcel()` → `Gate::authorize('movements.export')`
    - _Requirements: 6.1, 6.2_

  - [ ] 12.3 Add Gate checks to `BankAccountPage.php`, `ExpensePage.php`, `CreditLinePage.php`, `CompaniesClientsPage.php`, `ReminderPage.php`
    - Each: `save()` → `Gate::authorize('{module}.create')` / `Gate::authorize('{module}.edit')`
    - Each: `delete()` → `Gate::authorize('{module}.delete')`
    - _Requirements: 6.1, 6.2_

- [ ] 13. UI conditional rendering
  - [ ] 13.1 Update `resources/views/livewire/invoices/invoice-page.blade.php`
    - Wrap "New Invoice" button in `@can('invoices.create')`
    - Wrap edit/delete/export buttons in `@can('invoices.edit')`, `@can('invoices.delete')`, `@can('invoices.export')`
    - _Requirements: 6.3, 6.4_

  - [ ] 13.2 Update `resources/views/livewire/movements/movement-page.blade.php`
    - Same pattern as invoices for `movements.*` permissions
    - _Requirements: 6.3, 6.4_

  - [ ] 13.3 Update remaining module blade views (`bank-account-page`, `expense-page`, `credit-line-page`, `companies-clients-page`, `reminder-page`)
    - Wrap create/edit/delete buttons in `@can('{module}.{action}')` directives
    - _Requirements: 6.3, 6.4_

- [ ] 14. Checkpoint — enforcement and UI
  - Ensure middleware redirects unauthorized users, Gate checks abort unauthorized Livewire actions, and UI buttons are hidden for users lacking permissions. Ask the user if questions arise.

- [ ] 15. Property-based tests — remaining properties
  - [ ]* 15.1 Write integration property test — Property 4: Admin implicit allow (HTTP level)
    - **Property 4: Admin implicit allow (HTTP level)**
    - Generate random module routes; assert admin user receives 200, not a redirect
    - **Validates: Requirements 5.3**

  - [ ]* 15.2 Write integration property test — Property 5: No-role user denied (HTTP level)
    - **Property 5: No-role user denied (HTTP level)**
    - Generate random module routes; assert role-less user is redirected to dashboard
    - **Validates: Requirements 4.5, 5.1_

  - [ ]* 15.3 Write integration test — Settings tab visibility
    - Assert "Users & Roles" tab is absent in rendered HTML for non-admin users
    - Assert "Users & Roles" tab is present for admin users
    - **Validates: Requirements 9.1, 9.2**

- [ ] 16. Final checkpoint — full suite
  - Run `php artisan test` and ensure all tests pass. Ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- Property tests use PestPHP with `->repeat(100)` for minimum 100 iterations per property
- Each property test file should include a comment: `// Feature: roles-and-permissions, Property N: <title>`
- The `admin` role short-circuit is handled by `Gate::before`, so individual Gate definitions do not need to check `isAdmin()`
- Cache key `permissions` should be cleared whenever permissions are modified (add `Cache::forget('permissions')` in `RolesManager::saveRole()`)
- All pivot syncs in `RolesManager` are wrapped in `DB::transaction()` per the design's error handling spec
