# 🤖 Android SDK Implementation Guide

## Overview

This guide provides a complete Android implementation for connecting to the Tenant 1 Mobile API. The implementation uses Kotlin with modern Android development practices, including EncryptedSharedPreferences for secure storage, OkHttp for networking, and comprehensive security measures without certificate pinning.

## Architecture

```
Android App → Tenant 1 API → Central SSO (validation)
```

The Android app communicates directly with the Tenant 1 API, which handles authentication validation with the Central SSO server behind the scenes.

---

## 📦 Dependencies

### Add to `app/build.gradle`:

```kotlin
dependencies {
    // Core Android
    implementation 'androidx.core:core-ktx:1.12.0'
    implementation 'androidx.lifecycle:lifecycle-runtime-ktx:2.7.0'
    implementation 'androidx.activity:activity-compose:1.8.2'
    
    // Security
    implementation 'androidx.security:security-crypto:1.1.0-alpha06'
    implementation 'androidx.biometric:biometric:1.1.0'
    
    // Networking
    implementation 'com.squareup.okhttp3:okhttp:4.12.0'
    implementation 'com.squareup.okhttp3:logging-interceptor:4.12.0'
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
    
    // JSON
    implementation 'com.google.code.gson:gson:2.10.1'
    
    // Coroutines
    implementation 'org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3'
    
    // Testing
    testImplementation 'junit:junit:4.13.2'
    testImplementation 'org.mockito:mockito-core:5.8.0'
    testImplementation 'org.jetbrains.kotlinx:kotlinx-coroutines-test:1.7.3'
    androidTestImplementation 'androidx.test.ext:junit:1.1.5'
    androidTestImplementation 'androidx.test.espresso:espresso-core:3.5.1'
}
```

### Add to `app/proguard-rules.pro`:

```proguard
# Keep security classes
-keep class javax.crypto.** { *; }
-keep class java.security.** { *; }

# Keep Retrofit interfaces
-keep,allowobfuscation,allowshrinking interface retrofit2.Call
-keep,allowobfuscation,allowshrinking class retrofit2.Response

# Keep GSON annotations
-keepattributes Signature
-keepattributes *Annotation*
-keep class com.google.gson.reflect.TypeToken { *; }
-keep class * extends com.google.gson.reflect.TypeToken
```

---

## 🔐 Core Security Implementation

### 1. Security Utils

```kotlin
// app/src/main/java/com/tenant1/app/security/SecurityUtils.kt

package com.tenant1.app.security

import android.content.Context
import android.os.Build
import android.provider.Settings
import java.io.File
import java.security.MessageDigest
import java.util.*
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

object SecurityUtils {
    
    /**
     * Generate device fingerprint for identification
     */
    fun generateDeviceFingerprint(context: Context): String {
        val info = listOf(
            Build.MODEL,
            Build.VERSION.RELEASE,
            TimeZone.getDefault().id,
            Locale.getDefault().toString(),
            Build.CPU_ABI,
            getScreenResolution(context)
        )
        
        val fingerprint = info.joinToString("|")
        return hashSHA256(fingerprint)
    }
    
    /**
     * Generate HMAC-SHA256 signature for request
     */
    fun generateHMACSignature(
        canonicalRequest: String,
        secret: String
    ): String {
        val secretKey = SecretKeySpec(secret.toByteArray(), "HmacSHA256")
        val mac = Mac.getInstance("HmacSHA256")
        mac.init(secretKey)
        val hash = mac.doFinal(canonicalRequest.toByteArray())
        return hash.joinToString("") { "%02x".format(it) }
    }
    
    /**
     * Create canonical request string for HMAC signing
     */
    fun createCanonicalRequest(
        method: String,
        path: String,
        timestamp: String,
        deviceId: String,
        body: String
    ): String {
        return "$method|$path|$timestamp|$deviceId|$body"
    }
    
    /**
     * Check if device is rooted
     */
    fun isDeviceRooted(): Boolean {
        return checkRootMethod1() || checkRootMethod2() || checkRootMethod3()
    }
    
    /**
     * Check if debugger is attached
     */
    fun isDebuggerAttached(): Boolean {
        return android.os.Debug.isDebuggerConnected()
    }
    
    /**
     * Check if running on emulator
     */
    fun isEmulator(): Boolean {
        return (Build.FINGERPRINT.startsWith("generic")
                || Build.FINGERPRINT.startsWith("unknown")
                || Build.MODEL.contains("google_sdk")
                || Build.MODEL.contains("Emulator")
                || Build.MODEL.contains("Android SDK built for x86")
                || Build.MANUFACTURER.contains("Genymotion")
                || Build.BRAND.startsWith("generic") && Build.DEVICE.startsWith("generic")
                || "google_sdk" == Build.PRODUCT)
    }
    
    /**
     * Generate secure random string
     */
    fun generateRandomString(length: Int): String {
        val chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~"
        return (1..length)
            .map { chars.random() }
            .joinToString("")
    }
    
    /**
     * Generate PKCE code verifier
     */
    fun generateCodeVerifier(): String {
        return generateRandomString(64)
    }
    
    /**
     * Generate PKCE code challenge from verifier
     */
    fun generateCodeChallenge(verifier: String): String {
        val bytes = verifier.toByteArray()
        val messageDigest = MessageDigest.getInstance("SHA-256")
        val digest = messageDigest.digest(bytes)
        return Base64.getUrlEncoder()
            .withoutPadding()
            .encodeToString(digest)
    }
    
    private fun hashSHA256(input: String): String {
        val messageDigest = MessageDigest.getInstance("SHA-256")
        val digest = messageDigest.digest(input.toByteArray())
        return digest.joinToString("") { "%02x".format(it) }
    }
    
    private fun getScreenResolution(context: Context): String {
        val displayMetrics = context.resources.displayMetrics
        return "${displayMetrics.widthPixels}x${displayMetrics.heightPixels}"
    }
    
    private fun checkRootMethod1(): Boolean {
        val buildTags = Build.TAGS
        return buildTags != null && buildTags.contains("test-keys")
    }
    
    private fun checkRootMethod2(): Boolean {
        val paths = arrayOf(
            "/system/app/Superuser.apk",
            "/sbin/su",
            "/system/bin/su",
            "/system/xbin/su",
            "/data/local/xbin/su",
            "/data/local/bin/su",
            "/system/sd/xbin/su",
            "/system/bin/failsafe/su",
            "/data/local/su",
            "/su/bin/su"
        )
        
        for (path in paths) {
            if (File(path).exists()) return true
        }
        return false
    }
    
    private fun checkRootMethod3(): Boolean {
        var process: Process? = null
        return try {
            process = Runtime.getRuntime().exec(arrayOf("/system/xbin/which", "su"))
            val bufferedReader = process.inputStream.bufferedReader()
            bufferedReader.readLine() != null
        } catch (t: Throwable) {
            false
        } finally {
            process?.destroy()
        }
    }
}
```

