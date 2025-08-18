# 📊 Mobile API Monitoring & Analytics Guide

## Overview

This guide provides comprehensive monitoring and analytics strategies for the Tenant 1 Mobile API system. It covers metrics collection, dashboard setup, alerting, performance monitoring, security analytics, and business intelligence to ensure optimal operation and user experience.

## Monitoring Philosophy

### Multi-Dimensional Monitoring

```
┌─────────────────────────────────────────────────────────────┐
│                   Monitoring Layers                        │
├─────────────────────────────────────────────────────────────┤
│ Business Metrics     │ User engagement, feature adoption   │
│ Security Analytics   │ Threats, compromises, anomalies     │
│ Performance Metrics  │ Response times, throughput, errors  │
│ Infrastructure      │ System health, resource utilization  │
│ Application Logs     │ Debug info, audit trails, events    │
└─────────────────────────────────────────────────────────────┘
```

### Key Principles

1. **Proactive Monitoring**: Detect issues before users experience them
2. **Security-First**: Monitor for threats and anomalies continuously
3. **User-Centric**: Focus on metrics that impact user experience
4. **Actionable Insights**: Provide data that drives decision-making
5. **Real-Time Awareness**: Critical metrics updated in real-time

---

## 🔍 Metrics Collection

### 1. Backend Metrics (Laravel)

#### Custom Metrics Service

