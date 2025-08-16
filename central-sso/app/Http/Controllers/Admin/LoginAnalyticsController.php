<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LoginAuditService;
use App\Models\LoginAudit;
use App\Models\ActiveSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class LoginAnalyticsController extends Controller
{
    private LoginAuditService $auditService;

    public function __construct(LoginAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display the analytics dashboard
     */
    public function index()
    {
        $statistics = $this->auditService->getDashboardStatistics();
        
        return view('admin.analytics.index', [
            'statistics' => $statistics,
        ]);
    }

    /**
     * Get dashboard statistics as JSON
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->auditService->getDashboardStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get recent activity data
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        
        $recentLogins = LoginAudit::getRecentActivity($limit);
        $activeSessions = ActiveSession::getActiveSessions()->take($limit);
        
        return response()->json([
            'success' => true,
            'data' => [
                'recent_logins' => $recentLogins,
                'active_sessions' => $activeSessions,
            ]
        ]);
    }

    /**
     * Get login trends data
     */
    public function getLoginTrends(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $trends = LoginAudit::where('is_successful', true)
            ->where('login_at', '>=', $startDate)
            ->where('login_at', '<=', $endDate)
            ->selectRaw('DATE(login_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        // Fill in missing dates with 0
        $dateRange = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $dateRange[$dateKey] = [
                'date' => $dateKey,
                'count' => $trends[$dateKey]['count'] ?? 0
            ];
        }

        return response()->json([
            'success' => true,
            'data' => array_values($dateRange)
        ]);
    }

    /**
     * Get user activity details
     */
    public function getUserActivity(Request $request, int $userId): JsonResponse
    {
        $activity = $this->auditService->getUserActivity($userId);
        
        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    /**
     * Get tenant analytics
     */
    public function getTenantAnalytics(Request $request, string $tenantId): JsonResponse
    {
        $analytics = $this->auditService->getTenantActivity($tenantId);
        
        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get failed login attempts
     */
    public function getFailedAttempts(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        
        $failedAttempts = LoginAudit::getFailedAttempts($limit);
        
        return response()->json([
            'success' => true,
            'data' => $failedAttempts
        ]);
    }

    /**
     * Get active sessions by method
     */
    public function getActiveSessionsByMethod(): JsonResponse
    {
        $sessionsByMethod = ActiveSession::active()
            ->groupBy('login_method')
            ->selectRaw('login_method, count(*) as count')
            ->pluck('count', 'login_method')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $sessionsByMethod
        ]);
    }

    /**
     * Get hourly login distribution
     */
    public function getHourlyDistribution(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        $startDate = now()->subDays($days);

        $hourlyData = LoginAudit::where('is_successful', true)
            ->where('login_at', '>=', $startDate)
            ->selectRaw('HOUR(login_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour')
            ->toArray();

        // Fill in all 24 hours
        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[] = [
                'hour' => $h,
                'count' => $hourlyData[$h]['count'] ?? 0,
                'label' => sprintf('%02d:00', $h)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $hours
        ]);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->subDays(30);
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

        $loginData = LoginAudit::with(['user', 'tenant'])
            ->where('login_at', '>=', $startDate)
            ->where('login_at', '<=', $endDate)
            ->orderBy('login_at', 'desc')
            ->get();

        if ($format === 'csv') {
            return $this->exportToCsv($loginData, $startDate, $endDate);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unsupported export format'
        ], 400);
    }

    /**
     * Export data to CSV
     */
    private function exportToCsv($data, Carbon $startDate, Carbon $endDate)
    {
        $filename = sprintf(
            'login_analytics_%s_to_%s.csv',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Date/Time',
                'User Name',
                'User Email',
                'Tenant',
                'Login Method',
                'IP Address',
                'Success',
                'Session Duration (minutes)',
                'Failure Reason'
            ]);

            // CSV data
            foreach ($data as $record) {
                fputcsv($file, [
                    $record->login_at->format('Y-m-d H:i:s'),
                    $record->user ? $record->user->name : 'Unknown',
                    $record->user ? $record->user->email : 'Unknown',
                    $record->tenant ? $record->tenant->name : 'N/A',
                    ucfirst($record->login_method),
                    $record->ip_address,
                    $record->is_successful ? 'Yes' : 'No',
                    $record->session_duration ? round($record->session_duration / 60, 2) : 'N/A',
                    $record->failure_reason ?: 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Cleanup old audit records
     */
    public function cleanup(Request $request): JsonResponse
    {
        $days = $request->get('days', 90);
        
        if ($days < 30) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cleanup records newer than 30 days'
            ], 400);
        }

        $result = $this->auditService->cleanup($days);

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Cleanup completed. Deleted %d audit records and %d expired sessions.',
                $result['audit_records_deleted'],
                $result['expired_sessions_deleted']
            ),
            'data' => $result
        ]);
    }
}