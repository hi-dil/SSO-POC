<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginAudit;
use App\Models\ActiveSession;
use App\Models\User;
use App\DTOs\Request\LoginAuditRequestDTO;
use App\DTOs\Request\LogoutAuditRequestDTO;
use App\DTOs\Response\AuditResponseDTO;
use App\Services\LoginAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Audit",
 *     description="Login audit management endpoints"
 * )
 */
class LoginAuditController extends Controller
{
    private LoginAuditService $auditService;

    public function __construct(LoginAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * @OA\Post(
     *     path="/api/audit/login",
     *     summary="Record a login audit event",
     *     description="Record a login event from a tenant application",
     *     operationId="recordLoginAudit",
     *     tags={"Audit"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginAuditRequestDTO")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login audit recorded successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AuditResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found in central SSO",
     *         @OA\JsonContent(ref="#/components/schemas/AuditResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/AuditResponseDTO")
     *     )
     * )
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

        $dto = LoginAuditRequestDTO::fromArray($request->validated());

        try {
            // Find the central SSO user by email
            $user = User::where('email', $dto->email)->first();
            
            if (!$user) {
                Log::warning('Tenant login audit: User not found in central SSO', [
                    'email' => $dto->email,
                    'tenant' => $dto->tenant_id
                ]);
                
                $response = AuditResponseDTO::error('User not found in central SSO');
                return response()->json($response->toArray(), 404);
            }

            if ($dto->is_successful) {
                // Record successful login
                $audit = $this->auditService->recordLogin(
                    $user,
                    $dto->tenant_id,
                    $dto->login_method,
                    $dto->session_id
                );

                Log::info('Tenant login audit recorded', [
                    'audit_id' => $audit->id,
                    'user_id' => $user->id,
                    'tenant' => $dto->tenant_id,
                    'method' => $dto->login_method
                ]);
            } else {
                // Record failed login
                $audit = $this->auditService->recordFailedLogin(
                    $dto->email,
                    $dto->tenant_id,
                    $dto->login_method,
                    $dto->failure_reason ?: 'Unknown failure'
                );

                Log::info('Tenant failed login audit recorded', [
                    'audit_id' => $audit->id,
                    'email' => $dto->email,
                    'tenant' => $dto->tenant_id,
                    'reason' => $dto->failure_reason
                ]);
            }

            $response = AuditResponseDTO::success($audit->id, 'Audit recorded successfully');
            return response()->json($response->toArray());

        } catch (\Exception $e) {
            Log::error('Error recording tenant login audit', [
                'error' => $e->getMessage(),
                'email' => $dto->email,
                'tenant' => $dto->tenant_id
            ]);

            $response = AuditResponseDTO::error('Internal server error');
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/audit/logout",
     *     summary="Record a logout audit event",
     *     description="Record a logout event from a tenant application",
     *     operationId="recordLogoutAudit",
     *     tags={"Audit"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LogoutAuditRequestDTO")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logout audit recorded successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AuditResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/AuditResponseDTO")
     *     )
     * )
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

        $dto = LogoutAuditRequestDTO::fromArray($request->validated());

        try {
            $this->auditService->recordLogout($dto->session_id);

            Log::info('Tenant logout audit recorded', [
                'session_id' => $dto->session_id,
                'tenant' => $dto->tenant_id
            ]);

            $response = AuditResponseDTO::success(null, 'Logout audit recorded successfully');
            return response()->json($response->toArray());

        } catch (\Exception $e) {
            Log::error('Error recording tenant logout audit', [
                'error' => $e->getMessage(),
                'session_id' => $dto->session_id,
                'tenant' => $dto->tenant_id
            ]);

            $response = AuditResponseDTO::error('Internal server error');
            return response()->json($response->toArray(), 500);
        }
    }
}