```php
<?php
// app/Services/MetricsService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MetricsService
{
    /**
     * Record API request metrics
     */
    public function recordApiRequest(
        string $endpoint,
        string $method,
        int $statusCode,
        float $responseTime,
        ?int $userId = null,
        ?string $deviceId = null
    ): void {
        // Store detailed metrics in database
        DB::table('mobile_api_logs')->insert([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'response_time_ms' => round($responseTime * 1000),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update real-time counters in cache
        $this->updateRealTimeMetrics($endpoint, $method, $statusCode, $responseTime);
    }

    /**
     * Record authentication events
     */
    public function recordAuthEvent(
        string $eventType,
        bool $success,
        string $email,
        ?string $deviceId = null,
        ?string $failureReason = null
    ): void {
        $metricKey = "auth_events:" . date('Y-m-d-H');
        
        Cache::increment("{$metricKey}:total");
        Cache::increment("{$metricKey}:{$eventType}");
        
        if ($success) {
            Cache::increment("{$metricKey}:success");
        } else {
            Cache::increment("{$metricKey}:failure");
            
            // Track failure reasons
            if ($failureReason) {
                Cache::increment("{$metricKey}:failure_reason:{$failureReason}");
            }
        }

        // Track unique users and devices
        Cache::sadd("{$metricKey}:unique_users", $email);
        if ($deviceId) {
            Cache::sadd("{$metricKey}:unique_devices", $deviceId);
        }
    }

    /**
     * Record security events
     */
    public function recordSecurityEvent(
        string $eventType,
        string $severity,
        array $eventData,
        ?int $userId = null,
        ?string $deviceId = null
    ): void {
        // Store in security events table
        DB::table('security_events')->insert([
            'event_type' => $eventType,
            'severity' => $severity,
            'user_id' => $userId,
            'device_id' => $deviceId,
            'ip_address' => request()->ip(),
            'event_data' => json_encode($eventData),
            'created_at' => now()
        ]);

        // Update security metrics
        $metricKey = "security_events:" . date('Y-m-d-H');
        Cache::increment("{$metricKey}:total");
        Cache::increment("{$metricKey}:{$eventType}");
        Cache::increment("{$metricKey}:severity:{$severity}");

        // Alert on critical events
        if ($severity === 'critical' || $severity === 'high') {
            $this->triggerSecurityAlert($eventType, $eventData);
        }
    }

    /**
     * Get real-time metrics dashboard data
     */
    public function getDashboardMetrics(): array
    {
        $currentHour = date('Y-m-d-H');
        $lastHour = date('Y-m-d-H', strtotime('-1 hour'));

        return [
            'api_metrics' => $this->getApiMetrics($currentHour),
            'auth_metrics' => $this->getAuthMetrics($currentHour),
            'security_metrics' => $this->getSecurityMetrics($currentHour),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'user_metrics' => $this->getUserMetrics($currentHour),
            'device_metrics' => $this->getDeviceMetrics($currentHour),
            'trends' => $this->getTrendComparison($currentHour, $lastHour)
        ];
    }

    private function updateRealTimeMetrics(
        string $endpoint,
        string $method,
        int $statusCode,
        float $responseTime
    ): void {
        $metricKey = "api_metrics:" . date('Y-m-d-H');
        
        // Request counts
        Cache::increment("{$metricKey}:requests:total");
        Cache::increment("{$metricKey}:requests:{$method}");
        Cache::increment("{$metricKey}:status:{$statusCode}");
        Cache::increment("{$metricKey}:endpoint:" . str_replace('/', '_', $endpoint));

        // Response time tracking
        $responseTimeMs = round($responseTime * 1000);
        Cache::lpush("{$metricKey}:response_times", $responseTimeMs);
        Cache::ltrim("{$metricKey}:response_times", 0, 999); // Keep last 1000 samples

        // Error rate tracking
        if ($statusCode >= 400) {
            Cache::increment("{$metricKey}:errors:total");
            Cache::increment("{$metricKey}:errors:{$statusCode}");
        }
    }

    private function getApiMetrics(string $timeKey): array
    {
        return [
            'total_requests' => Cache::get("{$timeKey}:requests:total", 0),
            'requests_by_method' => [
                'GET' => Cache::get("{$timeKey}:requests:GET", 0),
                'POST' => Cache::get("{$timeKey}:requests:POST", 0),
                'PUT' => Cache::get("{$timeKey}:requests:PUT", 0),
                'DELETE' => Cache::get("{$timeKey}:requests:DELETE", 0)
            ],
            'status_codes' => [
                '2xx' => Cache::get("{$timeKey}:status:200", 0) + 
                        Cache::get("{$timeKey}:status:201", 0),
                '4xx' => Cache::get("{$timeKey}:status:400", 0) + 
                        Cache::get("{$timeKey}:status:401", 0) + 
                        Cache::get("{$timeKey}:status:403", 0) + 
                        Cache::get("{$timeKey}:status:404", 0),
                '5xx' => Cache::get("{$timeKey}:status:500", 0) + 
                        Cache::get("{$timeKey}:status:502", 0)
            ],
            'error_rate' => $this->calculateErrorRate($timeKey),
            'avg_response_time' => $this->calculateAvgResponseTime($timeKey)
        ];
    }

    private function getAuthMetrics(string $timeKey): array
    {
        return [
            'total_attempts' => Cache::get("auth_events:{$timeKey}:total", 0),
            'successful_logins' => Cache::get("auth_events:{$timeKey}:success", 0),
            'failed_logins' => Cache::get("auth_events:{$timeKey}:failure", 0),
            'unique_users' => Cache::scard("auth_events:{$timeKey}:unique_users"),
            'unique_devices' => Cache::scard("auth_events:{$timeKey}:unique_devices"),
            'login_methods' => [
                'direct' => Cache::get("auth_events:{$timeKey}:mobile_direct", 0),
                'oauth' => Cache::get("auth_events:{$timeKey}:mobile_oauth", 0)
            ],
            'success_rate' => $this->calculateAuthSuccessRate($timeKey)
        ];
    }

    private function getSecurityMetrics(string $timeKey): array
    {
        return [
            'total_events' => Cache::get("security_events:{$timeKey}:total", 0),
            'by_severity' => [
                'low' => Cache::get("security_events:{$timeKey}:severity:low", 0),
                'medium' => Cache::get("security_events:{$timeKey}:severity:medium", 0),
                'high' => Cache::get("security_events:{$timeKey}:severity:high", 0),
                'critical' => Cache::get("security_events:{$timeKey}:severity:critical", 0)
            ],
            'by_type' => [
                'compromised_device' => Cache::get("security_events:{$timeKey}:device_compromise", 0),
                'invalid_signature' => Cache::get("security_events:{$timeKey}:invalid_signature", 0),
                'rate_limit_exceeded' => Cache::get("security_events:{$timeKey}:rate_limit_exceeded", 0),
                'suspicious_activity' => Cache::get("security_events:{$timeKey}:suspicious_activity", 0)
            ]
        ];
    }

    private function calculateErrorRate(string $timeKey): float
    {
        $totalRequests = Cache::get("{$timeKey}:requests:total", 0);
        $totalErrors = Cache::get("{$timeKey}:errors:total", 0);
        
        return $totalRequests > 0 ? round(($totalErrors / $totalRequests) * 100, 2) : 0;
    }

    private function calculateAvgResponseTime(string $timeKey): float
    {
        $responseTimes = Cache::lrange("{$timeKey}:response_times", 0, -1);
        
        if (empty($responseTimes)) {
            return 0;
        }

        $sum = array_sum(array_map('floatval', $responseTimes));
        return round($sum / count($responseTimes), 2);
    }

    private function triggerSecurityAlert(string $eventType, array $eventData): void
    {
        // Implementation depends on your alerting system
        Log::critical("Security Alert: {$eventType}", $eventData);
        
        // Could integrate with:
        // - Slack webhooks
        // - Email alerts
        // - PagerDuty
        // - SMS notifications
    }
}
```

#### Middleware for Automatic Metrics Collection

```php
<?php
// app/Http/Middleware/MetricsMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MetricsService;

class MetricsMiddleware
{
    private $metricsService;

    public function __construct(MetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        // Record metrics
        $this->metricsService->recordApiRequest(
            endpoint: $request->path(),
            method: $request->method(),
            statusCode: $response->getStatusCode(),
            responseTime: $responseTime,
            userId: $request->user()->id ?? null,
            deviceId: $request->header('X-Device-Id')
        );

        return $response;
    }
}
```

### 2. Database Analytics Queries

#### Performance Analytics

```sql
-- Daily API performance summary
SELECT 
    DATE(created_at) as date,
    endpoint,
    COUNT(*) as request_count,
    AVG(response_time_ms) as avg_response_time,
    MAX(response_time_ms) as max_response_time,
    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count,
    (COUNT(CASE WHEN status_code >= 400 THEN 1 END) * 100.0 / COUNT(*)) as error_rate
FROM mobile_api_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at), endpoint
ORDER BY date DESC, request_count DESC;

-- Top slow endpoints
SELECT 
    endpoint,
    method,
    COUNT(*) as request_count,
    AVG(response_time_ms) as avg_response_time,
    PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY response_time_ms) as p95_response_time
FROM mobile_api_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY endpoint, method
HAVING COUNT(*) > 10
ORDER BY avg_response_time DESC
LIMIT 10;

-- Error analysis
SELECT 
    status_code,
    endpoint,
    COUNT(*) as error_count,
    COUNT(DISTINCT user_id) as affected_users,
    COUNT(DISTINCT device_id) as affected_devices
FROM mobile_api_logs 
WHERE status_code >= 400 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY status_code, endpoint
ORDER BY error_count DESC;
```

