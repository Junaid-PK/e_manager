# Requirements Document

## Introduction

This feature adds a granular role-based access control (RBAC) system to the Laravel/Livewire dashboard application. Currently all authenticated users have unrestricted access to every module. The new system introduces Roles (named groups of permissions) and Permissions (module + action pairs), allowing administrators to define exactly what each user can view, create, edit, delete, or export within each section of the application.

Modules covered: Dashboard, Invoices, Bank Movements, Bank Accounts, Expenses, Credit Lines, Companies/Clients, Reports, Reminders, Settings, Users.

Actions covered per module: `view`, `create`, `edit`, `delete`, `export` (where applicable).

## Glossary

- **RBAC_System**: The roles and permissions subsystem being introduced.
- **Role**: A named entity that groups a set of permissions and can be assigned to one or more users.
- **Permission**: A single capability defined as a combination of a module name and an action (e.g., `invoices.edit`).
- **Administrator**: A user with the built-in `admin` role that has all permissions and can manage roles and users.
- **User**: An authenticated account in the application, represented by the existing `User` model.
- **Module**: One of the application sections: `dashboard`, `invoices`, `movements`, `bank_accounts`, `expenses`, `credit_lines`, `companies_clients`, `reports`, `reminders`, `settings`, `users`.
- **Action**: One of the operations: `view`, `create`, `edit`, `delete`, `export`.
- **Permission_Gate**: A Laravel Gate or middleware check that enforces a permission at the route or component level.
- **Roles_Manager**: The Livewire component/page that allows Administrators to manage roles and their permissions.
- **Users_Manager**: The Livewire component/page that allows Administrators to create, edit, delete, and manage users, as well as assign roles to them.

---

## Requirements

### Requirement 1: Role Management

**User Story:** As an Administrator, I want to create, edit, and delete roles, so that I can define named groups of permissions that reflect my team's responsibilities.

#### Acceptance Criteria

1. THE Roles_Manager SHALL display a list of all existing roles including their name and the number of users assigned to each role.
2. WHEN an Administrator submits a valid role name, THE Roles_Manager SHALL create a new role and persist it to the database.
3. WHEN an Administrator submits a role name that already exists, THE Roles_Manager SHALL reject the submission and display a validation error.
4. WHEN an Administrator edits a role, THE Roles_Manager SHALL allow updating the role name and its assigned permissions.
5. WHEN an Administrator attempts to delete a role that has users assigned to it, THE Roles_Manager SHALL prevent deletion and display an error message indicating the number of affected users.
6. WHEN an Administrator deletes a role that has no users assigned, THE Roles_Manager SHALL remove the role and all its associated permission records from the database.
7. THE RBAC_System SHALL include a built-in `admin` role that cannot be deleted or renamed.

### Requirement 2: Permission Assignment to Roles

**User Story:** As an Administrator, I want to assign specific module/action permissions to each role, so that I can control exactly what each role can do in the application.

#### Acceptance Criteria

1. THE Roles_Manager SHALL present a permission matrix showing all Modules as rows and all Actions as columns, with checkboxes to toggle each permission.
2. WHEN an Administrator toggles a permission checkbox and saves, THE RBAC_System SHALL persist the updated permission set for that role.
3. THE RBAC_System SHALL support the following actions per module: `view`, `create`, `edit`, `delete`, and `export` (where `export` applies only to modules that have export functionality: `invoices`, `movements`).
4. WHEN the `admin` role is displayed in the permission matrix, THE Roles_Manager SHALL show all permissions as enabled and SHALL NOT allow them to be modified.
5. WHEN a role has no `view` permission for a module, THE RBAC_System SHALL treat all other actions for that module as implicitly denied for that role.

### Requirement 3: User Management

**User Story:** As an Administrator, I want to create, edit, and delete user accounts, so that I can manage who has access to the application without relying on self-registration.

#### Acceptance Criteria

1. THE Users_Manager SHALL display a paginated list of all users showing their name, email address, assigned roles, and account creation date.
2. WHEN an Administrator submits a valid name, email address, and password, THE Users_Manager SHALL create a new user account and persist it to the database.
3. WHEN an Administrator submits a new user form with an email address that already exists, THE Users_Manager SHALL reject the submission and display a validation error.
4. WHEN an Administrator submits a new user form with an invalid email format or a password shorter than 8 characters, THE Users_Manager SHALL reject the submission and display a descriptive validation error.
5. WHEN an Administrator edits an existing user, THE Users_Manager SHALL allow updating the user's name and email address.
6. WHEN an Administrator provides a new password while editing a user, THE Users_Manager SHALL update the user's password; WHEN the password field is left blank, THE Users_Manager SHALL leave the existing password unchanged.
7. WHEN an Administrator deletes a user, THE Users_Manager SHALL remove the user account and all associated role assignments from the database.
8. WHEN an Administrator attempts to delete their own account while being the sole Administrator, THE RBAC_System SHALL prevent the deletion and display an explanatory error message.
9. THE `users` module SHALL be treated as a protected module supporting `view`, `create`, `edit`, and `delete` actions, accessible only to users with the corresponding permissions.

