# Deployment Guide

## Overview

This guide covers deployment strategies for the multi-tenant SSO system in production environments, including security considerations, scaling options, and maintenance procedures.

## Production Architecture

### Recommended Infrastructure

```
┌─────────────────────┐    ┌─────────────────────┐    ┌─────────────────────┐
│   Load Balancer     │    │   Reverse Proxy     │    │   SSL Termination   │
│   (ALB/CloudFlare)  │    │   (Nginx/Traefik)   │    │   (Let's Encrypt)   │
└─────────┬───────────┘    └─────────┬───────────┘    └─────────┬───────────┘
          │                          │                          │
          └──────────────────────────┼──────────────────────────┘
                                     │
                    ┌─────────────────┼─────────────────┐
                    │                 │                 │
        ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
        │   Central SSO   │  │   Tenant App 1  │  │   Tenant App 2  │
        │  (Kubernetes)   │  │  (Kubernetes)   │  │  (Kubernetes)   │
        └─────────┬───────┘  └─────────┬───────┘  └─────────┬───────┘
                  │                    │                    │
                  └────────────────────┼────────────────────┘
                                       │
                         ┌─────────────┴─────────────┐
                         │      Database Cluster     │
                         │   ┌─────────────────────┐ │
                         │   │   Master (Write)    │ │
                         │   │   Read Replicas     │ │
                         │   │   Backup/Archive    │ │
                         │   └─────────────────────┘ │
                         └─────────────────────────────┘
```

### Component Specifications

#### Application Servers
- **CPU**: 2-4 vCPUs per service
- **Memory**: 4-8 GB RAM per service
- **Storage**: 20-50 GB SSD per service
- **Scaling**: Horizontal scaling with load balancer

#### Database Server
- **CPU**: 8-16 vCPUs (depending on tenant count)
- **Memory**: 16-64 GB RAM
- **Storage**: 100+ GB SSD with automated backups
- **High Availability**: Master-slave replication
- **Connection Pooling**: PgBouncer/ProxySQL recommended

## Docker Production Setup

### Production Docker Compose

```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  central-sso:
    build:
      context: ./central-sso
      dockerfile: Dockerfile.prod
    environment:
      APP_ENV: production
      APP_DEBUG: false
      APP_KEY: ${APP_KEY}
      DB_HOST: ${DB_HOST}
      DB_PASSWORD: ${DB_PASSWORD}
      JWT_SECRET: ${JWT_SECRET}
      REDIS_HOST: redis
    volumes:
      - ./storage/logs:/var/www/storage/logs
    networks:
      - sso-network
    restart: unless-stopped
    deploy:
      replicas: 3
      resources:
        limits:
          cpus: '2.0'
          memory: 4G
        reservations:
          cpus: '1.0'
          memory: 2G

  tenant1-app:
    build:
      context: ./tenant1-app
      dockerfile: Dockerfile.prod
    environment:
      APP_ENV: production
      APP_DEBUG: false
      CENTRAL_SSO_URL: https://sso.yourdomain.com
      CENTRAL_SSO_API: http://central-sso:8000/api
    networks:
      - sso-network
    restart: unless-stopped
    deploy:
      replicas: 2

  nginx:
    image: nginx:alpine
    volumes:
      - ./nginx/prod.conf:/etc/nginx/nginx.conf:ro
      - ./ssl:/etc/ssl/certs:ro
    ports:
      - "80:80"
      - "443:443"
    depends_on:
      - central-sso
      - tenant1-app
    networks:
      - sso-network
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
    networks:
      - sso-network
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}

  database:
    image: mariadb:10.11
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/backups:/backups
    networks:
      - sso-network
    restart: unless-stopped
    command: >
      --innodb-buffer-pool-size=2G
      --innodb-log-file-size=256M
      --max-connections=500
      --query-cache-size=128M

volumes:
  mysql_data:
  redis_data:

networks:
  sso-network:
    driver: bridge
```