#### Security Analytics

```sql
-- Security events summary
SELECT 
    event_type,
    severity,
    COUNT(*) as event_count,
    COUNT(DISTINCT user_id) as affected_users,
    COUNT(DISTINCT device_id) as affected_devices,
    COUNT(DISTINCT ip_address) as unique_ips
FROM security_events 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY event_type, severity
ORDER BY event_count DESC;

-- Failed login analysis
SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
    COUNT(*) as failed_attempts,
    COUNT(DISTINCT JSON_EXTRACT(event_data, '$.email')) as unique_emails,
    COUNT(DISTINCT ip_address) as unique_ips
FROM security_events 
WHERE event_type = 'mobile_auth_failure'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
ORDER BY hour;

-- Compromised device detection
SELECT 
    JSON_EXTRACT(event_data, '$.indicators') as compromise_type,
    COUNT(*) as detection_count,
    COUNT(DISTINCT device_id) as unique_devices
FROM security_events 
WHERE event_type = 'device_compromise'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY JSON_EXTRACT(event_data, '$.indicators')
ORDER BY detection_count DESC;
```

#### User Behavior Analytics

```sql
-- User engagement metrics
SELECT 
    DATE(created_at) as date,
    COUNT(DISTINCT user_id) as daily_active_users,
    COUNT(*) as total_requests,
    AVG(COUNT(*)) OVER (ORDER BY DATE(created_at) ROWS BETWEEN 6 PRECEDING AND CURRENT ROW) as avg_requests_7day
FROM mobile_api_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND user_id IS NOT NULL
GROUP BY DATE(created_at)
ORDER BY date;

-- Device usage patterns
SELECT 
    JSON_EXTRACT(device_info, '$.device_type') as device_type,
    JSON_EXTRACT(device_info, '$.os_version') as os_version,
    COUNT(DISTINCT device_id) as device_count,
    COUNT(DISTINCT user_id) as user_count,
    AVG(TIMESTAMPDIFF(DAY, created_at, last_seen_at)) as avg_session_days
FROM mobile_devices 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY device_type, os_version
ORDER BY device_count DESC;

-- Feature adoption
SELECT 
    CASE 
        WHEN endpoint LIKE '%/profile%' THEN 'Profile Management'
        WHEN endpoint LIKE '%/auth%' THEN 'Authentication'
        WHEN endpoint LIKE '%/devices%' THEN 'Device Management'
        ELSE 'Other'
    END as feature_category,
    COUNT(*) as usage_count,
    COUNT(DISTINCT user_id) as unique_users
FROM mobile_api_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND user_id IS NOT NULL
GROUP BY feature_category
ORDER BY usage_count DESC;
```

---

## 📈 Dashboard Setup

### 1. Real-Time Metrics Dashboard (Laravel)

```php
<?php
// app/Http/Controllers/MetricsDashboardController.php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Illuminate\Http\Request;

class MetricsDashboardController extends Controller
{
    private $metricsService;

    public function __construct(MetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Get dashboard data for the monitoring interface
     */
    public function getDashboardData(Request $request)
    {
        $timeRange = $request->input('timeRange', '1h');
        
        return response()->json([
            'timestamp' => now()->toISOString(),
            'timeRange' => $timeRange,
            'metrics' => $this->metricsService->getDashboardMetrics(),
            'alerts' => $this->getActiveAlerts(),
            'health' => $this->getSystemHealth()
        ]);
    }

    /**
     * Get detailed metrics for a specific time range
     */
    public function getMetricsHistory(Request $request)
    {
        $startTime = $request->input('start', now()->subDay());
        $endTime = $request->input('end', now());
        $granularity = $request->input('granularity', 'hour');

        return response()->json([
            'api_performance' => $this->getApiPerformanceHistory($startTime, $endTime, $granularity),
            'auth_metrics' => $this->getAuthMetricsHistory($startTime, $endTime, $granularity),
            'security_events' => $this->getSecurityEventsHistory($startTime, $endTime, $granularity),
            'user_activity' => $this->getUserActivityHistory($startTime, $endTime, $granularity)
        ]);
    }

    private function getActiveAlerts(): array
    {
        // Check for active alerts
        $alerts = [];

        // High error rate alert
        $errorRate = $this->metricsService->getErrorRate('1h');
        if ($errorRate > 5) {
            $alerts[] = [
                'type' => 'error_rate',
                'severity' => $errorRate > 10 ? 'critical' : 'warning',
                'message' => "High error rate: {$errorRate}%",
                'value' => $errorRate,
                'threshold' => 5
            ];
        }

        // Slow response time alert
        $avgResponseTime = $this->metricsService->getAvgResponseTime('1h');
        if ($avgResponseTime > 1000) {
            $alerts[] = [
                'type' => 'response_time',
                'severity' => $avgResponseTime > 2000 ? 'critical' : 'warning',
                'message' => "Slow response time: {$avgResponseTime}ms",
                'value' => $avgResponseTime,
                'threshold' => 1000
            ];
        }

        // Security events alert
        $securityEvents = $this->metricsService->getSecurityEventsCount('1h');
        if ($securityEvents > 10) {
            $alerts[] = [
                'type' => 'security_events',
                'severity' => 'warning',
                'message' => "High security event volume: {$securityEvents} events",
                'value' => $securityEvents,
                'threshold' => 10
            ];
        }

        return $alerts;
    }

    private function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'external_services' => $this->checkExternalServices()
        ];
    }
}
```