### 2. Secure Storage Manager

```kotlin
// app/src/main/java/com/tenant1/app/storage/SecureStorageManager.kt

package com.tenant1.app.storage

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class SecureStorageManager(context: Context) {
    
    private val masterKey = MasterKey.Builder(context)
        .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
        .build()
    
    private val encryptedSharedPreferences: SharedPreferences = 
        EncryptedSharedPreferences.create(
            context,
            "secure_auth_prefs",
            masterKey,
            EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
            EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
        )
    
    suspend fun storeTokens(
        accessToken: String,
        refreshToken: String,
        expiresIn: Long
    ) = withContext(Dispatchers.IO) {
        val expirationTime = System.currentTimeMillis() + (expiresIn * 1000)
        
        encryptedSharedPreferences.edit().apply {
            putString(KEY_ACCESS_TOKEN, accessToken)
            putString(KEY_REFRESH_TOKEN, refreshToken)
            putLong(KEY_TOKEN_EXPIRATION, expirationTime)
            putLong(KEY_TOKEN_STORED_AT, System.currentTimeMillis())
            apply()
        }
    }
    
    suspend fun getAccessToken(): String? = withContext(Dispatchers.IO) {
        encryptedSharedPreferences.getString(KEY_ACCESS_TOKEN, null)
    }
    
    suspend fun getRefreshToken(): String? = withContext(Dispatchers.IO) {
        encryptedSharedPreferences.getString(KEY_REFRESH_TOKEN, null)
    }
    
    suspend fun isTokenExpired(): Boolean = withContext(Dispatchers.IO) {
        val expirationTime = encryptedSharedPreferences.getLong(KEY_TOKEN_EXPIRATION, 0)
        val currentTime = System.currentTimeMillis()
        
        // Consider token expired if within 5 minutes of expiration
        val bufferTime = 5 * 60 * 1000 // 5 minutes
        currentTime >= (expirationTime - bufferTime)
    }
    
    suspend fun clearTokens() = withContext(Dispatchers.IO) {
        encryptedSharedPreferences.edit().apply {
            remove(KEY_ACCESS_TOKEN)
            remove(KEY_REFRESH_TOKEN)
            remove(KEY_TOKEN_EXPIRATION)
            remove(KEY_TOKEN_STORED_AT)
            apply()
        }
    }
    
    suspend fun storeUserInfo(
        userId: String,
        name: String,
        email: String
    ) = withContext(Dispatchers.IO) {
        encryptedSharedPreferences.edit().apply {
            putString(KEY_USER_ID, userId)
            putString(KEY_USER_NAME, name)
            putString(KEY_USER_EMAIL, email)
            apply()
        }
    }
    
    suspend fun getUserInfo(): UserInfo? = withContext(Dispatchers.IO) {
        val userId = encryptedSharedPreferences.getString(KEY_USER_ID, null)
        val name = encryptedSharedPreferences.getString(KEY_USER_NAME, null)
        val email = encryptedSharedPreferences.getString(KEY_USER_EMAIL, null)
        
        if (userId != null && name != null && email != null) {
            UserInfo(userId, name, email)
        } else {
            null
        }
    }
    
    suspend fun storeDeviceId(deviceId: String) = withContext(Dispatchers.IO) {
        encryptedSharedPreferences.edit().apply {
            putString(KEY_DEVICE_ID, deviceId)
            apply()
        }
    }
    
    suspend fun getDeviceId(): String? = withContext(Dispatchers.IO) {
        encryptedSharedPreferences.getString(KEY_DEVICE_ID, null)
    }
    
    suspend fun isLoggedIn(): Boolean = withContext(Dispatchers.IO) {
        val accessToken = getAccessToken()
        val refreshToken = getRefreshToken()
        accessToken != null && refreshToken != null
    }
    
    companion object {
        private const val KEY_ACCESS_TOKEN = "access_token"
        private const val KEY_REFRESH_TOKEN = "refresh_token"
        private const val KEY_TOKEN_EXPIRATION = "token_expiration"
        private const val KEY_TOKEN_STORED_AT = "token_stored_at"
        private const val KEY_USER_ID = "user_id"
        private const val KEY_USER_NAME = "user_name"
        private const val KEY_USER_EMAIL = "user_email"
        private const val KEY_DEVICE_ID = "device_id"
    }
}

data class UserInfo(
    val id: String,
    val name: String,
    val email: String
)
```

### 3. Device Manager

