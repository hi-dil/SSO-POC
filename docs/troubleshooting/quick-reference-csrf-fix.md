# Quick Reference: 419 CSRF Error Fix

## Emergency Fix Checklist

If you encounter 419 "Page Expired" errors on Cloudflare tunnel deployment:

### 1. Check SSL Certificate Coverage ⚡
```bash
curl -I https://your-domain.hi-dil.com
# Look for "This hostname is not covered by a certificate"
```

### 2. Verify Domain Format 📋
**❌ Wrong (Third-level - Not covered by Universal SSL)**:
- `sso.poc.hi-dil.com`
- `app.subdomain.domain.com`

**✅ Correct (Second-level - Covered by Universal SSL)**:
- `sso-poc.hi-dil.com` 
- `app-subdomain.domain.com`

### 3. Quick Environment Fix 🔧
```bash
# Update .env file
SESSION_DOMAIN=.hi-dil.com  # Remove extra subdomain level
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=*

# Force container restart
docker-compose down && docker-compose up -d --force-recreate

# Clear Laravel caches
docker exec central-sso php artisan config:clear
docker exec central-sso php artisan cache:clear
```

### 4. Test HTTPS Detection 🧪
```bash
curl -s https://sso-poc.hi-dil.com/debug-https | jq '.https_detection.isSecure'
# Should return: true
```

### 5. Cloudflare Dashboard Updates 🌐
1. **DNS**: Create new A/CNAME records with orange cloud
2. **Tunnel**: Update public hostnames in Zero Trust
3. **SSL/TLS**: Ensure mode is "Full" (not Flexible)

## One-Liner Diagnostic 🚀
```bash
curl -s https://sso-poc.hi-dil.com/debug-https | jq '{ssl_working: .https_detection.isSecure, session_domain: .session_config.domain, csrf_token: (.csrf_token != null)}'
```

**Expected Output**:
```json
{
  "ssl_working": true,
  "session_domain": ".hi-dil.com", 
  "csrf_token": true
}
```

## Common Gotchas ⚠️

1. **Multiple .env files** - Check for app-specific .env files
2. **Cached config** - Always clear Laravel caches after changes
3. **Network isolation** - Ensure tunnel can reach containers
4. **DNS propagation** - Wait 15-30 minutes for SSL provisioning

## Success Verification ✅
- SSL certificate loads without warnings
- `isSecure()` returns `true` 
- Session domain matches certificate coverage
- Login forms work without 419 errors

## Files Modified 📝
- `.env` - Domain URLs and session configuration
- `bootstrap/app.php` - TrustProxies middleware
- `infrastructure/cloudflare/config.yml` - Tunnel hostnames
- `docker-compose.yml` - Network references

---
**Full Documentation**: See `docs/troubleshooting/419-csrf-error-resolution.md`