### 2. Frontend Dashboard (Vue.js)

```vue
<!-- resources/js/components/MetricsDashboard.vue -->
<template>
  <div class="metrics-dashboard">
    <div class="dashboard-header">
      <h1>Mobile API Monitoring Dashboard</h1>
      <div class="time-range-selector">
        <select v-model="selectedTimeRange" @change="updateMetrics">
          <option value="1h">Last Hour</option>
          <option value="24h">Last 24 Hours</option>
          <option value="7d">Last 7 Days</option>
          <option value="30d">Last 30 Days</option>
        </select>
      </div>
    </div>

    <!-- Alerts Section -->
    <div v-if="alerts.length > 0" class="alerts-section">
      <div 
        v-for="alert in alerts" 
        :key="alert.type" 
        :class="['alert', `alert-${alert.severity}`]"
      >
        <i :class="getAlertIcon(alert.severity)"></i>
        {{ alert.message }}
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <h3>Total Requests</h3>
        <div class="kpi-value">{{ formatNumber(metrics.api_metrics?.total_requests || 0) }}</div>
        <div class="kpi-trend" :class="getTrendClass(trends.requests)">
          {{ formatTrend(trends.requests) }}
        </div>
      </div>

      <div class="kpi-card">
        <h3>Success Rate</h3>
        <div class="kpi-value">{{ formatPercentage(calculateSuccessRate()) }}</div>
        <div class="kpi-trend" :class="getTrendClass(trends.success_rate)">
          {{ formatTrend(trends.success_rate) }}
        </div>
      </div>

      <div class="kpi-card">
        <h3>Avg Response Time</h3>
        <div class="kpi-value">{{ metrics.api_metrics?.avg_response_time || 0 }}ms</div>
        <div class="kpi-trend" :class="getTrendClass(trends.response_time)">
          {{ formatTrend(trends.response_time) }}
        </div>
      </div>

      <div class="kpi-card">
        <h3>Active Users</h3>
        <div class="kpi-value">{{ metrics.user_metrics?.active_users || 0 }}</div>
        <div class="kpi-trend" :class="getTrendClass(trends.active_users)">
          {{ formatTrend(trends.active_users) }}
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-grid">
      <!-- API Performance Chart -->
      <div class="chart-container">
        <h3>API Performance</h3>
        <canvas ref="performanceChart"></canvas>
      </div>

      <!-- Authentication Metrics Chart -->
      <div class="chart-container">
        <h3>Authentication Activity</h3>
        <canvas ref="authChart"></canvas>
      </div>

      <!-- Security Events Chart -->
      <div class="chart-container">
        <h3>Security Events</h3>
        <canvas ref="securityChart"></canvas>
      </div>

      <!-- Device Distribution Chart -->
      <div class="chart-container">
        <h3>Device Distribution</h3>
        <canvas ref="deviceChart"></canvas>
      </div>
    </div>

    <!-- Tables Section -->
    <div class="tables-grid">
      <!-- Top Endpoints -->
      <div class="table-container">
        <h3>Top API Endpoints</h3>
        <table class="metrics-table">
          <thead>
            <tr>
              <th>Endpoint</th>
              <th>Requests</th>
              <th>Avg Response Time</th>
              <th>Error Rate</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="endpoint in topEndpoints" :key="endpoint.path">
              <td>{{ endpoint.path }}</td>
              <td>{{ formatNumber(endpoint.requests) }}</td>
              <td>{{ endpoint.avg_response_time }}ms</td>
              <td :class="getErrorRateClass(endpoint.error_rate)">
                {{ formatPercentage(endpoint.error_rate) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Security Events -->
      <div class="table-container">
        <h3>Recent Security Events</h3>
        <table class="metrics-table">
          <thead>
            <tr>
              <th>Time</th>
              <th>Event Type</th>
              <th>Severity</th>
              <th>Device ID</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="event in recentSecurityEvents" :key="event.id">
              <td>{{ formatTime(event.created_at) }}</td>
              <td>{{ event.event_type }}</td>
              <td :class="getSeverityClass(event.severity)">
                {{ event.severity }}
              </td>
              <td>{{ event.device_id || 'N/A' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';
import axios from 'axios';

export default {
  name: 'MetricsDashboard',
  data() {
    return {
      metrics: {},
      alerts: [],
      trends: {},
      selectedTimeRange: '1h',
      topEndpoints: [],
      recentSecurityEvents: [],
      charts: {},
      refreshInterval: null
    };
  },
  mounted() {
    this.initializeDashboard();
    this.startAutoRefresh();
  },
  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
    this.destroyCharts();
  },
  methods: {
    async initializeDashboard() {
      await this.updateMetrics();
      this.initializeCharts();
    },

    async updateMetrics() {
      try {
        const response = await axios.get('/api/metrics/dashboard', {
          params: { timeRange: this.selectedTimeRange }
        });
        
        this.metrics = response.data.metrics;
        this.alerts = response.data.alerts;
        this.trends = response.data.trends;
        
        this.updateCharts();
      } catch (error) {
        console.error('Failed to fetch metrics:', error);
      }
    },

    initializeCharts() {
      // Performance Chart
      this.charts.performance = new Chart(this.$refs.performanceChart, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Response Time (ms)',
            data: [],
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
          }, {
            label: 'Request Count',
            data: [],
            borderColor: 'rgb(255, 99, 132)',
            yAxisID: 'y1'
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              type: 'linear',
              display: true,
              position: 'left',
            },
            y1: {
              type: 'linear',
              display: true,
              position: 'right',
              grid: {
                drawOnChartArea: false,
              },
            }
          }
        }
      });

      // Auth Chart
      this.charts.auth = new Chart(this.$refs.authChart, {
        type: 'doughnut',
        data: {
          labels: ['Successful', 'Failed'],
          datasets: [{
            data: [],
            backgroundColor: ['#4CAF50', '#F44336']
          }]
        },
        options: {
          responsive: true
        }
      });

      // Security Chart
      this.charts.security = new Chart(this.$refs.securityChart, {
        type: 'bar',
        data: {
          labels: ['Low', 'Medium', 'High', 'Critical'],
          datasets: [{
            label: 'Security Events',
            data: [],
            backgroundColor: ['#4CAF50', '#FF9800', '#F44336', '#9C27B0']
          }]
        },
        options: {
          responsive: true
        }
      });

      // Device Chart
      this.charts.device = new Chart(this.$refs.deviceChart, {
        type: 'pie',
        data: {
          labels: ['iOS', 'Android', 'Other'],
          datasets: [{
            data: [],
            backgroundColor: ['#007AFF', '#34C759', '#FF3B30']
          }]
        },
        options: {
          responsive: true
        }
      });
    },

    updateCharts() {
      // Update performance chart
      if (this.charts.performance && this.metrics.performance_history) {
        this.charts.performance.data.labels = this.metrics.performance_history.map(p => p.time);
        this.charts.performance.data.datasets[0].data = this.metrics.performance_history.map(p => p.avg_response_time);
        this.charts.performance.data.datasets[1].data = this.metrics.performance_history.map(p => p.request_count);
        this.charts.performance.update();
      }

      // Update auth chart
      if (this.charts.auth && this.metrics.auth_metrics) {
        this.charts.auth.data.datasets[0].data = [
          this.metrics.auth_metrics.successful_logins,
          this.metrics.auth_metrics.failed_logins
        ];
        this.charts.auth.update();
      }

      // Update security chart
      if (this.charts.security && this.metrics.security_metrics) {
        const severity = this.metrics.security_metrics.by_severity;
        this.charts.security.data.datasets[0].data = [
          severity.low,
          severity.medium,
          severity.high,
          severity.critical
        ];
        this.charts.security.update();
      }

      // Update device chart
      if (this.charts.device && this.metrics.device_metrics) {
        this.charts.device.data.datasets[0].data = [
          this.metrics.device_metrics.ios,
          this.metrics.device_metrics.android,
          this.metrics.device_metrics.other
        ];
        this.charts.device.update();
      }
    },

    startAutoRefresh() {
      this.refreshInterval = setInterval(() => {
        this.updateMetrics();
      }, 30000); // Refresh every 30 seconds
    },

    destroyCharts() {
      Object.values(this.charts).forEach(chart => {
        if (chart) chart.destroy();
      });
    },

    calculateSuccessRate() {
      const total = this.metrics.api_metrics?.total_requests || 0;
      const errors = this.metrics.api_metrics?.status_codes?.['4xx'] + 
                    this.metrics.api_metrics?.status_codes?.['5xx'] || 0;
      return total > 0 ? ((total - errors) / total) * 100 : 100;
    },

    formatNumber(num) {
      return new Intl.NumberFormat().format(num);
    },

    formatPercentage(num) {
      return `${num.toFixed(1)}%`;
    },

    formatTrend(trend) {
      if (!trend) return '';
      const sign = trend > 0 ? '+' : '';
      return `${sign}${trend.toFixed(1)}%`;
    },

    formatTime(timestamp) {
      return new Date(timestamp).toLocaleTimeString();
    },

    getTrendClass(trend) {
      if (!trend) return '';
      return trend > 0 ? 'trend-up' : 'trend-down';
    },

    getAlertIcon(severity) {
      switch (severity) {
        case 'critical': return 'fas fa-exclamation-triangle';
        case 'warning': return 'fas fa-exclamation-circle';
        default: return 'fas fa-info-circle';
      }
    },

    getErrorRateClass(rate) {
      if (rate > 10) return 'error-rate-high';
      if (rate > 5) return 'error-rate-medium';
      return 'error-rate-low';
    },

    getSeverityClass(severity) {
      return `severity-${severity}`;
    }
  }
};
</script>

<style scoped>
.metrics-dashboard {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.alerts-section {
  margin-bottom: 20px;
}

.alert {
  padding: 10px 15px;
  margin-bottom: 10px;
  border-radius: 5px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.alert-critical {
  background-color: #ffebee;
  border-left: 4px solid #f44336;
  color: #c62828;
}

.alert-warning {
  background-color: #fff3e0;
  border-left: 4px solid #ff9800;
  color: #e65100;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.kpi-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.kpi-value {
  font-size: 2.5em;
  font-weight: bold;
  margin: 10px 0;
}

.kpi-trend {
  font-size: 0.9em;
  font-weight: 500;
}

.trend-up {
  color: #4CAF50;
}

.trend-down {
  color: #F44336;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.chart-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tables-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: 20px;
}

.table-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.metrics-table {
  width: 100%;
  border-collapse: collapse;
}

.metrics-table th,
.metrics-table td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

.metrics-table th {
  background-color: #f5f5f5;
  font-weight: 600;
}

.error-rate-high {
  color: #f44336;
  font-weight: bold;
}

.error-rate-medium {
  color: #ff9800;
  font-weight: bold;
}

.error-rate-low {
  color: #4caf50;
}

.severity-critical {
  color: #9c27b0;
  font-weight: bold;
}

.severity-high {
  color: #f44336;
  font-weight: bold;
}

.severity-medium {
  color: #ff9800;
}

.severity-low {
  color: #4caf50;
}
</style>
```

