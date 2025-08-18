# Mobile API Deployment Checklist

## Production Deployment Checklist for Tenant 1 Mobile API Integration

This checklist ensures secure and reliable deployment of the mobile API integration for Tenant 1 applications.

## 🔧 Infrastructure Setup

### 1. Environment Configuration
- [ ] Copy `.env.production` template from `.env.docker`
- [ ] Update production domain settings:
  ```env
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://tenant1.yourdomain.com
  CENTRAL_SSO_URL=https://sso.yourdomain.com
  ```
- [ ] Generate secure random values:
  ```bash
  php artisan key:generate
  php artisan jwt:secret
  ```
- [ ] Configure production database credentials
- [ ] Set up SSL certificates (Let's Encrypt recommended)

### 2. Security Configuration
- [ ] Generate production API keys:
  ```bash
  php artisan mobile:generate-api-key
  ```
- [ ] Configure HMAC secrets (64 characters minimum):
  ```env
  HMAC_SECRET=your_64_character_production_secret_here
  ```
- [ ] Set up rate limiting in production:
  ```env
  RATE_LIMIT_AUTH=10
  RATE_LIMIT_API=100
  RATE_LIMIT_REFRESH=5
  ```
- [ ] Configure trusted proxies for load balancers:
  ```env
  TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
  ```

### 3. Database Setup
- [ ] Run production migrations:
  ```bash
  php artisan migrate --force
  ```
- [ ] Seed OAuth clients:
  ```bash
  php artisan db:seed --class=OAuthClientSeeder --force
  ```
- [ ] Create database backups schedule
- [ ] Configure database connection pooling

## 🚀 Application Deployment

### 4. Laravel Application
- [ ] Install production dependencies:
  ```bash
  composer install --no-dev --optimize-autoloader
  ```
- [ ] Optimize application:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
  ```
- [ ] Set proper file permissions:
  ```bash
  chown -R www-data:www-data storage bootstrap/cache
  chmod -R 775 storage bootstrap/cache
  ```
- [ ] Configure queue workers for background jobs:
  ```bash
  php artisan queue:work --daemon --sleep=3 --tries=3
  ```

### 5. Web Server Configuration
- [ ] Configure Nginx/Apache virtual host
- [ ] Set up SSL/TLS with HTTP/2
- [ ] Configure security headers:
  ```nginx
  add_header X-Frame-Options "SAMEORIGIN";
  add_header X-XSS-Protection "1; mode=block";
  add_header X-Content-Type-Options "nosniff";
  add_header Referrer-Policy "no-referrer-when-downgrade";
  add_header Content-Security-Policy "default-src 'self'";
  ```
- [ ] Configure rate limiting at web server level
- [ ] Set up gzip compression

## 📱 Mobile App Configuration

### 6. Android Application
- [ ] Update API endpoints in build configuration:
  ```kotlin
  buildConfigField("String", "API_BASE_URL", "\"https://tenant1.yourdomain.com/api/mobile/\"")
  buildConfigField("String", "SSO_BASE_URL", "\"https://sso.yourdomain.com/\"")
  ```
- [ ] Configure network security config:
  ```xml
  <network-security-config>
      <domain-config>
          <domain includeSubdomains="true">yourdomain.com</domain>
          <trust-anchors>
              <certificates src="system"/>
          </trust-anchors>
      </domain-config>
  </network-security-config>
  ```
- [ ] Enable ProGuard/R8 obfuscation for release builds
- [ ] Configure app signing with production keystore
- [ ] Test on multiple Android versions and devices

### 7. iOS Application
- [ ] Update API endpoints in configuration:
  ```swift
  enum APIConfig {
      static let baseURL = "https://tenant1.yourdomain.com/api/mobile/"
      static let ssoBaseURL = "https://sso.yourdomain.com/"
  }
  ```
- [ ] Configure App Transport Security (ATS):
  ```xml
  <key>NSAppTransportSecurity</key>
  <dict>
      <key>NSExceptionDomains</key>
      <dict>
          <key>yourdomain.com</key>
          <dict>
              <key>NSExceptionRequiresForwardSecrecy</key>
              <false/>
              <key>NSExceptionMinimumTLSVersion</key>
              <string>TLSv1.2</string>
              <key>NSIncludesSubdomains</key>
              <true/>
          </dict>
      </dict>
  </dict>
  ```
- [ ] Configure code signing with distribution certificate
- [ ] Test on multiple iOS versions and devices
- [ ] Submit to App Store Connect for review

## 🔍 Monitoring Setup

### 8. Application Monitoring
- [ ] Configure Laravel logs:
  ```env
  LOG_CHANNEL=daily
  LOG_LEVEL=info
  LOG_DAYS=14
  ```
- [ ] Set up application performance monitoring (APM)
- [ ] Configure error tracking (Sentry recommended)
- [ ] Set up uptime monitoring
- [ ] Configure log aggregation (ELK stack recommended)

### 9. Security Monitoring
- [ ] Configure intrusion detection system
- [ ] Set up security log monitoring
- [ ] Configure automated security alerts:
  ```env
  SECURITY_ALERT_EMAIL=security@yourdomain.com
  SECURITY_ALERT_SLACK_WEBHOOK=https://hooks.slack.com/...
  ```
- [ ] Set up vulnerability scanning schedule
- [ ] Configure SIEM integration

### 10. Performance Monitoring
- [ ] Configure database performance monitoring
- [ ] Set up API response time monitoring
- [ ] Configure memory and CPU usage alerts
- [ ] Set up mobile app crash reporting
- [ ] Configure user analytics and usage tracking

## 🧪 Pre-Production Testing

### 11. Security Testing
- [ ] Run OWASP ZAP security scan
- [ ] Perform penetration testing on API endpoints
- [ ] Test HMAC signature validation
- [ ] Verify rate limiting functionality
- [ ] Test authentication flow end-to-end

### 12. Performance Testing
- [ ] Load testing with concurrent users:
  ```bash
  ab -n 1000 -c 50 https://tenant1.yourdomain.com/api/mobile/auth/login
  ```
- [ ] Stress testing API endpoints
- [ ] Mobile app performance testing
- [ ] Database query optimization verification
- [ ] CDN configuration testing

### 13. Integration Testing
- [ ] Test Central SSO integration
- [ ] Verify mobile app authentication flows
- [ ] Test API token refresh mechanism
- [ ] Verify audit logging functionality
- [ ] Test failover and recovery procedures

## 📋 Go-Live Procedures

### 14. Pre-Launch
- [ ] Schedule maintenance window
- [ ] Notify stakeholders of deployment
- [ ] Prepare rollback procedures
- [ ] Configure DNS records
- [ ] Set up monitoring alerts

### 15. Launch
- [ ] Deploy application to production
- [ ] Update DNS to point to production
- [ ] Verify all services are running
- [ ] Test critical user journeys
- [ ] Monitor logs for errors

### 16. Post-Launch
- [ ] Monitor system performance for 24 hours
- [ ] Verify mobile app functionality
- [ ] Check security monitoring alerts
- [ ] Review performance metrics
- [ ] Collect user feedback

## 🔄 Ongoing Maintenance

### 17. Regular Tasks
- [ ] Weekly security updates
- [ ] Monthly performance reviews
- [ ] Quarterly security audits
- [ ] Regular backup testing
- [ ] Log retention management

### 18. Incident Response
- [ ] Document incident response procedures
- [ ] Train team on security incident handling
- [ ] Set up emergency contact procedures
- [ ] Test disaster recovery procedures
- [ ] Maintain incident response runbooks

## 📊 Success Metrics

### 19. KPIs to Monitor
- [ ] API response times (&lt; 200ms average)
- [ ] Authentication success rate (&gt; 99.5%)
- [ ] Mobile app crash rate (&lt; 0.1%)
- [ ] Security incident count (0 critical)
- [ ] User satisfaction scores (&gt; 4.5/5)

### 20. Business Metrics
- [ ] Daily active users
- [ ] API usage growth
- [ ] Mobile app retention rates
- [ ] Support ticket volume
- [ ] Revenue impact (if applicable)

## 🚨 Emergency Procedures

### Rollback Plan
If critical issues are discovered post-deployment:

1. **Immediate Actions:**
   ```bash
   # Disable mobile API endpoints
   php artisan down --message="Maintenance in progress"
   
   # Revert to previous application version
   git checkout previous-stable-tag
   composer install --no-dev
   php artisan up
   ```

2. **Database Rollback:**
   ```bash
   # Restore from backup (if schema changes were made)
   mysql -u user -p database < backup_pre_deployment.sql
   ```

3. **Mobile App Fallback:**
   - Enable maintenance mode in mobile apps
   - Redirect to web-based authentication temporarily
   - Communicate with users through push notifications

### Emergency Contacts
- [ ] DevOps Team: +1-XXX-XXX-XXXX
- [ ] Security Team: security@yourdomain.com
- [ ] Database Admin: dba@yourdomain.com
- [ ] Mobile Team Lead: mobile@yourdomain.com

---

## ✅ Deployment Sign-off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| DevOps Lead | | | |
| Security Officer | | | |
| Mobile Team Lead | | | |
| Product Owner | | | |
| QA Manager | | | |

---

**Note:** This checklist should be customized based on your organization's specific requirements, infrastructure, and compliance needs. Always perform thorough testing in a staging environment before production deployment.

## 📚 Additional Resources

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Mobile App Security Best Practices](../security-configuration.md)
- [API Performance Optimization Guide](../monitoring-and-analytics.md)
- [OAuth 2.0 Security Guidelines](https://tools.ietf.org/html/rfc6749)
- [OWASP Mobile Security Testing Guide](https://owasp.org/www-project-mobile-security-testing-guide/)