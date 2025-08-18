<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Setting;
use App\DTOs\Request\LoginRequestDTO;
use App\DTOs\Request\RegisterRequestDTO;
use App\DTOs\Request\ValidateTokenRequestDTO;
use App\DTOs\Response\LoginResponseDTO;
use App\DTOs\Response\UserResponseDTO;
use App\DTOs\Response\ValidateTokenResponseDTO;
use App\DTOs\Response\ErrorResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Services\LoginAuditService;

/**
 * @OA\Info(
 *     title="SSO Authentication API",
 *     version="1.0.0",
 *     description="Single Sign-On API for multi-tenant authentication",
 *     @OA\Contact(
 *         email="admin@sso.com",
 *         name="SSO Admin"
 *     )
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Development server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

class AuthController extends Controller
{
    private LoginAuditService $auditService;

    public function __construct(LoginAuditService $auditService)
    {
        $this->auditService = $auditService;
    }
    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Authenticate user and get JWT token",
     *     description="Login with email and password for a specific tenant",
     *     operationId="login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequestDTO")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(ref="#/components/schemas/LoginResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied to tenant",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'tenant_slug' => 'required|string',
        ]);

        if ($validator->fails()) {
            $errorResponse = ErrorResponseDTO::validation($validator->errors()->toArray());
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }

        $loginDto = LoginRequestDTO::fromArray([
            'email' => $request->email,
            'password' => $request->password,
            'tenant' => $request->tenant_slug
        ]);

        $user = User::where('email', $loginDto->email)->with('tenants')->first();

        if (!$user || !Hash::check($loginDto->password, $user->password)) {
            // Record failed API login attempt
            $this->auditService->recordFailedLogin(
                $loginDto->email,
                $loginDto->tenant,
                'api',
                'Invalid credentials'
            );
            
            $errorResponse = ErrorResponseDTO::unauthorized('Invalid credentials');
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }

        if (!$user->hasAccessToTenant($loginDto->tenant)) {
            // Record failed API login attempt (access denied)
            $this->auditService->recordFailedLogin(
                $loginDto->email,
                $loginDto->tenant,
                'api',
                'Access denied to tenant'
            );
            
            $errorResponse = ErrorResponseDTO::forbidden('Access denied to tenant');
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }

        try {
            $customClaims = [
                'tenants' => $user->tenants->map(function($tenant) { return $tenant->slug; })->toArray(),
                'current_tenant' => $loginDto->tenant,
            ];

            // Get JWT TTL from settings (in minutes)
            $ttl = Setting::getJwtAccessTokenTtl();
            
            $token = JWTAuth::customClaims($customClaims)
                ->setTTL($ttl)
                ->fromUser($user);
        } catch (JWTException $e) {
            $errorResponse = new ErrorResponseDTO('Could not create token', null, 500);
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }

        // Record successful API login
        $this->auditService->recordLogin(
            $user,
            $loginDto->tenant,
            'api',
            null // API doesn't have Laravel session ID
        );

        $userDto = UserResponseDTO::fromUser($user, $loginDto->tenant);
        $loginResponse = LoginResponseDTO::success($token, $userDto);
        
        return response()->json($loginResponse->toArray());
    }

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="Register a new user",
     *     description="Register a new user for a specific tenant",
     *     operationId="register",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RegisterRequestDTO")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoginResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tenant not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'tenant_slug' => 'required|string',
        ]);

        if ($validator->fails()) {
            $errorResponse = ErrorResponseDTO::validation($validator->errors()->toArray());
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }

        $registerDto = RegisterRequestDTO::fromArray([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
            'tenant_slug' => $request->tenant_slug
        ]);

        $tenant = Tenant::where('slug', $registerDto->tenant_slug)->first();
        if (!$tenant) {
            $errorResponse = ErrorResponseDTO::notFound('Tenant not found');
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }

        $user = User::create([
            'name' => $registerDto->name,
            'email' => $registerDto->email,
            'password' => Hash::make($registerDto->password),
        ]);

        $user->tenants()->attach($tenant);

        try {
            $customClaims = [
                'tenants' => [$tenant->slug],
                'current_tenant' => $tenant->slug,
            ];

            // Get JWT TTL from settings (in minutes)
            $ttl = Setting::getJwtAccessTokenTtl();
            
            $token = JWTAuth::customClaims($customClaims)
                ->setTTL($ttl)
                ->fromUser($user);
        } catch (JWTException $e) {
            $errorResponse = new ErrorResponseDTO('User created but could not create token', null, 500);
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }

        $userDto = UserResponseDTO::fromUser($user, $tenant->slug);
        $loginResponse = LoginResponseDTO::success($token, $userDto, 'Registration successful');
        
        return response()->json($loginResponse->toArray(), 201);
    }

    /**
     * @OA\Post(
     *     path="/auth/validate",
     *     summary="Validate JWT token",
     *     description="Validate a JWT token and return user information",
     *     operationId="validateToken",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", description="JWT token to validate"),
     *             @OA\Property(property="tenant_slug", type="string", description="Tenant slug")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token validation result",
     *         @OA\JsonContent(ref="#/components/schemas/ValidateTokenResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid token",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token not valid for tenant",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     )
     * )
     */
    public function validateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'tenant_slug' => 'required|string',
        ]);

        if ($validator->fails()) {
            $response = ValidateTokenResponseDTO::invalid('Validation failed');
            return response()->json($response->toArray(), 422);
        }

        try {
            $user = JWTAuth::setToken($request->token)->authenticate();
            $payload = JWTAuth::setToken($request->token)->getPayload();
            
            $tenants = $payload->get('tenants', []);
            
            if (!in_array($request->tenant_slug, $tenants)) {
                $response = ValidateTokenResponseDTO::invalid('Token not valid for this tenant');
                return response()->json($response->toArray(), 403);
            }

            $userDto = UserResponseDTO::fromUser($user, $request->tenant_slug);
            $response = ValidateTokenResponseDTO::valid($userDto, date('c', $payload->get('exp')));
            
            return response()->json($response->toArray());
        } catch (JWTException $e) {
            $response = ValidateTokenResponseDTO::invalid('Token is invalid');
            return response()->json($response->toArray(), 401);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/user",
     *     summary="Get current authenticated user",
     *     description="Get the current authenticated user information",
     *     operationId="getUser",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User information",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="user", ref="#/components/schemas/UserResponseDTO")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid token",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     )
     * )
     */
    public function user(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $payload = JWTAuth::getPayload();
            
            $userDto = UserResponseDTO::fromUser($user, $payload->get('current_tenant'));
            
            return response()->json([
                'success' => true,
                'user' => $userDto->toArray()
            ]);
        } catch (JWTException $e) {
            $errorResponse = ErrorResponseDTO::unauthorized('Token is invalid');
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     summary="Refresh JWT token",
     *     description="Refresh an existing JWT token",
     *     operationId="refreshToken",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Could not refresh token",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     )
     * )
     */
    public function refresh(Request $request)
    {
        try {
            // Get refresh token TTL from settings (in minutes)
            $refreshTtl = Setting::getJwtRefreshTokenTtl();
            
            $token = JWTAuth::setTTL($refreshTtl)->refresh(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'token' => $token,
                'expires_in' => $refreshTtl * 60
            ]);
        } catch (JWTException $e) {
            $errorResponse = ErrorResponseDTO::unauthorized('Could not refresh token');
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Logout user",
     *     description="Invalidate the current JWT token",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Could not log out",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponseDTO")
     *     )
     * )
     */
    public function logout()
    {
        try {
            $token = JWTAuth::getToken();
            $user = JWTAuth::parseToken()->authenticate();
            
            // Record logout for API (using token as session identifier)
            $this->auditService->recordLogout($token->get());
            
            JWTAuth::invalidate($token);
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            $errorResponse = new ErrorResponseDTO('Could not log out', null, 500);
            return response()->json($errorResponse->toArray(), $errorResponse->code);
        }
    }
}