---

## 🚨 Alerting System

### 1. Alert Configuration

```php
<?php
// config/monitoring.php

return [
    'alerts' => [
        'error_rate' => [
            'warning_threshold' => 5.0,
            'critical_threshold' => 10.0,
            'time_window' => '5m'
        ],
        'response_time' => [
            'warning_threshold' => 1000, // ms
            'critical_threshold' => 2000, // ms
            'time_window' => '5m'
        ],
        'security_events' => [
            'warning_threshold' => 10,
            'critical_threshold' => 25,
            'time_window' => '1h'
        ],
        'auth_failure_rate' => [
            'warning_threshold' => 20.0,
            'critical_threshold' => 50.0,
            'time_window' => '10m'
        ]
    ],
    
    'notification_channels' => [
        'slack' => [
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_ALERT_CHANNEL', '#alerts'),
            'username' => 'Monitoring Bot'
        ],
        'email' => [
            'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', '')),
            'from' => env('MAIL_FROM_ADDRESS')
        ],
        'webhook' => [
            'url' => env('ALERT_WEBHOOK_URL'),
            'secret' => env('ALERT_WEBHOOK_SECRET')
        ]
    ]
];
```

### 2. Alert Manager

```php
<?php
// app/Services/AlertManager.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AlertManager
{
    private $config;

    public function __construct()
    {
        $this->config = config('monitoring');
    }

    /**
     * Check all alert conditions and trigger notifications
     */
    public function checkAlerts(): void
    {
        $alerts = [];

        // Check error rate
        $errorRate = $this->getErrorRate('5m');
        if ($errorRate >= $this->config['alerts']['error_rate']['critical_threshold']) {
            $alerts[] = $this->createAlert('error_rate', 'critical', [
                'current_value' => $errorRate,
                'threshold' => $this->config['alerts']['error_rate']['critical_threshold'],
                'message' => "Critical error rate: {$errorRate}%"
            ]);
        } elseif ($errorRate >= $this->config['alerts']['error_rate']['warning_threshold']) {
            $alerts[] = $this->createAlert('error_rate', 'warning', [
                'current_value' => $errorRate,
                'threshold' => $this->config['alerts']['error_rate']['warning_threshold'],
                'message' => "High error rate: {$errorRate}%"
            ]);
        }

        // Check response time
        $avgResponseTime = $this->getAvgResponseTime('5m');
        if ($avgResponseTime >= $this->config['alerts']['response_time']['critical_threshold']) {
            $alerts[] = $this->createAlert('response_time', 'critical', [
                'current_value' => $avgResponseTime,
                'threshold' => $this->config['alerts']['response_time']['critical_threshold'],
                'message' => "Critical response time: {$avgResponseTime}ms"
            ]);
        } elseif ($avgResponseTime >= $this->config['alerts']['response_time']['warning_threshold']) {
            $alerts[] = $this->createAlert('response_time', 'warning', [
                'current_value' => $avgResponseTime,
                'threshold' => $this->config['alerts']['response_time']['warning_threshold'],
                'message' => "Slow response time: {$avgResponseTime}ms"
            ]);
        }

        // Check security events
        $securityEvents = $this->getSecurityEventsCount('1h');
        if ($securityEvents >= $this->config['alerts']['security_events']['critical_threshold']) {
            $alerts[] = $this->createAlert('security_events', 'critical', [
                'current_value' => $securityEvents,
                'threshold' => $this->config['alerts']['security_events']['critical_threshold'],
                'message' => "Critical security event volume: {$securityEvents} events"
            ]);
        } elseif ($securityEvents >= $this->config['alerts']['security_events']['warning_threshold']) {
            $alerts[] = $this->createAlert('security_events', 'warning', [
                'current_value' => $securityEvents,
                'threshold' => $this->config['alerts']['security_events']['warning_threshold'],
                'message' => "High security event volume: {$securityEvents} events"
            ]);
        }

        // Send alerts
        foreach ($alerts as $alert) {
            $this->sendAlert($alert);
        }
    }

    /**
     * Send alert to configured notification channels
     */
    public function sendAlert(array $alert): void
    {
        // Send to Slack
        if ($this->config['notification_channels']['slack']['webhook_url']) {
            $this->sendSlackAlert($alert);
        }

        // Send email
        if (!empty($this->config['notification_channels']['email']['recipients'])) {
            $this->sendEmailAlert($alert);
        }

        // Send webhook
        if ($this->config['notification_channels']['webhook']['url']) {
            $this->sendWebhookAlert($alert);
        }

        // Log alert
        Log::channel('monitoring')->warning('Alert triggered', $alert);
    }

    private function sendSlackAlert(array $alert): void
    {
        $webhookUrl = $this->config['notification_channels']['slack']['webhook_url'];
        $channel = $this->config['notification_channels']['slack']['channel'];
        
        $color = $alert['severity'] === 'critical' ? 'danger' : 'warning';
        $emoji = $alert['severity'] === 'critical' ? ':rotating_light:' : ':warning:';
        
        $payload = [
            'channel' => $channel,
            'username' => 'Mobile API Monitor',
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "{$emoji} Mobile API Alert",
                    'fields' => [
                        [
                            'title' => 'Alert Type',
                            'value' => $alert['type'],
                            'short' => true
                        ],
                        [
                            'title' => 'Severity',
                            'value' => strtoupper($alert['severity']),
                            'short' => true
                        ],
                        [
                            'title' => 'Message',
                            'value' => $alert['data']['message'],
                            'short' => false
                        ],
                        [
                            'title' => 'Current Value',
                            'value' => $alert['data']['current_value'],
                            'short' => true
                        ],
                        [
                            'title' => 'Threshold',
                            'value' => $alert['data']['threshold'],
                            'short' => true
                        ]
                    ],
                    'footer' => 'Mobile API Monitoring',
                    'ts' => time()
                ]
            ]
        ];

        Http::post($webhookUrl, $payload);
    }

    private function sendEmailAlert(array $alert): void
    {
        $recipients = $this->config['notification_channels']['email']['recipients'];
        
        foreach ($recipients as $recipient) {
            Mail::raw($alert['data']['message'], function ($message) use ($recipient, $alert) {
                $message->to($recipient)
                    ->subject("Mobile API Alert: {$alert['type']} ({$alert['severity']})")
                    ->from($this->config['notification_channels']['email']['from']);
            });
        }
    }

    private function sendWebhookAlert(array $alert): void
    {
        $url = $this->config['notification_channels']['webhook']['url'];
        $secret = $this->config['notification_channels']['webhook']['secret'];
        
        $payload = [
            'alert' => $alert,
            'timestamp' => now()->toISOString(),
            'source' => 'mobile-api-monitoring'
        ];
        
        $signature = hash_hmac('sha256', json_encode($payload), $secret);
        
        Http::withHeaders([
            'X-Signature' => $signature,
            'X-Timestamp' => time()
        ])->post($url, $payload);
    }

    private function createAlert(string $type, string $severity, array $data): array
    {
        return [
            'id' => uniqid(),
            'type' => $type,
            'severity' => $severity,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ];
    }

    // Metric calculation methods (implement based on your metrics service)
    private function getErrorRate(string $timeWindow): float
    {
        // Implementation
        return 0.0;
    }

    private function getAvgResponseTime(string $timeWindow): float
    {
        // Implementation
        return 0.0;
    }

    private function getSecurityEventsCount(string $timeWindow): int
    {
        // Implementation
        return 0;
    }
}
```