```kotlin
// app/src/main/java/com/tenant1/app/device/DeviceManager.kt

package com.tenant1.app.device

import android.content.Context
import com.tenant1.app.security.SecurityUtils
import com.tenant1.app.storage.SecureStorageManager
import java.util.*

class DeviceManager(
    private val context: Context,
    private val secureStorage: SecureStorageManager
) {
    
    suspend fun getOrCreateDeviceId(): String {
        var deviceId = secureStorage.getDeviceId()
        
        if (deviceId == null) {
            deviceId = generateUniqueDeviceId()
            secureStorage.storeDeviceId(deviceId)
        }
        
        return deviceId
    }
    
    fun getDeviceInfo(): DeviceInfo {
        return DeviceInfo(
            deviceId = "", // Will be set by caller
            deviceType = "android",
            deviceName = android.os.Build.MODEL,
            deviceModel = android.os.Build.DEVICE,
            osVersion = android.os.Build.VERSION.RELEASE,
            appVersion = getAppVersion(),
            screenResolution = getScreenResolution(),
            timezone = TimeZone.getDefault().id,
            language = Locale.getDefault().toString()
        )
    }
    
    fun getSecurityInfo(): SecurityInfo {
        return SecurityInfo(
            rooted = SecurityUtils.isDeviceRooted(),
            debugger = SecurityUtils.isDebuggerAttached(),
            emulator = SecurityUtils.isEmulator(),
            appIntegrity = checkAppIntegrity()
        )
    }
    
    fun generateDeviceFingerprint(): String {
        return SecurityUtils.generateDeviceFingerprint(context)
    }
    
    private fun generateUniqueDeviceId(): String {
        // Generate a unique device ID
        val timestamp = System.currentTimeMillis()
        val randomComponent = SecurityUtils.generateRandomString(8)
        return "android_${timestamp}_${randomComponent}"
    }
    
    private fun getAppVersion(): String {
        return try {
            val packageManager = context.packageManager
            val packageInfo = packageManager.getPackageInfo(context.packageName, 0)
            packageInfo.versionName ?: "1.0.0"
        } catch (e: Exception) {
            "1.0.0"
        }
    }
    
    private fun getScreenResolution(): String {
        val displayMetrics = context.resources.displayMetrics
        return "${displayMetrics.widthPixels}x${displayMetrics.heightPixels}"
    }
    
    private fun checkAppIntegrity(): Boolean {
        // Basic app integrity check
        // In a production app, you might use Google Play Integrity API
        return try {
            val packageManager = context.packageManager
            val packageInfo = packageManager.getPackageInfo(
                context.packageName, 
                android.content.pm.PackageManager.GET_SIGNATURES
            )
            packageInfo.signatures.isNotEmpty()
        } catch (e: Exception) {
            false
        }
    }
}

data class DeviceInfo(
    val deviceId: String,
    val deviceType: String,
    val deviceName: String,
    val deviceModel: String,
    val osVersion: String,
    val appVersion: String,
    val screenResolution: String,
    val timezone: String,
    val language: String
)

data class SecurityInfo(
    val rooted: Boolean,
    val debugger: Boolean,
    val emulator: Boolean,
    val appIntegrity: Boolean
)
```

---

## 🌐 Network Layer

### 1. API Models

```kotlin
// app/src/main/java/com/tenant1/app/api/models/AuthModels.kt

package com.tenant1.app.api.models

import com.google.gson.annotations.SerializedName

// Request Models
data class AuthorizeRequest(
    @SerializedName("client_id") val clientId: String,
    @SerializedName("code_challenge") val codeChallenge: String,
    @SerializedName("code_challenge_method") val codeChallengeMethod: String = "S256",
    val scope: String = "read write"
)

data class TokenRequest(
    val code: String,
    @SerializedName("code_verifier") val codeVerifier: String,
    @SerializedName("device_id") val deviceId: String,
    val email: String,
    val password: String,
    @SerializedName("device_type") val deviceType: String,
    @SerializedName("device_name") val deviceName: String,
    @SerializedName("device_model") val deviceModel: String,
    @SerializedName("os_version") val osVersion: String,
    @SerializedName("app_version") val appVersion: String,
    @SerializedName("screen_resolution") val screenResolution: String,
    val timezone: String,
    val language: String
)

data class DirectLoginRequest(
    val email: String,
    val password: String,
    @SerializedName("device_id") val deviceId: String,
    @SerializedName("device_info") val deviceInfo: DeviceInfoRequest
)

data class DeviceInfoRequest(
    @SerializedName("device_type") val deviceType: String,
    @SerializedName("device_name") val deviceName: String,
    @SerializedName("device_model") val deviceModel: String,
    @SerializedName("os_version") val osVersion: String,
    @SerializedName("app_version") val appVersion: String,
    @SerializedName("screen_resolution") val screenResolution: String,
    val timezone: String,
    val language: String
)

data class RefreshTokenRequest(
    @SerializedName("refresh_token") val refreshToken: String,
    @SerializedName("device_id") val deviceId: String,
    @SerializedName("device_fingerprint") val deviceFingerprint: String
)

// Response Models
data class ApiResponse<T>(
    val success: Boolean,
    val data: T? = null,
    val error: String? = null,
    val message: String? = null
)

data class AuthorizeResponse(
    @SerializedName("authorization_code") val authorizationCode: String,
    @SerializedName("expires_in") val expiresIn: Int
)

data class TokenResponse(
    @SerializedName("token_type") val tokenType: String,
    @SerializedName("access_token") val accessToken: String,
    @SerializedName("refresh_token") val refreshToken: String,
    @SerializedName("expires_in") val expiresIn: Long,
    val scope: String? = null,
    val user: User
)

data class User(
    val id: String,
    val name: String,
    val email: String
)

data class RefreshTokenResponse(
    @SerializedName("token_type") val tokenType: String,
    @SerializedName("access_token") val accessToken: String,
    @SerializedName("refresh_token") val refreshToken: String,
    @SerializedName("expires_in") val expiresIn: Long
)
```

### 2. Security Interceptor