### Production Dockerfile

```dockerfile
# central-sso/Dockerfile.prod
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    zip \
    unzip \
    curl \
    git

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    bcmath \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www/
COPY .env.production /var/www/.env

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Copy configuration files
COPY docker/prod/nginx.conf /etc/nginx/nginx.conf
COPY docker/prod/php.ini /usr/local/etc/php/php.ini
COPY docker/prod/supervisord.conf /etc/supervisord.conf

# Generate application key and cache
RUN php artisan key:generate \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
```

## Kubernetes Deployment

### Namespace and ConfigMap

```yaml
# k8s/namespace.yaml
apiVersion: v1
kind: Namespace
metadata:
  name: sso-system

---
# k8s/configmap.yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: sso-config
  namespace: sso-system
data:
  APP_ENV: production
  APP_DEBUG: "false"
  DB_CONNECTION: mysql
  DB_HOST: mysql-service
  DB_PORT: "3306"
  REDIS_HOST: redis-service
  REDIS_PORT: "6379"
  JWT_TTL: "3600"
```

### Central SSO Deployment

```yaml
# k8s/central-sso-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: central-sso
  namespace: sso-system
spec:
  replicas: 3
  selector:
    matchLabels:
      app: central-sso
  template:
    metadata:
      labels:
        app: central-sso
    spec:
      containers:
      - name: central-sso
        image: your-registry/central-sso:latest
        ports:
        - containerPort: 8000
        env:
        - name: APP_KEY
          valueFrom:
            secretKeyRef:
              name: sso-secrets
              key: app-key
        - name: DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: sso-secrets
              key: db-password
        - name: JWT_SECRET
          valueFrom:
            secretKeyRef:
              name: sso-secrets
              key: jwt-secret
        envFrom:
        - configMapRef:
            name: sso-config
        resources:
          requests:
            memory: "2Gi"
            cpu: "1000m"
          limits:
            memory: "4Gi"
            cpu: "2000m"
        livenessProbe:
          httpGet:
            path: /health
            port: 8000
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /ready
            port: 8000
          initialDelaySeconds: 5
          periodSeconds: 5

---
apiVersion: v1
kind: Service
metadata:
  name: central-sso-service
  namespace: sso-system
spec:
  selector:
    app: central-sso
  ports:
  - port: 80
    targetPort: 8000
  type: ClusterIP
```

### Database Deployment

```yaml
# k8s/mysql-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: mysql
  namespace: sso-system
spec:
  replicas: 1
  selector:
    matchLabels:
      app: mysql
  template:
    metadata:
      labels:
        app: mysql
    spec:
      containers:
      - name: mysql
        image: mariadb:10.11
        env:
        - name: MYSQL_ROOT_PASSWORD
          valueFrom:
            secretKeyRef:
              name: sso-secrets
              key: mysql-root-password
        - name: MYSQL_DATABASE
          value: sso_main
        - name: MYSQL_USER
          value: sso_user
        - name: MYSQL_PASSWORD
          valueFrom:
            secretKeyRef:
              name: sso-secrets
              key: mysql-password
        ports:
        - containerPort: 3306
        volumeMounts:
        - name: mysql-storage
          mountPath: /var/lib/mysql
        resources:
          requests:
            memory: "8Gi"
            cpu: "2000m"
          limits:
            memory: "16Gi"
            cpu: "4000m"
      volumes:
      - name: mysql-storage
        persistentVolumeClaim:
          claimName: mysql-pvc

---
apiVersion: v1
kind: Service
metadata:
  name: mysql-service
  namespace: sso-system
spec:
  selector:
    app: mysql
  ports:
  - port: 3306
    targetPort: 3306
  type: ClusterIP

---
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: mysql-pvc
  namespace: sso-system
spec:
  accessModes:
  - ReadWriteOnce
  resources:
    requests:
      storage: 100Gi
  storageClassName: fast-ssd
```

