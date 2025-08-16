# 🔒 Secure Tenant Application Template

This template provides a complete, production-ready Laravel application with enterprise-grade SSO security integration.

## 🚀 Quick Start

1. **Copy this template**:
   ```bash
   cp -r tenant-template/ my-new-tenant-app/
   cd my-new-tenant-app/
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Update configuration**:
   - Set `TENANT_SLUG` to your unique tenant identifier
   - Get `TENANT_API_KEY` and `HMAC_SECRET` from central SSO administrator
   - Configure database settings

5. **Run migrations**:
   ```bash
   php artisan migrate
   ```

6. **Start development server**:
   ```bash
   php artisan serve --port=8003
   ```

## 🔐 Security Features Included

- ✅ **API Key Authentication**: Secure server-to-server communication
- ✅ **HMAC Request Signing**: Request integrity protection
- ✅ **Rate Limiting**: Brute force protection
- ✅ **Comprehensive Audit**: Full authentication logging
- ✅ **SSL/TLS Support**: Production encryption
- ✅ **Token Validation**: JWT security
- ✅ **Error Handling**: Graceful degradation
- ✅ **Request ID Tracking**: Complete audit trails
- ✅ **Session Security**: Secure session management

## 📁 Template Structure

```
tenant-template/
├── app/
│   ├── Http/Controllers/AuthController.php
│   ├── Services/SecureSSOService.php
│   ├── Services/LoginAuditService.php
│   └── Models/User.php
├── config/security.php
├── resources/views/auth/
├── routes/web.php
├── .env.example
├── docker-compose.yml
└── README.md
```

## 🛠️ Customization

1. **Branding**: Update views and assets with your branding
2. **Database**: Modify migrations for your specific needs
3. **Features**: Add your application-specific functionality
4. **Security**: Adjust security settings in `config/security.php`

## 📚 Documentation

For complete integration instructions, see the [Secure Tenant Integration Guide](../CLAUDE.md#-secure-tenant-integration-guide).

## 🆘 Support

- Check the main SSO documentation in `../CLAUDE.md`
- Contact your SSO administrator for API keys and configuration
- Review security logs for troubleshooting authentication issues