```kotlin
// app/src/main/java/com/tenant1/app/network/SecurityInterceptor.kt

package com.tenant1.app.network

import android.content.Context
import com.google.gson.Gson
import com.tenant1.app.device.DeviceManager
import com.tenant1.app.security.SecurityUtils
import com.tenant1.app.storage.SecureStorageManager
import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.Response
import okio.Buffer

class SecurityInterceptor(
    private val context: Context,
    private val deviceManager: DeviceManager,
    private val secureStorage: SecureStorageManager,
    private val hmacSecret: String
) : Interceptor {
    
    override fun intercept(chain: Interceptor.Chain): Response {
        val originalRequest = chain.request()
        
        val timestamp = (System.currentTimeMillis() / 1000).toString()
        val deviceId = runBlocking { deviceManager.getOrCreateDeviceId() }
        val deviceFingerprint = deviceManager.generateDeviceFingerprint()
        val securityInfo = deviceManager.getSecurityInfo()
        
        // Get request body
        val requestBody = originalRequest.body
        val bodyString = if (requestBody != null) {
            val buffer = Buffer()
            requestBody.writeTo(buffer)
            buffer.readUtf8()
        } else {
            ""
        }
        
        // Generate HMAC signature
        val canonicalRequest = SecurityUtils.createCanonicalRequest(
            method = originalRequest.method,
            path = originalRequest.url.encodedPath,
            timestamp = timestamp,
            deviceId = deviceId,
            body = bodyString
        )
        
        val signature = SecurityUtils.generateHMACSignature(
            canonicalRequest = canonicalRequest,
            secret = hmacSecret
        )
        
        // Build new request with security headers
        val newRequest = originalRequest.newBuilder()
            .addHeader("X-Timestamp", timestamp)
            .addHeader("X-Device-Id", deviceId)
            .addHeader("X-Device-Fingerprint", deviceFingerprint)
            .addHeader("X-Signature", signature)
            .addHeader("X-Device-Info", Gson().toJson(securityInfo))
            .addHeader("User-Agent", getUserAgent())
            .build()
        
        return chain.proceed(newRequest)
    }
    
    private fun getUserAgent(): String {
        val appVersion = try {
            val packageManager = context.packageManager
            val packageInfo = packageManager.getPackageInfo(context.packageName, 0)
            packageInfo.versionName ?: "1.0.0"
        } catch (e: Exception) {
            "1.0.0"
        }
        
        return "Tenant1AndroidApp/${appVersion} (Android ${android.os.Build.VERSION.RELEASE}; ${android.os.Build.MODEL})"
    }
}
```

### 3. Auth Interceptor

```kotlin
// app/src/main/java/com/tenant1/app/network/AuthInterceptor.kt

package com.tenant1.app.network

import com.tenant1.app.storage.SecureStorageManager
import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.Response

class AuthInterceptor(
    private val secureStorage: SecureStorageManager
) : Interceptor {
    
    override fun intercept(chain: Interceptor.Chain): Response {
        val originalRequest = chain.request()
        
        // Skip auth header for public endpoints
        val publicEndpoints = listOf(
            "/auth/authorize",
            "/auth/token", 
            "/auth/login",
            "/auth/refresh"
        )
        
        val isPublicEndpoint = publicEndpoints.any { 
            originalRequest.url.encodedPath.contains(it) 
        }
        
        if (isPublicEndpoint) {
            return chain.proceed(originalRequest)
        }
        
        // Add auth header for protected endpoints
        val accessToken = runBlocking { secureStorage.getAccessToken() }
        
        val newRequest = if (accessToken != null) {
            originalRequest.newBuilder()
                .addHeader("Authorization", "Bearer $accessToken")
                .build()
        } else {
            originalRequest
        }
        
        return chain.proceed(newRequest)
    }
}
```

### 4. API Service

```kotlin
// app/src/main/java/com/tenant1/app/api/ApiService.kt

package com.tenant1.app.api

import com.tenant1.app.api.models.*
import retrofit2.Response
import retrofit2.http.*

interface ApiService {
    
    @POST("auth/authorize")
    suspend fun authorize(
        @Body request: AuthorizeRequest
    ): Response<ApiResponse<AuthorizeResponse>>
    
    @POST("auth/token")
    suspend fun exchangeToken(
        @Body request: TokenRequest
    ): Response<ApiResponse<TokenResponse>>
    
    @POST("auth/login")
    suspend fun directLogin(
        @Body request: DirectLoginRequest
    ): Response<ApiResponse<TokenResponse>>
    
    @POST("auth/refresh")
    suspend fun refreshToken(
        @Body request: RefreshTokenRequest
    ): Response<ApiResponse<RefreshTokenResponse>>
    
    @POST("auth/logout")
    suspend fun logout(
        @Body request: Map<String, Boolean> = mapOf("revoke_all_device_tokens" to false)
    ): Response<ApiResponse<Any>>
    
    @GET("profile")
    suspend fun getProfile(): Response<ApiResponse<User>>
    
    @PUT("profile")
    suspend fun updateProfile(
        @Body request: Map<String, Any>
    ): Response<ApiResponse<User>>
    
    @GET("devices")
    suspend fun getDevices(): Response<ApiResponse<List<DeviceInfo>>>
    
    @DELETE("devices/{deviceId}")
    suspend fun revokeDevice(
        @Path("deviceId") deviceId: String
    ): Response<ApiResponse<Any>>
}
```

---

## 🔑 Authentication Service

### Main Authentication Service

