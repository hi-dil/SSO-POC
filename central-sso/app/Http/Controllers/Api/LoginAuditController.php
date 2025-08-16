<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginAudit;
use App\Models\ActiveSession;
use App\Models\User;
use App\Services\LoginAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class LoginAuditController extends Controller
{
    private LoginAuditService $auditService;

    public function __construct(LoginAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Record a login event from a tenant application
     */
    public function recordLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer',
            'email' => 'required|email',
            'tenant_id' => 'required|string',
            'login_method' => 'required|string|in:sso,direct,api',
            'is_successful' => 'required|boolean',
            'failure_reason' => 'nullable|string',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string',
            'session_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find the central SSO user by email
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                Log::warning('Tenant login audit: User not found in central SSO', [
                    'email' => $request->email,
                    'tenant' => $request->tenant_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'User not found in central SSO'
                ], 404);
            }

            if ($request->is_successful) {
                // Record successful login
                $audit = $this->auditService->recordLogin(
                    $user,
                    $request->tenant_id,
                    $request->login_method,
                    $request->session_id
                );

                Log::info('Tenant login audit recorded', [
                    'audit_id' => $audit->id,
                    'user_id' => $user->id,
                    'tenant' => $request->tenant_id,
                    'method' => $request->login_method
                ]);
            } else {
                // Record failed login
                $audit = $this->auditService->recordFailedLogin(
                    $request->email,
                    $request->tenant_id,
                    $request->login_method,
                    $request->failure_reason ?: 'Unknown failure'
                );

                Log::info('Tenant failed login audit recorded', [
                    'audit_id' => $audit->id,
                    'email' => $request->email,
                    'tenant' => $request->tenant_id,
                    'reason' => $request->failure_reason
                ]);
            }

            return response()->json([
                'success' => true,
                'audit_id' => $audit->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error recording tenant login audit', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'tenant' => $request->tenant_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Record a logout event from a tenant application
     */
    public function recordLogout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'tenant_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->auditService->recordLogout($request->session_id);

            Log::info('Tenant logout audit recorded', [
                'session_id' => $request->session_id,
                'tenant' => $request->tenant_id
            ]);

            return response()->json([
                'success' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error recording tenant logout audit', [
                'error' => $e->getMessage(),
                'session_id' => $request->session_id,
                'tenant' => $request->tenant_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}