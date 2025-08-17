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

## User Profile Management

### Overview

The Central SSO system includes a comprehensive user profile management system that extends beyond basic user account information to provide detailed personal, professional, and contact data management.

### Profile Categories

#### Basic Profile Information
- **Personal Details**: Name, date of birth, gender, nationality, biographical information
- **Contact Information**: Primary phone number, emergency contacts
- **Professional Data**: Job title, department, company, work location, employment status
- **System Data**: Avatar/profile photos, account preferences

#### Extended Profile Data
- **Family Members**: Relationships, emergency contacts, dependent information
- **Contact Methods**: Multiple phone numbers, email addresses, communication preferences
- **Addresses**: Home, work, billing, and shipping addresses with full geographic data
- **Social Media**: Professional networks, social media profiles, online presence

### Profile Management Interface

#### User Profile Views

**Personal Profile Page**: `/profile/show`
- Comprehensive view of all profile information
- Organized into tabbed sections for easy navigation
- Display of family members, contacts, addresses, and social media
- Quick edit buttons for each section

**Profile Editing**: `/profile/edit`
- Modal-based editing forms for each profile category
- Real-time validation and updates
- File upload support for avatars and documents
- Responsive design for desktop and mobile

#### Admin Profile Management

**Admin User Profile Access**: `/admin/users/{id}`
- View complete user profiles from admin interface
- Edit all profile categories for any user
- Bulk profile operations and data management
- Profile completion analytics and reports

### Profile Management API

#### Core Profile Endpoints

```http
GET    /api/user/profile              # Get complete user profile
PUT    /api/user/profile              # Update basic profile information
POST   /api/user/profile/avatar       # Upload profile avatar
DELETE /api/user/profile/avatar       # Remove profile avatar
```

#### Family Member Management

```http
GET    /api/user/profile/family                    # Get all family members
POST   /api/user/profile/family                    # Add new family member
GET    /api/user/profile/family/{id}               # Get specific family member
PUT    /api/user/profile/family/{id}               # Update family member
DELETE /api/user/profile/family/{id}               # Remove family member
```

#### Contact Information Management

```http
GET    /api/user/profile/contacts                  # Get all contact methods
POST   /api/user/profile/contacts                  # Add new contact method
GET    /api/user/profile/contacts/{id}             # Get specific contact
PUT    /api/user/profile/contacts/{id}             # Update contact information
DELETE /api/user/profile/contacts/{id}             # Remove contact method
```

#### Address Management

```http
GET    /api/user/profile/addresses                 # Get all addresses
POST   /api/user/profile/addresses                 # Add new address
GET    /api/user/profile/addresses/{id}            # Get specific address
PUT    /api/user/profile/addresses/{id}            # Update address
DELETE /api/user/profile/addresses/{id}            # Remove address
```

#### Social Media Management

```http
GET    /api/user/profile/social-media              # Get all social media profiles
POST   /api/user/profile/social-media              # Add new social media profile
GET    /api/user/profile/social-media/{id}         # Get specific profile
PUT    /api/user/profile/social-media/{id}         # Update social media profile
DELETE /api/user/profile/social-media/{id}         # Remove social media profile
```

### Database Schema

#### Extended User Table
```sql
users:
  -- Basic Information
  id, name, email, password, is_admin
  
  -- Profile Fields
  phone, date_of_birth, gender, nationality, bio, avatar_url
  
  -- Address Information
  address_line_1, address_line_2, city, state_province, postal_code, country
  
  -- Emergency Contacts
  emergency_contact_name, emergency_contact_phone, emergency_contact_relationship
  
  -- Professional Information
  job_title, department, company, work_location, hire_date, employment_status
  
  -- Timestamps
  created_at, updated_at
```

#### Profile Extension Tables
```sql
user_family_members:
  id, user_id, name, relationship, date_of_birth, phone, email, address, 
  emergency_contact, notes, created_at, updated_at

user_contacts:
  id, user_id, contact_type, contact_value, is_primary, is_verified, notes,
  created_at, updated_at

user_addresses:
  id, user_id, address_type, address_line_1, address_line_2, city,
  state_province, postal_code, country, is_primary, notes,
  created_at, updated_at

user_social_media:
  id, user_id, platform, username, profile_url, is_public, notes,
  created_at, updated_at
```

### Profile Management Features

#### Data Validation
- **Email Validation**: Ensures valid email formats
- **Phone Validation**: Supports international phone number formats
- **Date Validation**: Proper date formatting and age restrictions
- **URL Validation**: Validates social media and website URLs
- **Address Validation**: Geographic location validation

#### Privacy Controls
- **Visibility Settings**: Control what profile information is visible
- **Public/Private Toggle**: Granular control over data sharing
- **Export Controls**: Manage data export permissions
- **Admin Override**: Administrative access to all profile data

#### File Management
- **Avatar Upload**: Profile photo upload and management
- **Document Storage**: Support for additional profile documents
- **File Validation**: Size, type, and security validation
- **CDN Integration**: Optimized file delivery and storage

### Profile Management Permissions

The profile management system includes granular permissions:

- **profile.view.own**: View own profile information
- **profile.edit.own**: Edit own profile information  
- **profile.view.all**: View all user profiles (admin)
- **profile.edit.all**: Edit all user profiles (admin)
- **profile.export**: Export profile data
- **profile.analytics**: Access profile completion analytics

### Integration Points

#### Authentication Integration
- Profile data available in JWT tokens for tenant applications
- Automatic profile synchronization across SSO ecosystem
- Profile-based access control and personalization

#### Tenant Application Access
- Tenant applications can access user profile data via API
- Profile information can be used for personalization
- Contact and address data available for business processes

#### Reporting and Analytics
- Profile completion statistics and metrics
- Data quality reports and validation
- User engagement and profile usage analytics

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
- **Profile Templates**: Pre-configured profile structures for different user types
- **Data Import/Export**: Bulk profile data management tools
- **Advanced Search**: Search users by profile criteria and custom fields