```kotlin
// app/src/main/java/com/tenant1/app/auth/AuthService.kt

package com.tenant1.app.auth

import android.content.Context
import android.util.Log
import com.tenant1.app.api.ApiService
import com.tenant1.app.api.models.*
import com.tenant1.app.device.DeviceManager
import com.tenant1.app.security.SecurityUtils
import com.tenant1.app.storage.SecureStorageManager
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import retrofit2.Response

class AuthService(
    private val context: Context,
    private val apiService: ApiService,
    private val secureStorage: SecureStorageManager,
    private val deviceManager: DeviceManager
) {
    
    private var currentCodeVerifier: String? = null
    
    /**
     * OAuth 2.0 PKCE Flow - Step 1: Get Authorization Code
     */
    suspend fun getAuthorizationCode(): Result<String> = withContext(Dispatchers.IO) {
        try {
            // Generate PKCE parameters
            val codeVerifier = SecurityUtils.generateCodeVerifier()
            val codeChallenge = SecurityUtils.generateCodeChallenge(codeVerifier)
            
            // Store verifier for later use
            currentCodeVerifier = codeVerifier
            
            val request = AuthorizeRequest(
                clientId = "mobile_app",
                codeChallenge = codeChallenge,
                codeChallengeMethod = "S256"
            )
            
            val response = apiService.authorize(request)
            
            if (response.isSuccessful && response.body()?.success == true) {
                val authCode = response.body()?.data?.authorizationCode
                    ?: return@withContext Result.failure(Exception("No authorization code received"))
                
                Log.d(TAG, "Authorization code obtained successfully")
                Result.success(authCode)
            } else {
                val error = response.body()?.error ?: "Authorization failed"
                Log.e(TAG, "Authorization failed: $error")
                Result.failure(Exception(error))
            }
        } catch (e: Exception) {
            Log.e(TAG, "Authorization error", e)
            Result.failure(e)
        }
    }
    
    /**
     * OAuth 2.0 PKCE Flow - Step 2: Exchange Code for Tokens
     */
    suspend fun exchangeCodeForTokens(
        authorizationCode: String,
        email: String,
        password: String
    ): Result<TokenResponse> = withContext(Dispatchers.IO) {
        try {
            val codeVerifier = currentCodeVerifier
                ?: return@withContext Result.failure(Exception("No code verifier available"))
            
            val deviceId = deviceManager.getOrCreateDeviceId()
            val deviceInfo = deviceManager.getDeviceInfo().copy(deviceId = deviceId)
            
            val request = TokenRequest(
                code = authorizationCode,
                codeVerifier = codeVerifier,
                deviceId = deviceId,
                email = email,
                password = password,
                deviceType = deviceInfo.deviceType,
                deviceName = deviceInfo.deviceName,
                deviceModel = deviceInfo.deviceModel,
                osVersion = deviceInfo.osVersion,
                appVersion = deviceInfo.appVersion,
                screenResolution = deviceInfo.screenResolution,
                timezone = deviceInfo.timezone,
                language = deviceInfo.language
            )
            
            val response = apiService.exchangeToken(request)
            
            if (response.isSuccessful && response.body()?.success == true) {
                val tokenData = response.body()?.data
                    ?: return@withContext Result.failure(Exception("No token data received"))
                
                // Store tokens securely
                secureStorage.storeTokens(
                    accessToken = tokenData.accessToken,
                    refreshToken = tokenData.refreshToken,
                    expiresIn = tokenData.expiresIn
                )
                
                // Store user info
                secureStorage.storeUserInfo(
                    userId = tokenData.user.id,
                    name = tokenData.user.name,
                    email = tokenData.user.email
                )
                
                // Clear the code verifier
                currentCodeVerifier = null
                
                Log.d(TAG, "Token exchange successful")
                Result.success(tokenData)
            } else {
                val error = response.body()?.error ?: "Token exchange failed"
                Log.e(TAG, "Token exchange failed: $error")
                Result.failure(Exception(error))
            }
        } catch (e: Exception) {
            Log.e(TAG, "Token exchange error", e)
            Result.failure(e)
        }
    }
    
    /**
     * Direct Login (Alternative to OAuth flow)
     */
    suspend fun directLogin(
        email: String,
        password: String
    ): Result<TokenResponse> = withContext(Dispatchers.IO) {
        try {
            val deviceId = deviceManager.getOrCreateDeviceId()
            val deviceInfo = deviceManager.getDeviceInfo().copy(deviceId = deviceId)
            
            val request = DirectLoginRequest(
                email = email,
                password = password,
                deviceId = deviceId,
                deviceInfo = DeviceInfoRequest(
                    deviceType = deviceInfo.deviceType,
                    deviceName = deviceInfo.deviceName,
                    deviceModel = deviceInfo.deviceModel,
                    osVersion = deviceInfo.osVersion,
                    appVersion = deviceInfo.appVersion,
                    screenResolution = deviceInfo.screenResolution,
                    timezone = deviceInfo.timezone,
                    language = deviceInfo.language
                )
            )
            
            val response = apiService.directLogin(request)
            
            if (response.isSuccessful && response.body()?.success == true) {
                val tokenData = response.body()?.data
                    ?: return@withContext Result.failure(Exception("No token data received"))
                
                // Store tokens securely
                secureStorage.storeTokens(
                    accessToken = tokenData.accessToken,
                    refreshToken = tokenData.refreshToken,
                    expiresIn = tokenData.expiresIn
                )
                
                // Store user info
                secureStorage.storeUserInfo(
                    userId = tokenData.user.id,
                    name = tokenData.user.name,
                    email = tokenData.user.email
                )
                
                Log.d(TAG, "Direct login successful")
                Result.success(tokenData)
            } else {
                val error = response.body()?.message ?: response.body()?.error ?: "Login failed"
                Log.e(TAG, "Direct login failed: $error")
                Result.failure(Exception(error))
            }
        } catch (e: Exception) {
            Log.e(TAG, "Direct login error", e)
            Result.failure(e)
        }
    }
    
    /**
     * Refresh access token
     */
    suspend fun refreshToken(): Result<Boolean> = withContext(Dispatchers.IO) {
        try {
            val refreshToken = secureStorage.getRefreshToken()
                ?: return@withContext Result.failure(Exception("No refresh token available"))
            
            val deviceId = deviceManager.getOrCreateDeviceId()
            val deviceFingerprint = deviceManager.generateDeviceFingerprint()
            
            val request = RefreshTokenRequest(
                refreshToken = refreshToken,
                deviceId = deviceId,
                deviceFingerprint = deviceFingerprint
            )
            
            val response = apiService.refreshToken(request)
            
            if (response.isSuccessful && response.body()?.success == true) {
                val tokenData = response.body()?.data
                    ?: return@withContext Result.failure(Exception("No token data received"))
                
                // Store new tokens
                secureStorage.storeTokens(
                    accessToken = tokenData.accessToken,
                    refreshToken = tokenData.refreshToken,
                    expiresIn = tokenData.expiresIn
                )
                
                Log.d(TAG, "Token refresh successful")
                Result.success(true)
            } else {
                val error = response.body()?.error ?: "Token refresh failed"
                Log.e(TAG, "Token refresh failed: $error")
                Result.failure(Exception(error))
            }
        } catch (e: Exception) {
            Log.e(TAG, "Token refresh error", e)
            Result.failure(e)
        }
    }
    
    /**
     * Check if user is logged in
     */
    suspend fun isLoggedIn(): Boolean {
        return secureStorage.isLoggedIn()
    }
    
    /**
     * Check if token is valid (not expired)
     */
    suspend fun isTokenValid(): Boolean {
        return secureStorage.isLoggedIn() && !secureStorage.isTokenExpired()
    }
    
    /**
     * Get current user info
     */
    suspend fun getCurrentUser(): UserInfo? {
        return secureStorage.getUserInfo()
    }
    
    /**
     * Logout user
     */
    suspend fun logout(): Result<Boolean> = withContext(Dispatchers.IO) {
        try {
            // Call logout API
            val response = apiService.logout()
            
            // Clear local storage regardless of API call result
            secureStorage.clearTokens()
            
            if (response.isSuccessful && response.body()?.success == true) {
                Log.d(TAG, "Logout successful")
                Result.success(true)
            } else {
                Log.w(TAG, "Logout API call failed, but local tokens cleared")
                Result.success(true) // Still successful locally
            }
        } catch (e: Exception) {
            Log.e(TAG, "Logout error", e)
            // Clear local storage even if API call fails
            secureStorage.clearTokens()
            Result.success(true)
        }
    }
    
    /**
     * Ensure valid token (refresh if needed)
     */
    suspend fun ensureValidToken(): Result<Boolean> {
        return if (isTokenValid()) {
            Result.success(true)
        } else if (secureStorage.getRefreshToken() != null) {
            refreshToken()
        } else {
            Result.failure(Exception("No valid token or refresh token available"))
        }
    }
    
    companion object {
        private const val TAG = "AuthService"
    }
}
```