---

## 📱 Mobile Analytics

### 1. Mobile-Specific Metrics

```php
<?php
// app/Services/MobileAnalyticsService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MobileAnalyticsService
{
    /**
     * Get mobile app usage analytics
     */
    public function getUsageAnalytics(string $timeRange = '7d'): array
    {
        $startDate = $this->getStartDate($timeRange);

        return [
            'user_engagement' => $this->getUserEngagementMetrics($startDate),
            'device_analytics' => $this->getDeviceAnalytics($startDate),
            'feature_adoption' => $this->getFeatureAdoptionMetrics($startDate),
            'session_analytics' => $this->getSessionAnalytics($startDate),
            'performance_by_device' => $this->getPerformanceByDevice($startDate),
            'crash_analytics' => $this->getCrashAnalytics($startDate)
        ];
    }

    private function getUserEngagementMetrics($startDate): array
    {
        $dailyActiveUsers = DB::table('mobile_api_logs')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(DISTINCT user_id) as dau'))
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $monthlyActiveUsers = DB::table('mobile_api_logs')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count();

        $sessionMetrics = DB::table('mobile_api_logs')
            ->select(
                DB::raw('user_id'),
                DB::raw('COUNT(*) as requests_per_session'),
                DB::raw('TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as session_duration_minutes')
            )
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->groupBy('user_id', DB::raw('DATE(created_at)'))
            ->get();

        return [
            'daily_active_users' => $dailyActiveUsers,
            'monthly_active_users' => $monthlyActiveUsers,
            'avg_session_duration' => $sessionMetrics->avg('session_duration_minutes'),
            'avg_requests_per_session' => $sessionMetrics->avg('requests_per_session')
        ];
    }

    private function getDeviceAnalytics($startDate): array
    {
        $deviceDistribution = DB::table('mobile_devices')
            ->select(
                'device_type',
                DB::raw('COUNT(*) as device_count'),
                DB::raw('COUNT(DISTINCT user_id) as user_count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('device_type')
            ->get();

        $osVersions = DB::table('mobile_devices')
            ->select(
                'device_type',
                'os_version',
                DB::raw('COUNT(*) as device_count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('device_type', 'os_version')
            ->orderBy('device_count', 'desc')
            ->get();

        $appVersions = DB::table('mobile_devices')
            ->select(
                'app_version',
                DB::raw('COUNT(*) as device_count'),
                DB::raw('COUNT(DISTINCT user_id) as user_count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('app_version')
            ->orderBy('device_count', 'desc')
            ->get();

        return [
            'device_distribution' => $deviceDistribution,
            'os_versions' => $osVersions,
            'app_versions' => $appVersions
        ];
    }

    private function getFeatureAdoptionMetrics($startDate): array
    {
        $featureUsage = DB::table('mobile_api_logs')
            ->select(
                DB::raw('
                    CASE 
                        WHEN endpoint LIKE "%/auth%" THEN "Authentication"
                        WHEN endpoint LIKE "%/profile%" THEN "Profile Management" 
                        WHEN endpoint LIKE "%/devices%" THEN "Device Management"
                        ELSE "Other"
                    END as feature
                '),
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->groupBy('feature')
            ->orderBy('usage_count', 'desc')
            ->get();

        return [
            'feature_usage' => $featureUsage
        ];
    }

    private function getSessionAnalytics($startDate): array
    {
        $sessionLengths = DB::table('mobile_api_logs')
            ->select(
                DB::raw('user_id'),
                DB::raw('device_id'),
                DB::raw('DATE(created_at) as session_date'),
                DB::raw('MIN(created_at) as session_start'),
                DB::raw('MAX(created_at) as session_end'),
                DB::raw('TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as duration_minutes'),
                DB::raw('COUNT(*) as request_count')
            )
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->groupBy('user_id', 'device_id', DB::raw('DATE(created_at)'))
            ->having('duration_minutes', '>', 0)
            ->get();

        return [
            'avg_session_duration' => $sessionLengths->avg('duration_minutes'),
            'avg_requests_per_session' => $sessionLengths->avg('request_count'),
            'session_distribution' => $sessionLengths->groupBy(function ($session) {
                if ($session->duration_minutes < 5) return '0-5 min';
                if ($session->duration_minutes < 15) return '5-15 min';
                if ($session->duration_minutes < 30) return '15-30 min';
                return '30+ min';
            })->map->count()
        ];
    }

    private function getPerformanceByDevice($startDate): array
    {
        return DB::table('mobile_api_logs')
            ->join('mobile_devices', 'mobile_api_logs.device_id', '=', 'mobile_devices.device_id')
            ->select(
                'mobile_devices.device_type',
                'mobile_devices.os_version',
                DB::raw('AVG(mobile_api_logs.response_time_ms) as avg_response_time'),
                DB::raw('COUNT(*) as request_count'),
                DB::raw('SUM(CASE WHEN mobile_api_logs.status_code >= 400 THEN 1 ELSE 0 END) as error_count')
            )
            ->where('mobile_api_logs.created_at', '>=', $startDate)
            ->groupBy('mobile_devices.device_type', 'mobile_devices.os_version')
            ->orderBy('avg_response_time', 'desc')
            ->get()
            ->map(function ($item) {
                $item->error_rate = $item->request_count > 0 
                    ? round(($item->error_count / $item->request_count) * 100, 2) 
                    : 0;
                return $item;
            });
    }

    private function getCrashAnalytics($startDate): array
    {
        // This would integrate with crash reporting services
        // like Firebase Crashlytics or Bugsnag
        return [
            'crash_free_rate' => 99.5,
            'crashes_by_device' => [],
            'crashes_by_os' => [],
            'top_crash_causes' => []
        ];
    }

    private function getStartDate(string $timeRange): \Carbon\Carbon
    {
        switch ($timeRange) {
            case '1d':
                return now()->subDay();
            case '7d':
                return now()->subWeek();
            case '30d':
                return now()->subMonth();
            case '90d':
                return now()->subMonths(3);
            default:
                return now()->subWeek();
        }
    }
}
```

---

This comprehensive monitoring and analytics guide provides the foundation for maintaining visibility into your mobile API system's health, performance, and user experience. The next step would be to complete the final documentation file - the deployment checklist.

<function_calls>
<invoke name="TodoWrite">
<parameter name="todos">[{"id": "1", "content": "Create main mobile API documentation directory and README", "status": "completed"}, {"id": "2", "content": "Create implementation guide with Laravel Sanctum and OAuth 2.0 PKCE", "status": "completed"}, {"id": "3", "content": "Create API reference documentation", "status": "completed"}, {"id": "4", "content": "Create security configuration guide (without cert pinning)", "status": "completed"}, {"id": "5", "content": "Create Android SDK implementation guide", "status": "completed"}, {"id": "6", "content": "Create iOS SDK implementation guide", "status": "completed"}, {"id": "7", "content": "Create testing guide", "status": "completed"}, {"id": "8", "content": "Create monitoring and analytics guide", "status": "completed"}, {"id": "9", "content": "Create deployment checklist", "status": "in_progress"}]