### Requirement 4: User Role Assignment

**User Story:** As an Administrator, I want to assign one or more roles to each user, so that each person gets the appropriate level of access.

#### Acceptance Criteria

1. THE Users_Manager SHALL display a list of all users with their currently assigned roles.
2. WHEN an Administrator assigns a role to a user, THE RBAC_System SHALL persist the user-role association and apply the combined permissions of all assigned roles immediately on the user's next request.
3. WHEN an Administrator removes a role from a user, THE RBAC_System SHALL revoke the permissions granted exclusively by that role on the user's next request.
4. THE RBAC_System SHALL allow a user to have multiple roles simultaneously, with effective permissions being the union of all assigned role permissions.
5. WHEN a user has no roles assigned, THE RBAC_System SHALL deny access to all protected modules except the user's own profile page.

### Requirement 5: Access Enforcement on Routes

**User Story:** As a developer, I want route-level access control, so that users without the required permission cannot reach a module's page.

#### Acceptance Criteria

1. WHEN an authenticated user navigates to a module route without the `view` permission for that module, THE RBAC_System SHALL redirect the user to the dashboard with a 403 error notification.
2. THE RBAC_System SHALL enforce permissions using Laravel middleware applied to each protected route.
3. WHEN a user with the `admin` role navigates to any route, THE RBAC_System SHALL grant access without checking individual permissions.
4. IF the permission middleware encounters an unauthenticated request, THEN THE RBAC_System SHALL redirect the user to the login page.

### Requirement 6: Access Enforcement in Livewire Components

**User Story:** As a developer, I want action-level access control inside Livewire components, so that unauthorized actions (create, edit, delete, export) are blocked even if a user manipulates the UI.

#### Acceptance Criteria

1. WHEN a Livewire component method that performs a `create`, `edit`, `delete`, or `export` action is invoked, THE RBAC_System SHALL verify the corresponding permission before executing the action.
2. IF a user lacks the required permission for a Livewire action, THEN THE RBAC_System SHALL abort the action and dispatch an `unauthorized` notification to the user.
3. THE RBAC_System SHALL hide UI elements (buttons, links) for actions the current user does not have permission to perform, using Blade directives or component conditionals.
4. WHEN a user has `view` permission but not `edit` permission for a module, THE RBAC_System SHALL display the module's data in read-only mode.

### Requirement 7: Administrator Self-Protection

**User Story:** As a system designer, I want to prevent Administrators from accidentally locking themselves out, so that the application always has at least one Administrator.

#### Acceptance Criteria

1. THE RBAC_System SHALL ensure at least one user is assigned the `admin` role at all times.
2. WHEN an Administrator attempts to remove the `admin` role from the last user who holds it, THE Roles_Manager SHALL prevent the action and display an explanatory error message.

### Requirement 8: Roles and Permissions Seeding

**User Story:** As a developer, I want default roles seeded on fresh installs, so that the application is usable immediately after setup.

#### Acceptance Criteria

1. THE RBAC_System SHALL seed a default `admin` role with all permissions on database setup.
2. THE RBAC_System SHALL seed a default `viewer` role with only `view` permissions for all modules on database setup.
3. WHEN the seeder runs on an existing database, THE RBAC_System SHALL use `firstOrCreate` semantics to avoid duplicating roles or permissions.
4. THE RBAC_System SHALL assign the `admin` role to the first existing user during seeding if no user already holds the `admin` role.

### Requirement 9: Settings Page Access for Roles UI

**User Story:** As an Administrator, I want to manage roles and user assignments from within the Settings page, so that access control configuration is centralized.

#### Acceptance Criteria

1. THE Settings page SHALL include a "Roles & Permissions" tab visible only to users with the `admin` role.
2. WHEN a non-Administrator navigates to the Settings page, THE RBAC_System SHALL hide the "Roles & Permissions" tab and deny access to its content.
3. THE Roles_Manager SHALL be accessible as a sub-section of the Settings page or as a dedicated route protected by the `admin` role check.