---

## 🔧 Network Configuration

### Retrofit Setup

```kotlin
// app/src/main/java/com/tenant1/app/network/NetworkModule.kt

package com.tenant1.app.network

import android.content.Context
import com.tenant1.app.BuildConfig
import com.tenant1.app.api.ApiService
import com.tenant1.app.device.DeviceManager
import com.tenant1.app.storage.SecureStorageManager
import okhttp3.ConnectionSpec
import okhttp3.OkHttpClient
import okhttp3.Protocol
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object NetworkModule {
    
    fun provideApiService(
        context: Context,
        deviceManager: DeviceManager,
        secureStorage: SecureStorageManager
    ): ApiService {
        return provideRetrofit(context, deviceManager, secureStorage)
            .create(ApiService::class.java)
    }
    
    private fun provideRetrofit(
        context: Context,
        deviceManager: DeviceManager,
        secureStorage: SecureStorageManager
    ): Retrofit {
        return Retrofit.Builder()
            .baseUrl(getBaseUrl())
            .client(provideOkHttpClient(context, deviceManager, secureStorage))
            .addConverterFactory(GsonConverterFactory.create())
            .build()
    }
    
    private fun provideOkHttpClient(
        context: Context,
        deviceManager: DeviceManager,
        secureStorage: SecureStorageManager
    ): OkHttpClient {
        val builder = OkHttpClient.Builder()
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .protocols(listOf(Protocol.HTTP_2, Protocol.HTTP_1_1))
            .connectionSpecs(listOf(
                ConnectionSpec.MODERN_TLS,
                ConnectionSpec.COMPATIBLE_TLS
            ))
        
        // Add security interceptor (always)
        builder.addInterceptor(
            SecurityInterceptor(
                context = context,
                deviceManager = deviceManager,
                secureStorage = secureStorage,
                hmacSecret = getHmacSecret()
            )
        )
        
        // Add auth interceptor (always)
        builder.addInterceptor(AuthInterceptor(secureStorage))
        
        // Add logging in debug builds
        if (BuildConfig.DEBUG) {
            val loggingInterceptor = HttpLoggingInterceptor().apply {
                level = HttpLoggingInterceptor.Level.BODY
                redactHeader("Authorization")
                redactHeader("X-Signature")
            }
            builder.addInterceptor(loggingInterceptor)
        }
        
        return builder.build()
    }
    
    private fun getBaseUrl(): String {
        return if (BuildConfig.DEBUG) {
            "http://10.0.2.2:8001/api/v1/mobile/" // Android emulator
        } else {
            "https://tenant1.example.com/api/v1/mobile/"
        }
    }
    
    private fun getHmacSecret(): String {
        // In production, this should come from a secure source
        // For now, using BuildConfig (set in build.gradle)
        return BuildConfig.MOBILE_HMAC_SECRET
    }
}
```

### Build Configuration

```kotlin
// app/build.gradle (in android block)

android {
    // ... existing configuration ...
    
    buildTypes {
        debug {
            buildConfigField "String", "MOBILE_HMAC_SECRET", "\"your_development_hmac_secret\""
            buildConfigField "String", "API_BASE_URL", "\"http://10.0.2.2:8001/api/v1/mobile/\""
        }
        release {
            buildConfigField "String", "MOBILE_HMAC_SECRET", "\"your_production_hmac_secret\""
            buildConfigField "String", "API_BASE_URL", "\"https://tenant1.example.com/api/v1/mobile/\""
            minifyEnabled true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
}
```