### Ingress Configuration

```yaml
# k8s/ingress.yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: sso-ingress
  namespace: sso-system
  annotations:
    kubernetes.io/ingress.class: nginx
    cert-manager.io/cluster-issuer: letsencrypt-prod
    nginx.ingress.kubernetes.io/rate-limit: "100"
    nginx.ingress.kubernetes.io/rate-limit-window: "1m"
spec:
  tls:
  - hosts:
    - sso.yourdomain.com
    - tenant1.yourdomain.com
    - tenant2.yourdomain.com
    secretName: sso-tls-secret
  rules:
  - host: sso.yourdomain.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: central-sso-service
            port:
              number: 80
  - host: tenant1.yourdomain.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: tenant1-service
            port:
              number: 80
```

## Security Configuration

### Environment Variables

```bash
# .env.production (Central SSO)
APP_NAME="Central SSO"
APP_ENV=production
APP_KEY=base64:your-32-character-secret-key
APP_DEBUG=false
APP_URL=https://sso.yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=mysql-cluster.internal
DB_PORT=3306
DB_DATABASE=sso_main
DB_USERNAME=sso_user
DB_PASSWORD=secure-random-password

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DRIVER=s3
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis-cluster.internal
REDIS_PASSWORD=secure-redis-password
REDIS_PORT=6379

JWT_SECRET=your-jwt-secret-key
JWT_TTL=3600
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=secure-email-password
MAIL_ENCRYPTION=tls

# Third-party services
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-s3-bucket

# Monitoring
TELESCOPE_ENABLED=false
SENTRY_LARAVEL_DSN=your-sentry-dsn

# Security headers
SECURE_HEADERS=true
HSTS_MAX_AGE=31536000
```

### SSL/TLS Configuration

```nginx
# nginx/prod.conf
server {
    listen 80;
    server_name sso.yourdomain.com tenant1.yourdomain.com tenant2.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name sso.yourdomain.com;
    
    ssl_certificate /etc/ssl/certs/yourdomain.com.pem;
    ssl_certificate_key /etc/ssl/private/yourdomain.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'";
    
    # Rate limiting
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
    limit_req_zone $binary_remote_addr zone=api:10m rate=100r/m;
    
    location /api/auth/login {
        limit_req zone=login burst=3 nodelay;
        proxy_pass http://central-sso:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        proxy_pass http://central-sso:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    location / {
        proxy_pass http://central-sso:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Firewall Rules

```bash
# AWS Security Groups / iptables rules
# Allow HTTP/HTTPS traffic
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# Allow database access only from application servers
iptables -A INPUT -p tcp -s 10.0.1.0/24 --dport 3306 -j ACCEPT
iptables -A INPUT -p tcp --dport 3306 -j DROP

# Allow Redis access only from application servers
iptables -A INPUT -p tcp -s 10.0.1.0/24 --dport 6379 -j ACCEPT
iptables -A INPUT -p tcp --dport 6379 -j DROP

# Block direct access to application ports
iptables -A INPUT -p tcp --dport 8000:8002 -j DROP
```

## Database Configuration

### Production MySQL Configuration

```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
# Basic settings
bind-address = 0.0.0.0
port = 3306
max_connections = 500
max_user_connections = 450

# InnoDB settings
innodb_buffer_pool_size = 8G
innodb_log_file_size = 512M
innodb_log_buffer_size = 64M
innodb_flush_log_at_trx_commit = 1
innodb_lock_wait_timeout = 50
innodb_file_per_table = 1

# Query cache
query_cache_size = 256M
query_cache_type = 1
query_cache_limit = 2M

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1

# Binary logging for replication
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
max_binlog_size = 100M
binlog_format = ROW

# Character set
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci

# Security
skip_name_resolve = 1
```

### Database Backup Strategy

```bash
#!/bin/bash
# backup-databases.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/mysql"
MYSQL_USER="backup_user"
MYSQL_PASSWORD="backup_password"
MYSQL_HOST="mysql-service"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup central database
mysqldump -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD \
    --single-transaction --routines --triggers \
    sso_main > $BACKUP_DIR/sso_main_$DATE.sql

# Backup tenant databases
for tenant in tenant1 tenant2; do
    mysqldump -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD \
        --single-transaction --routines --triggers \
        ${tenant}_db > $BACKUP_DIR/${tenant}_db_$DATE.sql
done

# Compress backups
gzip $BACKUP_DIR/*_$DATE.sql

# Upload to S3
aws s3 cp $BACKUP_DIR/ s3://your-backup-bucket/mysql/ --recursive --exclude "*" --include "*_$DATE.sql.gz"

# Clean old local backups (keep 7 days)
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

# Clean old S3 backups (keep 30 days)
aws s3 ls s3://your-backup-bucket/mysql/ | grep -E "\.sql\.gz$" | awk '$1 < "'$(date -d '30 days ago' +%Y-%m-%d)'" {print $4}' | xargs -I {} aws s3 rm s3://your-backup-bucket/mysql/{}
```

### Database Monitoring

```bash
# database-monitor.sh
#!/bin/bash

# Monitor connection count
CONNECTIONS=$(mysql -h mysql-service -u monitor -pmonitor_password -e "SHOW STATUS LIKE 'Threads_connected';" | grep Threads_connected | awk '{print $2}')
if [ $CONNECTIONS -gt 400 ]; then
    echo "HIGH CONNECTION COUNT: $CONNECTIONS" | mail -s "Database Alert" admin@yourdomain.com
fi

# Monitor slow queries
SLOW_QUERIES=$(mysql -h mysql-service -u monitor -pmonitor_password -e "SHOW STATUS LIKE 'Slow_queries';" | grep Slow_queries | awk '{print $2}')
LAST_SLOW_QUERIES=$(cat /tmp/last_slow_queries 2>/dev/null || echo 0)
if [ $SLOW_QUERIES -gt $LAST_SLOW_QUERIES ]; then
    DIFF=$((SLOW_QUERIES - LAST_SLOW_QUERIES))
    if [ $DIFF -gt 10 ]; then
        echo "SLOW QUERIES INCREASED: +$DIFF" | mail -s "Database Alert" admin@yourdomain.com
    fi
fi
echo $SLOW_QUERIES > /tmp/last_slow_queries

# Check replication status (if using replication)
SLAVE_STATUS=$(mysql -h mysql-replica -u monitor -pmonitor_password -e "SHOW SLAVE STATUS\G" | grep "Slave_IO_Running\|Slave_SQL_Running" | grep -c "Yes")
if [ $SLAVE_STATUS -ne 2 ]; then
    echo "REPLICATION ERROR" | mail -s "Database Alert" admin@yourdomain.com
fi
```

## Monitoring and Logging

### Application Performance Monitoring

```yaml
# APM with New Relic/DataDog
version: '3.8'
services:
  central-sso:
    environment:
      NEW_RELIC_LICENSE_KEY: ${NEW_RELIC_LICENSE_KEY}
      NEW_RELIC_APP_NAME: "Central SSO"
      NEW_RELIC_LOG_LEVEL: info
    volumes:
      - ./newrelic.ini:/usr/local/etc/php/conf.d/newrelic.ini
```

### Log Aggregation

```yaml
# ELK Stack for log aggregation
version: '3.8'
services:
  elasticsearch:
    image: elasticsearch:8.5.0
    environment:
      - discovery.type=single-node
      - "ES_JAVA_OPTS=-Xms2g -Xmx2g"
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data

  logstash:
    image: logstash:8.5.0
    volumes:
      - ./logstash/logstash.conf:/usr/share/logstash/pipeline/logstash.conf
    depends_on:
      - elasticsearch

  kibana:
    image: kibana:8.5.0
    ports:
      - "5601:5601"
    environment:
      - ELASTICSEARCH_HOSTS=http://elasticsearch:9200
    depends_on:
      - elasticsearch

  filebeat:
    image: elastic/filebeat:8.5.0
    volumes:
      - ./filebeat/filebeat.yml:/usr/share/filebeat/filebeat.yml
      - /var/lib/docker/containers:/var/lib/docker/containers:ro
      - /var/run/docker.sock:/var/run/docker.sock:ro
    depends_on:
      - logstash
```

### Health Checks

```php
// app/Http/Controllers/HealthController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function health()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toISOString()
        ], $healthy ? 200 : 503);
    }

    public function ready()
    {
        try {
            DB::connection()->getPdo();
            return response()->json(['status' => 'ready'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'not ready'], 503);
        }
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            $latency = $this->measureLatency(fn() => DB::select('SELECT 1'));
            return [
                'status' => 'ok',
                'latency_ms' => $latency
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function checkRedis()
    {
        try {
            $latency = $this->measureLatency(fn() => Redis::ping());
            return [
                'status' => 'ok',
                'latency_ms' => $latency
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function checkStorage()
    {
        try {
            $testFile = 'health-check-' . time() . '.txt';
            Storage::put($testFile, 'health check');
            Storage::delete($testFile);
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function measureLatency(callable $callback)
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }
}
```

## Deployment Automation

### CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]
    tags: ['v*']

env:
  REGISTRY: your-registry.azurecr.io
  NAMESPACE: sso-system

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Run Tests
        run: |
          docker-compose -f docker-compose.test.yml up --abort-on-container-exit
          docker-compose -f docker-compose.test.yml down

  build:
    needs: test
    runs-on: ubuntu-latest
    outputs:
      image-tag: ${{ steps.meta.outputs.tags }}
      image-digest: ${{ steps.build.outputs.digest }}
    steps:
      - uses: actions/checkout@v3
      
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v2
        
      - name: Login to Registry
        uses: docker/login-action@v2
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ secrets.REGISTRY_USERNAME }}
          password: ${{ secrets.REGISTRY_PASSWORD }}
          
      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v4
        with:
          images: ${{ env.REGISTRY }}/central-sso
          tags: |
            type=ref,event=branch
            type=ref,event=pr
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            
      - name: Build and push
        id: build
        uses: docker/build-push-action@v4
        with:
          context: ./central-sso
          file: ./central-sso/Dockerfile.prod
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

  deploy:
    needs: build
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    environment: production
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup kubectl
        uses: azure/setup-kubectl@v3
        
      - name: Set up Kustomize
        run: |
          curl -s "https://raw.githubusercontent.com/kubernetes-sigs/kustomize/master/hack/install_kustomize.sh" | bash
          sudo mv kustomize /usr/local/bin/
          
      - name: Deploy to Kubernetes
        run: |
          cd k8s/overlays/production
          kustomize edit set image central-sso=${{ needs.build.outputs.image-tag }}@${{ needs.build.outputs.image-digest }}
          kustomize build . | kubectl apply -f -
          kubectl rollout status deployment/central-sso -n ${{ env.NAMESPACE }}
          kubectl rollout status deployment/tenant1-app -n ${{ env.NAMESPACE }}
          
      - name: Run Post-deployment Tests
        run: |
          kubectl wait --for=condition=ready pod -l app=central-sso -n ${{ env.NAMESPACE }} --timeout=300s
          ./scripts/test-deployment.sh
```

### Blue-Green Deployment Script

```bash
#!/bin/bash
# blue-green-deploy.sh

set -e

NAMESPACE="sso-system"
SERVICE="central-sso"
NEW_VERSION=${1:-$(date +%Y%m%d-%H%M%S)}
CURRENT_DEPLOYMENT=$(kubectl get deployment -l app=$SERVICE -n $NAMESPACE -o jsonpath='{.items[0].metadata.name}')

echo "Current deployment: $CURRENT_DEPLOYMENT"
echo "Deploying version: $NEW_VERSION"

# Create new deployment
if [[ $CURRENT_DEPLOYMENT == *"blue"* ]]; then
    NEW_COLOR="green"
    OLD_COLOR="blue"
else
    NEW_COLOR="blue"
    OLD_COLOR="green"
fi

NEW_DEPLOYMENT="$SERVICE-$NEW_COLOR"
echo "Creating new deployment: $NEW_DEPLOYMENT"

# Deploy new version
kubectl patch deployment $NEW_DEPLOYMENT -n $NAMESPACE -p '{
    "spec": {
        "template": {
            "spec": {
                "containers": [{
                    "name": "central-sso",
                    "image": "your-registry/central-sso:'$NEW_VERSION'"
                }]
            }
        }
    }
}'

# Wait for new deployment to be ready
kubectl rollout status deployment/$NEW_DEPLOYMENT -n $NAMESPACE --timeout=600s

# Run health checks
echo "Running health checks..."
kubectl run health-check --image=curlimages/curl --rm -i --restart=Never -n $NAMESPACE -- \
    curl -f http://$NEW_DEPLOYMENT:80/health || {
    echo "Health check failed, rolling back..."
    kubectl rollout undo deployment/$NEW_DEPLOYMENT -n $NAMESPACE
    exit 1
}

# Switch traffic to new deployment
echo "Switching traffic to $NEW_DEPLOYMENT"
kubectl patch service $SERVICE-service -n $NAMESPACE -p '{
    "spec": {
        "selector": {
            "deployment": "'$NEW_DEPLOYMENT'"
        }
    }
}'

# Wait a bit for traffic to switch
sleep 30

# Scale down old deployment
echo "Scaling down old deployment: $SERVICE-$OLD_COLOR"
kubectl scale deployment $SERVICE-$OLD_COLOR --replicas=0 -n $NAMESPACE

echo "Deployment completed successfully!"
echo "New deployment: $NEW_DEPLOYMENT"
echo "To rollback: kubectl patch service $SERVICE-service -n $NAMESPACE -p '{\"spec\":{\"selector\":{\"deployment\":\"$SERVICE-$OLD_COLOR\"}}}'"
```

## Maintenance Procedures

### Database Maintenance

```bash
#!/bin/bash
# maintenance-tasks.sh

# Optimize tables
mysql -u root -p sso_main -e "OPTIMIZE TABLE users, tenants, tenant_users;"

# Update statistics
mysql -u root -p sso_main -e "ANALYZE TABLE users, tenants, tenant_users;"

# Clean old sessions (older than 7 days)
mysql -u root -p sso_main -e "DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY));"

# Clean expired tokens
mysql -u root -p sso_main -e "DELETE FROM personal_access_tokens WHERE expires_at < NOW() AND expires_at IS NOT NULL;"

# Clean failed jobs (older than 30 days)
mysql -u root -p sso_main -e "DELETE FROM failed_jobs WHERE failed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"
```

### Application Cache Warming

```bash
#!/bin/bash
# warm-cache.sh

ENDPOINTS=(
    "https://sso.yourdomain.com/api/health"
    "https://sso.yourdomain.com/auth/tenant1"
    "https://sso.yourdomain.com/auth/tenant2"
    "https://tenant1.yourdomain.com/health"
    "https://tenant2.yourdomain.com/health"
)

echo "Warming up application caches..."

for endpoint in "${ENDPOINTS[@]}"; do
    echo "Warming $endpoint"
    curl -s -o /dev/null -w "%{http_code}\n" "$endpoint"
done

echo "Cache warming completed"
```

This deployment guide provides comprehensive production setup instructions including Docker, Kubernetes, security configuration, monitoring, and automated deployment procedures.