# Role Management System

This document describes the comprehensive Role-Based Access Control (RBAC) system implemented in the Central SSO server.

## Overview

The role management system provides fine-grained access control for the Central SSO server, allowing administrators to control who can access what features and perform which actions.

## Permission System

### Categories

The system organizes permissions into 6 logical categories:

#### 1. Users (4 permissions)
- `users.view` - View user lists and details
- `users.create` - Create new users
- `users.edit` - Edit existing user information
- `users.delete` - Delete users from the system

#### 2. Roles (5 permissions)
- `roles.view` - View roles and their permissions
- `roles.create` - Create new roles
- `roles.edit` - Edit existing roles
- `roles.delete` - Delete custom roles (system roles protected)
- `roles.assign` - Assign/remove roles from users

#### 3. Tenants (4 permissions)
- `tenants.view` - View tenant information
- `tenants.create` - Create new tenant applications
- `tenants.edit` - Edit tenant configuration
- `tenants.delete` - Delete tenants (with safeguards)

#### 4. System (2 permissions)
- `system.settings` - Access system configuration
- `system.logs` - View system logs and monitoring

#### 5. API (1 permission)
- `api.manage` - Access API management tools

#### 6. Developer (2 permissions)
- `telescope.access` - Access Laravel Telescope debugging interface
- `swagger.access` - Access API documentation (Swagger/OpenAPI)

### Permission Structure

Each permission has the following attributes:
- **Name**: Human-readable permission name
- **Slug**: Unique identifier (category.action format)
- **Category**: Logical grouping for organization
- **Description**: Detailed explanation of what the permission allows
- **System Flag**: Indicates if permission is system-protected

## Role System

### Default Roles

The system comes with 5 pre-configured roles:

#### 1. Super Admin
- **Permissions**: All 19 permissions
- **Purpose**: Full system access
- **Protection**: System role, cannot be deleted

#### 2. Admin
- **Permissions**: All except system-level permissions
- **Purpose**: General administration
- **Protection**: System role, cannot be deleted

#### 3. Manager
- **Permissions**: View and manage users, limited tenant access
- **Purpose**: User management and basic tenant operations
- **Protection**: System role, cannot be deleted

#### 4. User
- **Permissions**: Basic viewing permissions
- **Purpose**: Standard user access
- **Protection**: System role, cannot be deleted

#### 5. Viewer
- **Permissions**: Read-only access to most resources
- **Purpose**: Monitoring and reporting
- **Protection**: System role, cannot be deleted

### Custom Roles

Administrators can create custom roles with any combination of permissions:
- Define custom role names and descriptions
- Select specific permissions from all categories
- Assign custom roles to users
- Edit custom role permissions
- Delete custom roles (system roles protected)

## Multi-Tenant Role Assignment

### Scope Options

Roles can be assigned in two scopes:

#### Global Roles
- Apply across all tenants
- Useful for system administrators
- No tenant restriction

#### Tenant-Specific Roles
- Apply only to specific tenants
- Useful for tenant administrators
- Restricted to assigned tenant

### Assignment Examples

```
User: john@example.com
- Global Role: Manager (access to user management across all tenants)
- Tenant 1 Role: Admin (full access within Tenant 1)
- Tenant 2 Role: Viewer (read-only access within Tenant 2)
```

## Role Management Interface

### Web UI Features

The role management interface provides:

#### Roles Tab
- List all roles with permissions and user counts
- Create new custom roles
- Edit existing roles (system roles have limited editing)
- Delete custom roles with confirmation
- Visual permission badges showing assigned permissions

#### Permissions Tab
- Organized by category for easy browsing
- Detailed permission descriptions
- System permission indicators
- Permission usage tracking

#### User Assignments Tab
- List all users with their current roles
- Assign/remove roles from users
- Tenant-specific role management
- Real-time role updates

### UI Components

#### Modern Design
- **shadcn/ui Design System**: Consistent, professional appearance
- **Responsive Layout**: Works on desktop and mobile devices
- **Toast Notifications**: User-friendly success/error messages
- **Interactive Elements**: Smooth animations and transitions

#### User Experience
- **Confirmation Dialogs**: Prevent accidental deletions
- **Real-time Updates**: Live data refresh after changes
- **Form Validation**: Client and server-side validation
- **Loading States**: Visual feedback during operations

## API Integration

### REST Endpoints

All role management features are available via REST API:

```http
# Roles Management
GET    /api/roles                    # List all roles
POST   /api/roles                    # Create new role
GET    /api/roles/{id}               # Get role details
PUT    /api/roles/{id}               # Update role
DELETE /api/roles/{id}               # Delete role

# Permissions Management
GET    /api/permissions              # List all permissions
GET    /api/permissions/categories   # Get permission categories

# User Role Assignment
GET    /api/users/{id}/roles         # Get user roles
POST   /api/users/{id}/roles         # Assign role to user
DELETE /api/users/{id}/roles         # Remove role from user
PUT    /api/users/{id}/roles/sync    # Sync user roles
```

### Request/Response Format

#### Create Role Request
```json
{
  "name": "Content Manager",
  "description": "Manages content and users",
  "permissions": ["users.view", "users.edit", "tenants.view"]
}
```

#### Role Response
```json
{
  "data": {
    "id": 6,
    "name": "Content Manager",
    "slug": "content-manager",
    "description": "Manages content and users",
    "is_system": false,
    "created_at": "2024-01-15T10:30:00Z",
    "permissions": [
      {
        "id": 1,
        "name": "View Users",
        "slug": "users.view",
        "category": "users"
      }
    ]
  }
}
```

#### Assign Role Request
```json
{
  "role_slug": "content-manager",
  "tenant_id": 1  // Optional: for tenant-specific assignment
}
```

## Security Implementation

### Middleware Protection

All role management features are protected by middleware:

```php
// Route protection example
Route::middleware(['auth', 'permission:roles.view'])->group(function () {
    Route::get('/admin/roles', [RoleController::class, 'index']);
});
```

### Permission Checking

Multiple methods for permission verification:

```php
// In controllers
if (!auth()->user()->hasPermission('users.create')) {
    abort(403, 'Unauthorized');
}

// In Blade templates
@if(auth()->user()->hasPermission('roles.edit'))
    <button>Edit Role</button>
@endif

// Middleware
Route::middleware('permission:tenants.delete')->delete('/tenants/{id}');
```

### Database Security

#### Model Protection
- System roles/permissions have `is_system = true`
- Deletion attempts on system items are blocked
- Cascade deletions handled safely

#### Relationship Integrity
- Foreign key constraints maintain data integrity
- Pivot table relationships properly managed
- Orphaned records prevented

## Best Practices

### Role Design
1. **Principle of Least Privilege**: Grant minimum permissions needed
2. **Logical Grouping**: Group related permissions in roles
3. **Clear Naming**: Use descriptive role names and descriptions
4. **Regular Review**: Periodically audit role assignments

### Permission Assignment
1. **Start Small**: Begin with minimal permissions, add as needed
2. **Test Thoroughly**: Verify permission combinations work correctly
3. **Document Changes**: Keep track of custom role modifications
4. **Monitor Usage**: Track which permissions are actually used

### Multi-Tenant Considerations
1. **Scope Clarity**: Clearly define global vs tenant-specific roles
2. **Tenant Isolation**: Ensure tenant-specific roles don't cross boundaries
3. **Administrative Hierarchy**: Maintain clear admin role hierarchy
4. **Access Reviews**: Regularly review cross-tenant access

## Troubleshooting

### Common Issues

#### Permission Denied Errors
- Verify user has required role assigned
- Check if role has necessary permissions
- Confirm role assignment is in correct tenant scope

#### Role Assignment Failures
- Ensure role exists and is not deleted
- Verify user exists in the system
- Check for database connection issues

#### UI Not Loading
- Verify user has `roles.view` permission
- Check browser console for JavaScript errors
- Confirm API endpoints are accessible

### Debug Commands

```bash
# Check user roles
docker exec central-sso php artisan tinker
>>> User::find(1)->roles

# Verify permissions
>>> User::find(1)->hasPermission('users.view')

# List all permissions
>>> Permission::all()->pluck('slug')
```

## Migration and Seeding

### Database Migrations
- `create_roles_table` - Creates roles table
- `create_permissions_table` - Creates permissions table
- `create_role_permissions_table` - Permission assignments
- `create_model_has_roles_table` - User role assignments

### Seeders
- `DefaultRolesAndPermissionsSeeder` - Seeds all default roles and permissions
- `AddTestUsersSeeder` - Assigns roles to test users

### Running Migrations
```bash
# Run all migrations
docker exec central-sso php artisan migrate

# Seed default data
docker exec central-sso php artisan db:seed --class=DefaultRolesAndPermissionsSeeder
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder
```

This role management system provides a solid foundation for controlling access to the Central SSO system while remaining flexible enough to accommodate custom business requirements.