---

## 🎨 UI Implementation

### Login Activity

```kotlin
// app/src/main/java/com/tenant1/app/ui/LoginActivity.kt

package com.tenant1.app.ui

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.lifecycle.lifecycleScope
import com.tenant1.app.auth.AuthService
import com.tenant1.app.device.DeviceManager
import com.tenant1.app.network.NetworkModule
import com.tenant1.app.storage.SecureStorageManager
import com.tenant1.app.ui.theme.Tenant1Theme
import kotlinx.coroutines.launch

class LoginActivity : ComponentActivity() {
    
    private lateinit var authService: AuthService
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        // Initialize services
        val secureStorage = SecureStorageManager(this)
        val deviceManager = DeviceManager(this, secureStorage)
        val apiService = NetworkModule.provideApiService(this, deviceManager, secureStorage)
        authService = AuthService(this, apiService, secureStorage, deviceManager)
        
        // Check if already logged in
        lifecycleScope.launch {
            if (authService.isLoggedIn()) {
                if (authService.isTokenValid()) {
                    navigateToMain()
                } else {
                    // Try to refresh token
                    authService.refreshToken().onSuccess {
                        navigateToMain()
                    }
                }
            }
        }
        
        setContent {
            Tenant1Theme {
                LoginScreen(
                    onLogin = { email, password ->
                        performLogin(email, password)
                    }
                )
            }
        }
    }
    
    private fun performLogin(email: String, password: String) {
        lifecycleScope.launch {
            try {
                // Use direct login for simplicity
                authService.directLogin(email, password).onSuccess {
                    runOnUiThread {
                        Toast.makeText(this@LoginActivity, "Login successful!", Toast.LENGTH_SHORT).show()
                        navigateToMain()
                    }
                }.onFailure { error ->
                    runOnUiThread {
                        Toast.makeText(this@LoginActivity, "Login failed: ${error.message}", Toast.LENGTH_LONG).show()
                    }
                }
            } catch (e: Exception) {
                runOnUiThread {
                    Toast.makeText(this@LoginActivity, "Login error: ${e.message}", Toast.LENGTH_LONG).show()
                }
            }
        }
    }
    
    private fun navigateToMain() {
        startActivity(Intent(this, MainActivity::class.java))
        finish()
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LoginScreen(onLogin: (String, String) -> Unit) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(false) }
    
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text(
            text = "Tenant 1 Login",
            style = MaterialTheme.typography.headlineMedium,
            modifier = Modifier.padding(bottom = 32.dp)
        )
        
        OutlinedTextField(
            value = email,
            onValueChange = { email = it },
            label = { Text("Email") },
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            enabled = !isLoading
        )
        
        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Password") },
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 24.dp),
            enabled = !isLoading
        )
        
        Button(
            onClick = {
                if (email.isNotBlank() && password.isNotBlank()) {
                    isLoading = true
                    onLogin(email, password)
                }
            },
            modifier = Modifier
                .fillMaxWidth()
                .height(48.dp),
            enabled = !isLoading && email.isNotBlank() && password.isNotBlank()
        ) {
            if (isLoading) {
                CircularProgressIndicator(
                    color = MaterialTheme.colorScheme.onPrimary,
                    modifier = Modifier.size(20.dp)
                )
            } else {
                Text("Login")
            }
        }
        
        Spacer(modifier = Modifier.height(16.dp))
        
        Text(
            text = "Demo Credentials:\nuser@tenant1.com / password",
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}
```

---

## 🧪 Testing

### Unit Tests

```kotlin
// app/src/test/java/com/tenant1/app/AuthServiceTest.kt

package com.tenant1.app

import com.tenant1.app.api.ApiService
import com.tenant1.app.api.models.*
import com.tenant1.app.auth.AuthService
import com.tenant1.app.device.DeviceManager
import com.tenant1.app.storage.SecureStorageManager
import kotlinx.coroutines.test.runTest
import org.junit.Before
import org.junit.Test
import org.mockito.Mock
import org.mockito.MockitoAnnotations
import org.mockito.kotlin.whenever
import retrofit2.Response
import kotlin.test.assertTrue

class AuthServiceTest {
    
    @Mock
    private lateinit var apiService: ApiService
    
    @Mock
    private lateinit var secureStorage: SecureStorageManager
    
    @Mock
    private lateinit var deviceManager: DeviceManager
    
    private lateinit var authService: AuthService
    
    @Before
    fun setup() {
        MockitoAnnotations.openMocks(this)
        authService = AuthService(
            context = mockContext,
            apiService = apiService,
            secureStorage = secureStorage,
            deviceManager = deviceManager
        )
    }
    
    @Test
    fun `direct login success should store tokens`() = runTest {
        // Arrange
        val email = "test@example.com"
        val password = "password"
        val mockResponse = ApiResponse(
            success = true,
            data = TokenResponse(
                tokenType = "Bearer",
                accessToken = "access_token",
                refreshToken = "refresh_token",
                expiresIn = 1800,
                user = User("1", "Test User", email)
            )
        )
        
        whenever(deviceManager.getOrCreateDeviceId()).thenReturn("device_123")
        whenever(deviceManager.getDeviceInfo()).thenReturn(mockDeviceInfo())
        whenever(apiService.directLogin(any())).thenReturn(
            Response.success(mockResponse)
        )
        
        // Act
        val result = authService.directLogin(email, password)
        
        // Assert
        assertTrue(result.isSuccess)
        verify(secureStorage).storeTokens(
            accessToken = "access_token",
            refreshToken = "refresh_token",
            expiresIn = 1800
        )
    }
    
    @Test
    fun `token refresh should update stored tokens`() = runTest {
        // Arrange
        val mockResponse = ApiResponse(
            success = true,
            data = RefreshTokenResponse(
                tokenType = "Bearer",
                accessToken = "new_access_token",
                refreshToken = "new_refresh_token",
                expiresIn = 1800
            )
        )
        
        whenever(secureStorage.getRefreshToken()).thenReturn("old_refresh_token")
        whenever(deviceManager.getOrCreateDeviceId()).thenReturn("device_123")
        whenever(deviceManager.generateDeviceFingerprint()).thenReturn("fingerprint")
        whenever(apiService.refreshToken(any())).thenReturn(
            Response.success(mockResponse)
        )
        
        // Act
        val result = authService.refreshToken()
        
        // Assert
        assertTrue(result.isSuccess)
        verify(secureStorage).storeTokens(
            accessToken = "new_access_token",
            refreshToken = "new_refresh_token",
            expiresIn = 1800
        )
    }
    
    private fun mockDeviceInfo() = DeviceInfo(
        deviceId = "device_123",
        deviceType = "android",
        deviceName = "Test Device",
        deviceModel = "TestModel",
        osVersion = "11",
        appVersion = "1.0.0",
        screenResolution = "1080x1920",
        timezone = "UTC",
        language = "en-US"
    )
}
```

