# User Management

The Central SSO system provides comprehensive user management capabilities for administering all users across the multi-tenant environment.

## Overview

The user management system allows administrators to:
- Create, edit, and delete user accounts
- Manage user access to specific tenants
- Set admin privileges
- View user roles and permissions
- Handle password management securely

## Accessing User Management

The user management interface is available to authenticated admin users:

1. **URL**: `http://localhost:8000/admin/users`
2. **Navigation**: Admin Panel → Users (first item in sidebar)
3. **Permissions**: Requires authentication and appropriate admin permissions

## Features

### User Overview

The main user management page displays:
- **User List**: All users with avatars, names, and email addresses
- **Admin Badges**: Visual indicators for admin users
- **Tenant Access**: List of tenants each user can access
- **Role Assignments**: Current roles assigned to each user (both global and tenant-specific)
- **Creation Date**: When each user account was created
- **Action Buttons**: Edit, Manage Tenants, Delete options

### Creating Users

**Access**: Click "New User" button in the top-right corner

**Form Fields**:
- **Name**: Full name of the user
- **Email**: Email address (must be unique)
- **Password**: Secure password (minimum 8 characters)
- **Confirm Password**: Password confirmation for security
- **Admin User**: Checkbox to grant admin privileges
- **Tenant Access**: Multi-select checkboxes for tenant assignments

**Validation**:
- Email uniqueness is enforced
- Password confirmation is required
- Strong password requirements (8+ characters)

### Editing Users

**Access**: Click "Edit" button next to any user

**Capabilities**:
- Update name and email address
- Change admin status
- Modify tenant access assignments
- Update password (optional - leave blank to keep current)
- Password confirmation required when changing password

**Restrictions**:
- Email must remain unique across all users
- Cannot remove your own admin privileges

### Tenant Access Management

**Access**: Click "Tenants" button next to any user

**Current Access**:
- View all tenants the user currently has access to
- Remove access with individual "Remove" buttons
- Visual indicators for tenant names and slugs

**Grant New Access**:
- Dropdown to select from available tenants
- Only shows tenants not already assigned to the user
- "Grant Access" button to assign new tenant

**Real-time Updates**:
- Interface updates immediately after changes
- No page refresh required

### User Deletion

**Access**: Click "Delete" button next to any user

**Security Features**:
- Confirmation dialog prevents accidental deletion
- Cannot delete your own account (safety feature)
- Permanent action with warning message

**Cascade Behavior**:
- Removes all role assignments
- Removes all tenant access
- Maintains referential integrity

## API Endpoints

### User CRUD Operations

```http
GET    /admin/users           # List all users
POST   /admin/users           # Create new user
PUT    /admin/users/{id}      # Update existing user
DELETE /admin/users/{id}      # Delete user
```

### Tenant Assignment

```http
POST   /admin/users/{id}/tenants    # Assign tenant access
DELETE /admin/users/{id}/tenants    # Remove tenant access
```

### Data Endpoints

```http
GET /admin/users/data    # JSON data for AJAX updates
```

## Security Considerations

### Password Management
- Passwords are hashed using Laravel's bcrypt with 12 rounds
- Password confirmation required for all changes
- Minimum 8 character requirement enforced
- Option to leave password blank when editing (keeps current)

### Authorization
- User management requires authentication
- Admin privileges may be required for certain operations
- CSRF protection enabled on all forms
- Session-based authentication for web interface

### Self-Protection
- Users cannot delete their own accounts
- Prevents accidental lockout scenarios
- Admin status changes are logged and auditable

## Integration with Other Systems

### Role Management
- User management integrates with the role system
- Users can be assigned roles through the role management interface
- Role assignments are displayed in the user overview
- Both global and tenant-specific roles are supported

### Tenant Management
- Tenant access is managed independently from role assignments
- Users must have tenant access before being assigned tenant-specific roles
- Removing tenant access automatically removes associated roles

### Authentication Flow
- Users created through this interface can authenticate via:
  - Central SSO login forms
  - Direct tenant application login
  - API authentication with JWT tokens

## User Interface Design

### Modern UI Components
- **shadcn/ui Design System**: Consistent with the rest of the admin interface
- **Responsive Layout**: Works on desktop and mobile devices
- **Toast Notifications**: User-friendly success/error messages
- **Modal Dialogs**: Non-intrusive creation and editing workflows
- **Real-time Updates**: AJAX-powered interface updates without page refresh

### Accessibility Features
- Proper form labels and semantic HTML
- Keyboard navigation support
- Screen reader compatibility
- High contrast design elements

## Common Workflows

### Onboarding New Users

1. **Create User Account**:
   - Navigate to `/admin/users`
   - Click "New User"
   - Fill out required information
   - Assign initial tenant access
   - Save user account

2. **Assign Roles** (if needed):
   - Navigate to `/admin/roles`
   - Go to "User Assignments" tab
   - Find the new user
   - Click "Manage Roles"
   - Assign appropriate roles

3. **Test Access**:
   - User can now login at `/login`
   - User should have access to assigned tenants
   - Verify role permissions are working

### Managing Tenant Access

1. **Review Current Access**:
   - View user's current tenant assignments in the main list
   - Click "Tenants" to see detailed access management

2. **Grant Additional Access**:
   - Select tenant from dropdown (only shows available tenants)
   - Click "Grant Access"
   - Verify access appears in user's tenant list

3. **Remove Access**:
   - Click "Remove" next to specific tenant
   - Confirm the change in the interface
   - User will lose access to that tenant immediately

### Bulk Operations

Currently, the interface focuses on individual user management. For bulk operations, consider:
- Database seeders for large-scale user imports
- API endpoints for programmatic user creation
- CSV import functionality (potential future enhancement)

## Troubleshooting

### Common Issues

**Users Cannot Login**:
- Verify user account is not deleted
- Check tenant access assignments
- Ensure password was set correctly
- Verify email address is correct

**Permission Denied Errors**:
- Check user's role assignments
- Verify tenant-specific permissions
- Ensure user has access to the tenant they're trying to access

**Interface Not Loading**:
- Check authentication status
- Verify admin permissions
- Check browser console for JavaScript errors
- Ensure CSRF tokens are valid

### Database Queries

Useful queries for debugging user issues:

```sql
-- Check user's tenant access
SELECT u.name, u.email, t.name as tenant_name, t.slug 
FROM users u 
JOIN tenant_users tu ON u.id = tu.user_id 
JOIN tenants t ON tu.tenant_id = t.id 
WHERE u.email = 'user@example.com';

-- Check user's role assignments
SELECT u.name, r.name as role_name, r.slug, mhr.tenant_id
FROM users u 
JOIN model_has_roles mhr ON u.id = mhr.model_id 
JOIN roles r ON mhr.role_id = r.id 
WHERE u.email = 'user@example.com' 
AND mhr.model_type = 'App\\Models\\User';
```

## Future Enhancements

Potential improvements to the user management system:

- **Bulk User Import**: CSV/Excel file upload for mass user creation
- **User Groups**: Organize users into groups for easier management
- **Activity Logging**: Track user management actions and changes
- **Password Policies**: Configurable password strength requirements
- **Account Deactivation**: Soft delete functionality instead of permanent deletion
- **Email Verification**: Require email verification for new accounts
- **Password Reset**: Admin-initiated password reset functionality
- **Export Functionality**: Export user lists and reports