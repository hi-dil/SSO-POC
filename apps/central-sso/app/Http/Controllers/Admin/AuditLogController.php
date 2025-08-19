<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class AuditLogController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->middleware('can:audit.view');
        $this->auditService = $auditService;
    }

    /**
     * Display the audit logs dashboard
     */
    public function index(Request $request)
    {
        $filters = $this->getFilters($request);
        
        // Get activities based on filters
        $activities = $this->auditService->getActivities(
            $filters['module'],
            $filters['submodule'],
            $filters['user_id'],
            $filters['subject_type'],
            $filters['subject_id'],
            $filters['start_date'],
            $filters['end_date'],
            $filters['per_page']
        );

        // Get statistics for the dashboard
        $statistics = $this->auditService->getStatistics(
            $filters['start_date'],
            $filters['end_date']
        );

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        return view('admin.audit-logs.index', compact(
            'activities',
            'statistics',
            'filters',
            'filterOptions'
        ));
    }

    /**
     * Show detailed activity view
     */
    public function show(Activity $activity)
    {
        $activity->load(['causer', 'subject']);
        
        return view('admin.audit-logs.show', compact('activity'));
    }

    /**
     * Get activities via AJAX for real-time updates
     */
    public function activities(Request $request)
    {
        $filters = $this->getFilters($request);
        
        $activities = $this->auditService->getActivities(
            $filters['module'],
            $filters['submodule'],
            $filters['user_id'],
            $filters['subject_type'],
            $filters['subject_id'],
            $filters['start_date'],
            $filters['end_date'],
            $filters['per_page']
        );

        return response()->json([
            'success' => true,
            'data' => $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'log_name' => $activity->log_name,
                    'created_at' => $activity->created_at->diffForHumans(),
                    'created_at_full' => $activity->created_at->format('Y-m-d H:i:s'),
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'subject' => $activity->subject ? [
                        'type' => class_basename($activity->subject),
                        'id' => $activity->subject->id,
                    ] : null,
                    'properties' => $activity->properties,
                ];
            }),
        ]);
    }

    /**
     * Get statistics via AJAX
     */
    public function statistics(Request $request)
    {
        $filters = $this->getFilters($request);
        
        $statistics = $this->auditService->getStatistics(
            $filters['start_date'],
            $filters['end_date']
        );

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Export audit logs
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,json',
            'module' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = $this->getFilters($request);
        
        // Get all activities without limit for export
        $activities = $this->auditService->getActivities(
            $filters['module'],
            $filters['submodule'],
            $filters['user_id'],
            $filters['subject_type'],
            $filters['subject_id'],
            $filters['start_date'],
            $filters['end_date'],
            10000 // Large limit for export
        );

        $format = $request->input('format', 'csv');
        $filename = 'audit-logs-' . now()->format('Y-m-d-H-i-s');

        if ($format === 'csv') {
            return $this->exportCsv($activities, $filename);
        } else {
            return $this->exportJson($activities, $filename);
        }
    }

    /**
     * Clean up old audit logs
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:30|max:365',
        ]);

        $days = $request->input('days');
        $deletedCount = $this->auditService->cleanup($days);

        // Log the cleanup operation
        $this->auditService->logSystem(
            'log_archived',
            "Cleaned up {$deletedCount} audit log entries older than {$days} days",
            null,
            ['deleted_count' => $deletedCount, 'days_threshold' => $days]
        );

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} old audit log entries.",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Get user activities for a specific user
     */
    public function userActivities(Request $request, int $userId)
    {
        $filters = $this->getFilters($request);
        $filters['user_id'] = $userId;
        
        $activities = $this->auditService->getActivities(
            $filters['module'],
            $filters['submodule'],
            $filters['user_id'],
            $filters['subject_type'],
            $filters['subject_id'],
            $filters['start_date'],
            $filters['end_date'],
            $filters['per_page']
        );

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get filters from request
     */
    private function getFilters(Request $request): array
    {
        return [
            'module' => $request->input('module'),
            'submodule' => $request->input('submodule'),
            'user_id' => $request->input('user_id') ? (int) $request->input('user_id') : null,
            'subject_type' => $request->input('subject_type'),
            'subject_id' => $request->input('subject_id') ? (int) $request->input('subject_id') : null,
            'start_date' => $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null,
            'end_date' => $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null,
            'per_page' => min((int) $request->input('per_page', 100), 500),
        ];
    }

    /**
     * Get filter options for dropdowns
     */
    private function getFilterOptions(): array
    {
        $modules = config('audit-modules.modules', []);
        $logNames = Activity::distinct()->pluck('log_name')->filter();
        $subjectTypes = Activity::distinct()->pluck('subject_type')->filter();
        
        return [
            'modules' => $modules,
            'log_names' => $logNames,
            'subject_types' => $subjectTypes,
        ];
    }

    /**
     * Export activities as CSV
     */
    private function exportCsv($activities, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function () use ($activities) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID',
                'Description',
                'Module',
                'Submodule',
                'User',
                'User Email',
                'Subject Type',
                'Subject ID',
                'IP Address',
                'User Agent',
                'Date',
                'Properties'
            ]);

            // CSV Data
            foreach ($activities as $activity) {
                $properties = $activity->properties;
                fputcsv($file, [
                    $activity->id,
                    $activity->description,
                    $properties['module'] ?? $activity->log_name,
                    $properties['submodule'] ?? '',
                    $activity->causer?->name ?? 'System',
                    $activity->causer?->email ?? '',
                    $activity->subject_type ? class_basename($activity->subject_type) : '',
                    $activity->subject_id ?? '',
                    $properties['ip_address'] ?? '',
                    $properties['user_agent'] ?? '',
                    $activity->created_at->format('Y-m-d H:i:s'),
                    json_encode($properties, JSON_UNESCAPED_SLASHES),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export activities as JSON
     */
    private function exportJson($activities, string $filename)
    {
        $data = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'description' => $activity->description,
                'log_name' => $activity->log_name,
                'module' => $activity->properties['module'] ?? $activity->log_name,
                'submodule' => $activity->properties['submodule'] ?? null,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                    'email' => $activity->causer->email,
                ] : null,
                'subject' => $activity->subject ? [
                    'type' => get_class($activity->subject),
                    'id' => $activity->subject->id,
                ] : null,
                'properties' => $activity->properties,
                'created_at' => $activity->created_at->toISOString(),
            ];
        });

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}.json\"",
        ];

        return Response::make(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, $headers);
    }
}