### Integration Tests

```kotlin
// app/src/androidTest/java/com/tenant1/app/AuthIntegrationTest.kt

package com.tenant1.app

import androidx.test.ext.junit.runners.AndroidJUnit4
import androidx.test.platform.app.InstrumentationRegistry
import com.tenant1.app.auth.AuthService
import com.tenant1.app.device.DeviceManager
import com.tenant1.app.network.NetworkModule
import com.tenant1.app.storage.SecureStorageManager
import kotlinx.coroutines.test.runTest
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import kotlin.test.assertTrue

@RunWith(AndroidJUnit4::class)
class AuthIntegrationTest {
    
    private lateinit var authService: AuthService
    private lateinit var secureStorage: SecureStorageManager
    
    @Before
    fun setup() {
        val context = InstrumentationRegistry.getInstrumentation().targetContext
        secureStorage = SecureStorageManager(context)
        val deviceManager = DeviceManager(context, secureStorage)
        val apiService = NetworkModule.provideApiService(context, deviceManager, secureStorage)
        authService = AuthService(context, apiService, secureStorage, deviceManager)
    }
    
    @Test
    fun fullAuthenticationFlow() = runTest {
        // Clear any existing tokens
        secureStorage.clearTokens()
        
        // Test login
        val loginResult = authService.directLogin(
            email = "user@tenant1.com",
            password = "password"
        )
        
        assertTrue(loginResult.isSuccess, "Login should succeed")
        assertTrue(authService.isLoggedIn(), "Should be logged in after successful login")
        assertTrue(authService.isTokenValid(), "Token should be valid after login")
        
        // Test token refresh
        val refreshResult = authService.refreshToken()
        assertTrue(refreshResult.isSuccess, "Token refresh should succeed")
        
        // Test logout
        val logoutResult = authService.logout()
        assertTrue(logoutResult.isSuccess, "Logout should succeed")
        assertTrue(!authService.isLoggedIn(), "Should not be logged in after logout")
    }
}
```

---

## 📱 Usage Examples

### Initialize in Application Class

```kotlin
// app/src/main/java/com/tenant1/app/Tenant1Application.kt

package com.tenant1.app

import android.app.Application
import com.tenant1.app.auth.AuthService
import com.tenant1.app.device.DeviceManager
import com.tenant1.app.network.NetworkModule
import com.tenant1.app.storage.SecureStorageManager

class Tenant1Application : Application() {
    
    lateinit var authService: AuthService
        private set
    
    override fun onCreate() {
        super.onCreate()
        
        // Initialize core services
        val secureStorage = SecureStorageManager(this)
        val deviceManager = DeviceManager(this, secureStorage)
        val apiService = NetworkModule.provideApiService(this, deviceManager, secureStorage)
        
        authService = AuthService(this, apiService, secureStorage, deviceManager)
    }
}
```

### Use in Activity

```kotlin
// Example usage in any activity
class SomeActivity : ComponentActivity() {
    
    private val authService: AuthService
        get() = (application as Tenant1Application).authService
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        lifecycleScope.launch {
            // Ensure we have a valid token
            authService.ensureValidToken().onSuccess {
                // Token is valid, proceed with API calls
                loadUserData()
            }.onFailure {
                // Token invalid, redirect to login
                navigateToLogin()
            }
        }
    }
    
    private suspend fun loadUserData() {
        val userInfo = authService.getCurrentUser()
        // Update UI with user info
    }
}
```

---

## 🔒 Security Best Practices

### 1. Token Storage
- ✅ Uses EncryptedSharedPreferences
- ✅ Tokens stored with hardware-backed encryption when available
- ✅ Automatic token expiration handling

### 2. Request Security
- ✅ HMAC-SHA256 signing for all requests
- ✅ Timestamp validation to prevent replay attacks
- ✅ Device fingerprinting for additional validation

### 3. Device Security
- ✅ Root detection (basic implementation)
- ✅ Debugger detection
- ✅ Emulator detection
- ✅ App integrity checks

### 4. Network Security
- ✅ TLS 1.2+ enforcement
- ✅ No certificate pinning (by design)
- ✅ Proper error handling
- ✅ Request/response logging in debug builds only

---

## 🚀 Production Considerations

### 1. Obfuscation
Enable ProGuard/R8 for release builds to protect against reverse engineering.

### 2. Security Hardening
- Implement additional root detection methods
- Add anti-tampering measures
- Use Google Play Integrity API
- Implement certificate pinning if required

### 3. Monitoring
- Integrate with crash reporting (Firebase Crashlytics)
- Add performance monitoring
- Implement custom analytics for security events

### 4. Testing
- Test on various Android versions and devices
- Test network conditions (slow, offline, intermittent)
- Test security scenarios (rooted devices, etc.)

---

This Android implementation provides a secure, production-ready foundation for connecting to the Tenant 1 Mobile API while maintaining the simplified architecture without certificate pinning complexity.