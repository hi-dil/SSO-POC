<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class AuditService
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Log an activity with module and submodule context
     */
    public function log(
        string $module,
        string $submodule,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?Model $causer = null
    ): Activity {
        $logName = config("audit-modules.log_names.{$module}", $module);
        
        $activity = activity($logName)
            ->withProperties(array_merge($properties, [
                'module' => $module,
                'submodule' => $submodule,
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'request_method' => $this->request->method(),
                'request_url' => $this->request->fullUrl(),
                'request_id' => $this->request->header('X-Request-ID', uniqid('req_', true)),
            ]));

        if ($subject) {
            $activity->performedOn($subject);
        }

        if ($causer) {
            $activity->causedBy($causer);
        } elseif (Auth::check()) {
            $activity->causedBy(Auth::user());
        }

        return $activity->log($description);
    }

    /**
     * Log authentication events
     */
    public function logAuthentication(
        string $submodule,
        string $description,
        ?Model $user = null,
        array $properties = []
    ): Activity {
        return $this->log('authentication', $submodule, $description, $user, $properties);
    }

    /**
     * Log user management events
     */
    public function logUserManagement(
        string $submodule,
        string $description,
        ?Model $user = null,
        array $properties = []
    ): Activity {
        return $this->log('user_management', $submodule, $description, $user, $properties);
    }

    /**
     * Log tenant management events
     */
    public function logTenantManagement(
        string $submodule,
        string $description,
        ?Model $tenant = null,
        array $properties = []
    ): Activity {
        return $this->log('tenant_management', $submodule, $description, $tenant, $properties);
    }

    /**
     * Log settings changes
     */
    public function logSettings(
        string $submodule,
        string $description,
        ?Model $setting = null,
        array $properties = []
    ): Activity {
        return $this->log('settings', $submodule, $description, $setting, $properties);
    }

    /**
     * Log role and permission changes
     */
    public function logRolesPermissions(
        string $submodule,
        string $description,
        ?Model $subject = null,
        array $properties = []
    ): Activity {
        return $this->log('roles_permissions', $submodule, $description, $subject, $properties);
    }

    /**
     * Log security events
     */
    public function logSecurity(
        string $submodule,
        string $description,
        ?Model $subject = null,
        array $properties = []
    ): Activity {
        return $this->log('security', $submodule, $description, $subject, $properties);
    }

    /**
     * Log system administration events
     */
    public function logSystem(
        string $submodule,
        string $description,
        ?Model $subject = null,
        array $properties = []
    ): Activity {
        return $this->log('system', $submodule, $description, $subject, $properties);
    }

    /**
     * Log model changes with before/after values
     */
    public function logModelChange(
        string $module,
        string $submodule,
        string $action,
        Model $model,
        array $originalAttributes = [],
        array $newAttributes = []
    ): Activity {
        $description = ucfirst($action) . ' ' . class_basename($model);
        
        $properties = [
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id ?? null,
        ];

        if (!empty($originalAttributes)) {
            $properties['old'] = $originalAttributes;
        }

        if (!empty($newAttributes)) {
            $properties['new'] = $newAttributes;
        }

        // Calculate changes
        if (!empty($originalAttributes) && !empty($newAttributes)) {
            $changes = [];
            foreach ($newAttributes as $key => $newValue) {
                $oldValue = $originalAttributes[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
            if (!empty($changes)) {
                $properties['changes'] = $changes;
            }
        }

        return $this->log($module, $submodule, $description, $model, $properties);
    }

    /**
     * Log bulk operations
     */
    public function logBulkOperation(
        string $module,
        string $submodule,
        string $operation,
        array $items,
        array $properties = []
    ): Activity {
        $count = count($items);
        $description = "Bulk {$operation} performed on {$count} items";
        
        $bulkProperties = array_merge($properties, [
            'operation' => $operation,
            'item_count' => $count,
            'items' => $items,
        ]);

        return $this->log($module, $submodule, $description, null, $bulkProperties);
    }

    /**
     * Log failed operations
     */
    public function logFailure(
        string $module,
        string $submodule,
        string $operation,
        string $reason,
        ?Model $subject = null,
        array $properties = []
    ): Activity {
        $description = "Failed {$operation}: {$reason}";
        
        $failureProperties = array_merge($properties, [
            'operation' => $operation,
            'failure_reason' => $reason,
            'success' => false,
        ]);

        return $this->log($module, $submodule, $description, $subject, $failureProperties);
    }

    /**
     * Get audit activities with filtering
     */
    public function getActivities(
        ?string $module = null,
        ?string $submodule = null,
        ?int $causerId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        int $limit = 50
    ) {
        $query = Activity::with(['causer', 'subject']);

        if ($module) {
            $logName = config("audit-modules.log_names.{$module}", $module);
            $query->where('log_name', $logName);
        }

        if ($submodule) {
            $query->whereJsonContains('properties->submodule', $submodule);
        }

        if ($causerId) {
            $query->where('causer_id', $causerId);
        }

        if ($subjectType) {
            $query->where('subject_type', $subjectType);
        }

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Get activity statistics
     */
    public function getStatistics(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        $query = Activity::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalActivities = $query->count();
        
        $byModule = $query->clone()
            ->selectRaw('log_name, count(*) as count')
            ->groupBy('log_name')
            ->orderByDesc('count')
            ->pluck('count', 'log_name')
            ->toArray();

        $byUser = $query->clone()
            ->whereNotNull('causer_id')
            ->selectRaw('causer_id, count(*) as count')
            ->groupBy('causer_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('causer')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->causer_id => [
                    'count' => $item->count,
                    'user' => $item->causer,
                ]];
            })
            ->toArray();

        $recentActivities = $query->clone()
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Calculate today's activities
        $todayActivities = Activity::whereDate('created_at', today())->count();
        
        // Calculate active users (users with activity in last 30 days)
        $activeUsers = Activity::whereNotNull('causer_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->distinct('causer_id')
            ->count();
        
        // Get top module
        $topModule = null;
        if (!empty($byModule)) {
            $topModuleName = array_keys($byModule)[0]; // Get first key (highest count due to orderByDesc)
            $topModuleCount = $byModule[$topModuleName];
            
            // Convert log_name to human readable
            $modules = config('audit-modules.modules', []);
            $moduleName = $modules[$topModuleName]['name'] ?? ucfirst(str_replace('_', ' ', $topModuleName));
            
            $topModule = [
                'name' => $moduleName,
                'count' => $topModuleCount
            ];
        }

        return [
            'total_activities' => $totalActivities,
            'today_activities' => $todayActivities,
            'active_users' => $activeUsers,
            'top_module' => $topModule,
            'by_module' => $byModule,
            'top_users' => $byUser,
            'recent_activities' => $recentActivities,
        ];
    }

    /**
     * Clean up old audit logs
     */
    public function cleanup(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return Activity::where('created_at', '<', $cutoffDate)->delete();
    }
}