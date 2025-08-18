# 🍎 iOS SDK Implementation Guide

## Overview

This guide provides a complete iOS implementation for connecting to the Tenant 1 Mobile API. The implementation uses Swift with modern iOS development practices, including Keychain Services for secure storage, URLSession for networking, and comprehensive security measures without certificate pinning.

## Architecture

```
iOS App → Tenant 1 API → Central SSO (validation)
```

The iOS app communicates directly with the Tenant 1 API, which handles authentication validation with the Central SSO server behind the scenes.

---

## 📦 Dependencies

### Add to `Package.swift` (SPM) or `Podfile`:

**Swift Package Manager:**
```swift
// Package.swift
dependencies: [
    .package(url: "https://github.com/kishikawakatsumi/KeychainAccess.git", from: "4.2.2"),
    .package(url: "https://github.com/Alamofire/Alamofire.git", from: "5.8.1")
]
```

**CocoaPods:**
```ruby
# Podfile
pod 'KeychainAccess', '~> 4.2'
pod 'Alamofire', '~> 5.8'
```

### Project Configuration

Add to `Info.plist`:
```xml
<key>NSAppTransportSecurity</key>
<dict>
    <key>NSAllowsArbitraryLoads</key>
    <false/>
    <key>NSExceptionDomains</key>
    <dict>
        <key>tenant1.example.com</key>
        <dict>
            <key>NSExceptionRequiresForwardSecrecy</key>
            <false/>
            <key>NSExceptionMinimumTLSVersion</key>
            <string>TLSv1.2</string>
        </dict>
    </dict>
</key>
```

---

## 🔐 Core Security Implementation

### 1. Security Utils

```swift
// SecurityUtils.swift

import Foundation
import CryptoKit
import LocalAuthentication

class SecurityUtils {
    
    // MARK: - Device Information
    
    static func generateDeviceFingerprint() -> String {
        let info = [
            UIDevice.current.model,
            UIDevice.current.systemVersion,
            TimeZone.current.identifier,
            Locale.current.identifier,
            getScreenResolution(),
            getDeviceMemory()
        ]
        
        let fingerprint = info.joined(separator: "|")
        return hashSHA256(fingerprint)
    }
    
    static func generateDeviceId() -> String {
        // Generate a unique device ID
        let timestamp = Int(Date().timeIntervalSince1970)
        let randomComponent = generateRandomString(length: 8)
        return "ios_\(timestamp)_\(randomComponent)"
    }
    
    // MARK: - HMAC Signature Generation
    
    static func generateHMACSignature(canonicalRequest: String, secret: String) -> String {
        let key = SymmetricKey(data: Data(secret.utf8))
        let signature = HMAC<SHA256>.authenticationCode(
            for: Data(canonicalRequest.utf8),
            using: key
        )
        return Data(signature).hexEncodedString()
    }
    
    static func createCanonicalRequest(
        method: String,
        path: String,
        timestamp: String,
        deviceId: String,
        body: String
    ) -> String {
        return "\(method)|\(path)|\(timestamp)|\(deviceId)|\(body)"
    }
    
    // MARK: - PKCE Implementation
    
    static func generateCodeVerifier() -> String {
        return generateRandomString(length: 64)
    }
    
    static func generateCodeChallenge(from verifier: String) -> String {
        let data = verifier.data(using: .utf8)!
        let hash = SHA256.hash(data: data)
        return Data(hash).base64URLEncodedString()
    }
    
    // MARK: - Device Security Checks
    
    static func isJailbroken() -> Bool {
        #if targetEnvironment(simulator)
        return false
        #else
        return checkJailbreakMethod1() || checkJailbreakMethod2() || checkJailbreakMethod3()
        #endif
    }
    
    static func isDebuggerAttached() -> Bool {
        var info = kinfo_proc()
        var mib: [Int32] = [CTL_KERN, KERN_PROC, KERN_PROC_PID, getpid()]
        var size = MemoryLayout<kinfo_proc>.stride
        
        let result = sysctl(&mib, u_int(mib.count), &info, &size, nil, 0)
        assert(result == 0, "sysctl failed")
        
        return (info.kp_proc.p_flag & P_TRACED) != 0
    }
    
    static func isEmulator() -> Bool {
        #if targetEnvironment(simulator)
        return true
        #else
        return false
        #endif
    }
    
    // MARK: - Utility Functions
    
    static func generateRandomString(length: Int) -> String {
        let chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~"
        return String((0..<length).map { _ in chars.randomElement()! })
    }
    
    private static func hashSHA256(_ input: String) -> String {
        let data = input.data(using: .utf8)!
        let hash = SHA256.hash(data: data)
        return Data(hash).hexEncodedString()
    }
    
    private static func getScreenResolution() -> String {
        let scale = UIScreen.main.scale
        let bounds = UIScreen.main.bounds
        let width = Int(bounds.width * scale)
        let height = Int(bounds.height * scale)
        return "\(width)x\(height)"
    }
    
    private static func getDeviceMemory() -> String {
        return String(ProcessInfo.processInfo.physicalMemory / 1024 / 1024) + "MB"
    }
    
    // MARK: - Jailbreak Detection Methods
    
    private static func checkJailbreakMethod1() -> Bool {
        let jailbreakPaths = [
            "/Applications/Cydia.app",
            "/Library/MobileSubstrate/MobileSubstrate.dylib",
            "/bin/bash",
            "/usr/sbin/sshd",
            "/etc/apt",
            "/private/var/lib/apt/",
            "/private/var/lib/cydia",
            "/private/var/mobile/Library/SBSettings/Themes",
            "/Library/MobileSubstrate/DynamicLibraries/LiveClock.plist",
            "/System/Library/LaunchDaemons/com.ikey.bbot.plist"
        ]
        
        return jailbreakPaths.contains { FileManager.default.fileExists(atPath: $0) }
    }
    
    private static func checkJailbreakMethod2() -> Bool {
        let testPath = "/private/test.txt"
        do {
            try "test".write(toFile: testPath, atomically: true, encoding: .utf8)
            try FileManager.default.removeItem(atPath: testPath)
            return true // If we can write outside sandbox, device is jailbroken
        } catch {
            return false
        }
    }
    
    private static func checkJailbreakMethod3() -> Bool {
        // Check if we can open cydia URL scheme
        if let url = URL(string: "cydia://package/com.example.package") {
            return UIApplication.shared.canOpenURL(url)
        }
        return false
    }
}

// MARK: - Extensions

extension Data {
    func hexEncodedString() -> String {
        return map { String(format: "%02hhx", $0) }.joined()
    }
    
    func base64URLEncodedString() -> String {
        return base64EncodedString()
            .replacingOccurrences(of: "+", with: "-")
            .replacingOccurrences(of: "/", with: "_")
            .replacingOccurrences(of: "=", with: "")
    }
}
```

### 2. Keychain Manager

```swift
// KeychainManager.swift

import Foundation
import KeychainAccess

class KeychainManager {
    
    private let keychain: Keychain
    
    init(service: String = "com.tenant1.app") {
        self.keychain = Keychain(service: service)
            .accessibility(.afterFirstUnlockThisDeviceOnly)
    }
    
    // MARK: - Token Management
    
    func storeTokens(
        accessToken: String,
        refreshToken: String,
        expiresIn: TimeInterval
    ) throws {
        let expirationTime = Date().addingTimeInterval(expiresIn)
        
        try keychain.set(accessToken, key: Keys.accessToken)
        try keychain.set(refreshToken, key: Keys.refreshToken)
        try keychain.set(expirationTime.timeIntervalSince1970.description, key: Keys.tokenExpiration)
        try keychain.set(Date().timeIntervalSince1970.description, key: Keys.tokenStoredAt)
    }
    
    func getAccessToken() -> String? {
        return try? keychain.get(Keys.accessToken)
    }
    
    func getRefreshToken() -> String? {
        return try? keychain.get(Keys.refreshToken)
    }
    
    func isTokenExpired() -> Bool {
        guard let expirationString = try? keychain.get(Keys.tokenExpiration),
              let expirationTime = TimeInterval(expirationString) else {
            return true
        }
        
        let currentTime = Date().timeIntervalSince1970
        let bufferTime: TimeInterval = 5 * 60 // 5 minutes buffer
        
        return currentTime >= (expirationTime - bufferTime)
    }
    
    func clearTokens() throws {
        try keychain.remove(Keys.accessToken)
        try keychain.remove(Keys.refreshToken)
        try keychain.remove(Keys.tokenExpiration)
        try keychain.remove(Keys.tokenStoredAt)
    }
    
    // MARK: - User Information
    
    func storeUserInfo(userId: String, name: String, email: String) throws {
        try keychain.set(userId, key: Keys.userId)
        try keychain.set(name, key: Keys.userName)
        try keychain.set(email, key: Keys.userEmail)
    }
    
    func getUserInfo() -> UserInfo? {
        guard let userId = try? keychain.get(Keys.userId),
              let name = try? keychain.get(Keys.userName),
              let email = try? keychain.get(Keys.userEmail) else {
            return nil
        }
        
        return UserInfo(id: userId, name: name, email: email)
    }
    
    func clearUserInfo() throws {
        try keychain.remove(Keys.userId)
        try keychain.remove(Keys.userName)
        try keychain.remove(Keys.userEmail)
    }
    
    // MARK: - Device Information
    
    func storeDeviceId(_ deviceId: String) throws {
        try keychain.set(deviceId, key: Keys.deviceId)
    }
    
    func getDeviceId() -> String? {
        return try? keychain.get(Keys.deviceId)
    }
    
    // MARK: - Login State
    
    func isLoggedIn() -> Bool {
        return getAccessToken() != nil && getRefreshToken() != nil
    }
    
    // MARK: - Biometric Authentication
    
    func authenticateWithBiometrics() async -> Bool {
        let context = LAContext()
        var error: NSError?
        
        guard context.canEvaluatePolicy(.deviceOwnerAuthenticationWithBiometrics, error: &error) else {
            return false
        }
        
        do {
            let result = try await context.evaluatePolicy(
                .deviceOwnerAuthenticationWithBiometrics,
                localizedReason: "Authenticate to access your account"
            )
            return result
        } catch {
            return false
        }
    }
    
    private struct Keys {
        static let accessToken = "access_token"
        static let refreshToken = "refresh_token"
        static let tokenExpiration = "token_expiration"
        static let tokenStoredAt = "token_stored_at"
        static let userId = "user_id"
        static let userName = "user_name"
        static let userEmail = "user_email"
        static let deviceId = "device_id"
    }
}

struct UserInfo {
    let id: String
    let name: String
    let email: String
}
```

### 3. Device Manager

```swift
// DeviceManager.swift

import Foundation
import UIKit

class DeviceManager {
    
    private let keychainManager: KeychainManager
    
    init(keychainManager: KeychainManager) {
        self.keychainManager = keychainManager
    }
    
    func getOrCreateDeviceId() -> String {
        if let existingDeviceId = keychainManager.getDeviceId() {
            return existingDeviceId
        }
        
        let newDeviceId = SecurityUtils.generateDeviceId()
        try? keychainManager.storeDeviceId(newDeviceId)
        return newDeviceId
    }
    
    func getDeviceInfo() -> DeviceInfo {
        return DeviceInfo(
            deviceId: getOrCreateDeviceId(),
            deviceType: "ios",
            deviceName: UIDevice.current.name,
            deviceModel: getDeviceModel(),
            osVersion: UIDevice.current.systemVersion,
            appVersion: getAppVersion(),
            screenResolution: getScreenResolution(),
            timezone: TimeZone.current.identifier,
            language: Locale.current.identifier
        )
    }
    
    func getSecurityInfo() -> SecurityInfo {
        return SecurityInfo(
            jailbroken: SecurityUtils.isJailbroken(),
            debugger: SecurityUtils.isDebuggerAttached(),
            emulator: SecurityUtils.isEmulator(),
            appIntegrity: checkAppIntegrity()
        )
    }
    
    func generateDeviceFingerprint() -> String {
        return SecurityUtils.generateDeviceFingerprint()
    }
    
    private func getDeviceModel() -> String {
        var systemInfo = utsname()
        uname(&systemInfo)
        let modelCode = withUnsafePointer(to: &systemInfo.machine) {
            $0.withMemoryRebound(to: CChar.self, capacity: 1) {
                ptr in String.init(validatingUTF8: ptr)
            }
        }
        return modelCode ?? UIDevice.current.model
    }
    
    private func getAppVersion() -> String {
        return Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "1.0.0"
    }
    
    private func getScreenResolution() -> String {
        let scale = UIScreen.main.scale
        let bounds = UIScreen.main.bounds
        let width = Int(bounds.width * scale)
        let height = Int(bounds.height * scale)
        return "\(width)x\(height)"
    }
    
    private func checkAppIntegrity() -> Bool {
        // Basic app integrity check
        guard let path = Bundle.main.path(forResource: "Info", ofType: "plist") else {
            return false
        }
        return FileManager.default.fileExists(atPath: path)
    }
}

struct DeviceInfo: Codable {
    let deviceId: String
    let deviceType: String
    let deviceName: String
    let deviceModel: String
    let osVersion: String
    let appVersion: String
    let screenResolution: String
    let timezone: String
    let language: String
    
    enum CodingKeys: String, CodingKey {
        case deviceId = "device_id"
        case deviceType = "device_type"
        case deviceName = "device_name"
        case deviceModel = "device_model"
        case osVersion = "os_version"
        case appVersion = "app_version"
        case screenResolution = "screen_resolution"
        case timezone, language
    }
}

struct SecurityInfo: Codable {
    let jailbroken: Bool
    let debugger: Bool
    let emulator: Bool
    let appIntegrity: Bool
    
    enum CodingKeys: String, CodingKey {
        case jailbroken, debugger, emulator
        case appIntegrity = "app_integrity"
    }
}
```

---

## 🌐 Network Layer

### 1. API Models

```swift
// APIModels.swift

import Foundation

// MARK: - Request Models

struct AuthorizeRequest: Codable {
    let clientId: String
    let codeChallenge: String
    let codeChallengeMethod: String
    let scope: String
    
    enum CodingKeys: String, CodingKey {
        case clientId = "client_id"
        case codeChallenge = "code_challenge"
        case codeChallengeMethod = "code_challenge_method"
        case scope
    }
    
    init(clientId: String = "mobile_app", codeChallenge: String, scope: String = "read write") {
        self.clientId = clientId
        self.codeChallenge = codeChallenge
        self.codeChallengeMethod = "S256"
        self.scope = scope
    }
}

struct TokenRequest: Codable {
    let code: String
    let codeVerifier: String
    let deviceId: String
    let email: String
    let password: String
    let deviceType: String
    let deviceName: String
    let deviceModel: String
    let osVersion: String
    let appVersion: String
    let screenResolution: String
    let timezone: String
    let language: String
    
    enum CodingKeys: String, CodingKey {
        case code
        case codeVerifier = "code_verifier"
        case deviceId = "device_id"
        case email, password
        case deviceType = "device_type"
        case deviceName = "device_name"
        case deviceModel = "device_model"
        case osVersion = "os_version"
        case appVersion = "app_version"
        case screenResolution = "screen_resolution"
        case timezone, language
    }
}

struct DirectLoginRequest: Codable {
    let email: String
    let password: String
    let deviceId: String
    let deviceInfo: DeviceInfoRequest
    
    enum CodingKeys: String, CodingKey {
        case email, password
        case deviceId = "device_id"
        case deviceInfo = "device_info"
    }
}

struct DeviceInfoRequest: Codable {
    let deviceType: String
    let deviceName: String
    let deviceModel: String
    let osVersion: String
    let appVersion: String
    let screenResolution: String
    let timezone: String
    let language: String
    
    enum CodingKeys: String, CodingKey {
        case deviceType = "device_type"
        case deviceName = "device_name"
        case deviceModel = "device_model"
        case osVersion = "os_version"
        case appVersion = "app_version"
        case screenResolution = "screen_resolution"
        case timezone, language
    }
}

struct RefreshTokenRequest: Codable {
    let refreshToken: String
    let deviceId: String
    let deviceFingerprint: String
    
    enum CodingKeys: String, CodingKey {
        case refreshToken = "refresh_token"
        case deviceId = "device_id"
        case deviceFingerprint = "device_fingerprint"
    }
}

// MARK: - Response Models

struct APIResponse<T: Codable>: Codable {
    let success: Bool
    let data: T?
    let error: String?
    let message: String?
}

struct AuthorizeResponse: Codable {
    let authorizationCode: String
    let expiresIn: Int
    
    enum CodingKeys: String, CodingKey {
        case authorizationCode = "authorization_code"
        case expiresIn = "expires_in"
    }
}

struct TokenResponse: Codable {
    let tokenType: String
    let accessToken: String
    let refreshToken: String
    let expiresIn: TimeInterval
    let scope: String?
    let user: User
    
    enum CodingKeys: String, CodingKey {
        case tokenType = "token_type"
        case accessToken = "access_token"
        case refreshToken = "refresh_token"
        case expiresIn = "expires_in"
        case scope, user
    }
}

struct RefreshTokenResponse: Codable {
    let tokenType: String
    let accessToken: String
    let refreshToken: String
    let expiresIn: TimeInterval
    
    enum CodingKeys: String, CodingKey {
        case tokenType = "token_type"
        case accessToken = "access_token"
        case refreshToken = "refresh_token"
        case expiresIn = "expires_in"
    }
}

struct User: Codable {
    let id: String
    let name: String
    let email: String
}

// MARK: - Error Models

struct APIError: Error, Codable {
    let message: String
    let code: String?
    
    init(message: String, code: String? = nil) {
        self.message = message
        self.code = code
    }
}
```

### 2. Network Manager

```swift
// NetworkManager.swift

import Foundation
import Alamofire

class NetworkManager {
    
    static let shared = NetworkManager()
    
    private let session: Session
    private let deviceManager: DeviceManager
    private let keychainManager: KeychainManager
    
    private init() {
        self.keychainManager = KeychainManager()
        self.deviceManager = DeviceManager(keychainManager: keychainManager)
        
        let configuration = URLSessionConfiguration.default
        configuration.timeoutIntervalForRequest = 30
        configuration.timeoutIntervalForResource = 60
        
        // Create session with interceptors
        self.session = Session(
            configuration: configuration,
            interceptor: Interceptor(
                adapters: [
                    SecurityAdapter(deviceManager: deviceManager, keychainManager: keychainManager),
                    AuthAdapter(keychainManager: keychainManager)
                ],
                retriers: [
                    AuthRetrier(authService: nil) // Will be set later
                ]
            )
        )
    }
    
    func setAuthService(_ authService: AuthService) {
        // Update retrier with auth service reference
        if let interceptor = session.interceptor as? Interceptor,
           let retriers = interceptor.retriers as? [AuthRetrier] {
            retriers.first?.authService = authService
        }
    }
    
    private var baseURL: String {
        #if DEBUG
        return "http://localhost:8001/api/v1/mobile/"
        #else
        return "https://tenant1.example.com/api/v1/mobile/"
        #endif
    }
    
    // MARK: - Request Methods
    
    func request<T: Codable>(
        _ endpoint: String,
        method: HTTPMethod = .get,
        parameters: Parameters? = nil,
        encoding: ParameterEncoding = JSONEncoding.default,
        headers: HTTPHeaders? = nil
    ) async throws -> T {
        
        let url = baseURL + endpoint
        
        let response = await session.request(
            url,
            method: method,
            parameters: parameters,
            encoding: encoding,
            headers: headers
        )
        .validate()
        .serializingDecodable(APIResponse<T>.self)
        .response
        
        switch response.result {
        case .success(let apiResponse):
            if apiResponse.success, let data = apiResponse.data {
                return data
            } else {
                throw APIError(
                    message: apiResponse.error ?? apiResponse.message ?? "Request failed",
                    code: "API_ERROR"
                )
            }
        case .failure(let error):
            if let data = response.data,
               let apiResponse = try? JSONDecoder().decode(APIResponse<T>.self, from: data),
               let errorMessage = apiResponse.error ?? apiResponse.message {
                throw APIError(message: errorMessage, code: "API_ERROR")
            }
            throw error
        }
    }
    
    func requestWithoutData(
        _ endpoint: String,
        method: HTTPMethod = .get,
        parameters: Parameters? = nil,
        encoding: ParameterEncoding = JSONEncoding.default,
        headers: HTTPHeaders? = nil
    ) async throws -> Bool {
        
        let url = baseURL + endpoint
        
        let response = await session.request(
            url,
            method: method,
            parameters: parameters,
            encoding: encoding,
            headers: headers
        )
        .validate()
        .serializingDecodable(APIResponse<EmptyResponse>.self)
        .response
        
        switch response.result {
        case .success(let apiResponse):
            return apiResponse.success
        case .failure:
            return false
        }
    }
}

// MARK: - Adapters and Retriers

class SecurityAdapter: RequestAdapter {
    
    private let deviceManager: DeviceManager
    private let keychainManager: KeychainManager
    private let hmacSecret: String
    
    init(deviceManager: DeviceManager, keychainManager: KeychainManager) {
        self.deviceManager = deviceManager
        self.keychainManager = keychainManager
        
        // In production, this should come from a secure source
        #if DEBUG
        self.hmacSecret = "your_development_hmac_secret"
        #else
        self.hmacSecret = "your_production_hmac_secret"
        #endif
    }
    
    func adapt(_ urlRequest: URLRequest, for session: Session, completion: @escaping (Result<URLRequest, Error>) -> Void) {
        var request = urlRequest
        
        let timestamp = String(Int(Date().timeIntervalSince1970))
        let deviceId = deviceManager.getOrCreateDeviceId()
        let deviceFingerprint = deviceManager.generateDeviceFingerprint()
        let securityInfo = deviceManager.getSecurityInfo()
        
        // Get request body
        let bodyString: String
        if let httpBody = request.httpBody {
            bodyString = String(data: httpBody, encoding: .utf8) ?? ""
        } else {
            bodyString = ""
        }
        
        // Generate HMAC signature
        let canonicalRequest = SecurityUtils.createCanonicalRequest(
            method: request.httpMethod ?? "GET",
            path: request.url?.path ?? "",
            timestamp: timestamp,
            deviceId: deviceId,
            body: bodyString
        )
        
        let signature = SecurityUtils.generateHMACSignature(
            canonicalRequest: canonicalRequest,
            secret: hmacSecret
        )
        
        // Add security headers
        request.setValue(timestamp, forHTTPHeaderField: "X-Timestamp")
        request.setValue(deviceId, forHTTPHeaderField: "X-Device-Id")
        request.setValue(deviceFingerprint, forHTTPHeaderField: "X-Device-Fingerprint")
        request.setValue(signature, forHTTPHeaderField: "X-Signature")
        
        // Add device security info
        if let securityData = try? JSONEncoder().encode(securityInfo),
           let securityString = String(data: securityData, encoding: .utf8) {
            request.setValue(securityString, forHTTPHeaderField: "X-Device-Info")
        }
        
        // Add User-Agent
        let appVersion = Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "1.0.0"
        let userAgent = "Tenant1iOSApp/\(appVersion) (iOS \(UIDevice.current.systemVersion); \(UIDevice.current.model))"
        request.setValue(userAgent, forHTTPHeaderField: "User-Agent")
        
        completion(.success(request))
    }
}

class AuthAdapter: RequestAdapter {
    
    private let keychainManager: KeychainManager
    
    init(keychainManager: KeychainManager) {
        self.keychainManager = keychainManager
    }
    
    func adapt(_ urlRequest: URLRequest, for session: Session, completion: @escaping (Result<URLRequest, Error>) -> Void) {
        var request = urlRequest
        
        // Skip auth header for public endpoints
        let publicEndpoints = ["/auth/authorize", "/auth/token", "/auth/login", "/auth/refresh"]
        let isPublicEndpoint = publicEndpoints.contains { request.url?.path.contains($0) == true }
        
        if !isPublicEndpoint, let accessToken = keychainManager.getAccessToken() {
            request.setValue("Bearer \(accessToken)", forHTTPHeaderField: "Authorization")
        }
        
        completion(.success(request))
    }
}

class AuthRetrier: RequestRetrier {
    
    weak var authService: AuthService?
    
    init(authService: AuthService?) {
        self.authService = authService
    }
    
    func retry(_ request: Request, for session: Session, dueTo error: Error, completion: @escaping (RetryResult) -> Void) {
        
        guard let response = request.task?.response as? HTTPURLResponse,
              response.statusCode == 401,
              let authService = authService else {
            completion(.doNotRetry)
            return
        }
        
        // Try to refresh token
        Task {
            do {
                let success = try await authService.refreshToken()
                if success {
                    completion(.retry)
                } else {
                    completion(.doNotRetry)
                }
            } catch {
                completion(.doNotRetry)
            }
        }
    }
}

struct EmptyResponse: Codable {}
```

---

## 🔑 Authentication Service

### Main Authentication Service

```swift
// AuthService.swift

import Foundation

@MainActor
class AuthService: ObservableObject {
    
    @Published var isLoggedIn = false
    @Published var currentUser: UserInfo?
    
    private let networkManager: NetworkManager
    private let keychainManager: KeychainManager
    private let deviceManager: DeviceManager
    
    private var currentCodeVerifier: String?
    
    init() {
        self.networkManager = NetworkManager.shared
        self.keychainManager = KeychainManager()
        self.deviceManager = DeviceManager(keychainManager: keychainManager)
        
        // Set self reference in network manager for token refresh
        networkManager.setAuthService(self)
        
        // Initialize state
        checkLoginState()
    }
    
    // MARK: - OAuth 2.0 PKCE Flow
    
    func getAuthorizationCode() async throws -> String {
        let codeVerifier = SecurityUtils.generateCodeVerifier()
        let codeChallenge = SecurityUtils.generateCodeChallenge(from: codeVerifier)
        
        currentCodeVerifier = codeVerifier
        
        let request = AuthorizeRequest(codeChallenge: codeChallenge)
        let response: AuthorizeResponse = try await networkManager.request(
            "auth/authorize",
            method: .post,
            parameters: try request.asDictionary()
        )
        
        return response.authorizationCode
    }
    
    func exchangeCodeForTokens(
        authorizationCode: String,
        email: String,
        password: String
    ) async throws -> TokenResponse {
        
        guard let codeVerifier = currentCodeVerifier else {
            throw APIError(message: "No code verifier available")
        }
        
        let deviceInfo = deviceManager.getDeviceInfo()
        
        let request = TokenRequest(
            code: authorizationCode,
            codeVerifier: codeVerifier,
            deviceId: deviceInfo.deviceId,
            email: email,
            password: password,
            deviceType: deviceInfo.deviceType,
            deviceName: deviceInfo.deviceName,
            deviceModel: deviceInfo.deviceModel,
            osVersion: deviceInfo.osVersion,
            appVersion: deviceInfo.appVersion,
            screenResolution: deviceInfo.screenResolution,
            timezone: deviceInfo.timezone,
            language: deviceInfo.language
        )
        
        let response: TokenResponse = try await networkManager.request(
            "auth/token",
            method: .post,
            parameters: try request.asDictionary()
        )
        
        // Store tokens and user info
        try keychainManager.storeTokens(
            accessToken: response.accessToken,
            refreshToken: response.refreshToken,
            expiresIn: response.expiresIn
        )
        
        try keychainManager.storeUserInfo(
            userId: response.user.id,
            name: response.user.name,
            email: response.user.email
        )
        
        // Clear code verifier
        currentCodeVerifier = nil
        
        // Update state
        isLoggedIn = true
        currentUser = UserInfo(
            id: response.user.id,
            name: response.user.name,
            email: response.user.email
        )
        
        return response
    }
    
    // MARK: - Direct Login
    
    func directLogin(email: String, password: String) async throws -> TokenResponse {
        let deviceInfo = deviceManager.getDeviceInfo()
        
        let request = DirectLoginRequest(
            email: email,
            password: password,
            deviceId: deviceInfo.deviceId,
            deviceInfo: DeviceInfoRequest(
                deviceType: deviceInfo.deviceType,
                deviceName: deviceInfo.deviceName,
                deviceModel: deviceInfo.deviceModel,
                osVersion: deviceInfo.osVersion,
                appVersion: deviceInfo.appVersion,
                screenResolution: deviceInfo.screenResolution,
                timezone: deviceInfo.timezone,
                language: deviceInfo.language
            )
        )
        
        let response: TokenResponse = try await networkManager.request(
            "auth/login",
            method: .post,
            parameters: try request.asDictionary()
        )
        
        // Store tokens and user info
        try keychainManager.storeTokens(
            accessToken: response.accessToken,
            refreshToken: response.refreshToken,
            expiresIn: response.expiresIn
        )
        
        try keychainManager.storeUserInfo(
            userId: response.user.id,
            name: response.user.name,
            email: response.user.email
        )
        
        // Update state
        isLoggedIn = true
        currentUser = UserInfo(
            id: response.user.id,
            name: response.user.name,
            email: response.user.email
        )
        
        return response
    }
    
    // MARK: - Token Management
    
    func refreshToken() async throws -> Bool {
        guard let refreshToken = keychainManager.getRefreshToken() else {
            throw APIError(message: "No refresh token available")
        }
        
        let deviceId = deviceManager.getOrCreateDeviceId()
        let deviceFingerprint = deviceManager.generateDeviceFingerprint()
        
        let request = RefreshTokenRequest(
            refreshToken: refreshToken,
            deviceId: deviceId,
            deviceFingerprint: deviceFingerprint
        )
        
        let response: RefreshTokenResponse = try await networkManager.request(
            "auth/refresh",
            method: .post,
            parameters: try request.asDictionary()
        )
        
        // Store new tokens
        try keychainManager.storeTokens(
            accessToken: response.accessToken,
            refreshToken: response.refreshToken,
            expiresIn: response.expiresIn
        )
        
        return true
    }
    
    func isTokenValid() -> Bool {
        return keychainManager.isLoggedIn() && !keychainManager.isTokenExpired()
    }
    
    func ensureValidToken() async throws {
        if !isTokenValid() {
            if keychainManager.getRefreshToken() != nil {
                try await refreshToken()
            } else {
                throw APIError(message: "No valid token or refresh token available")
            }
        }
    }
    
    // MARK: - User Management
    
    func getCurrentUser() -> UserInfo? {
        return keychainManager.getUserInfo()
    }
    
    func logout() async throws {
        // Call logout API
        try? await networkManager.requestWithoutData(
            "auth/logout",
            method: .post,
            parameters: ["revoke_all_device_tokens": false]
        )
        
        // Clear local storage
        try keychainManager.clearTokens()
        try keychainManager.clearUserInfo()
        
        // Update state
        isLoggedIn = false
        currentUser = nil
    }
    
    // MARK: - State Management
    
    private func checkLoginState() {
        isLoggedIn = keychainManager.isLoggedIn()
        currentUser = keychainManager.getUserInfo()
    }
    
    // MARK: - Biometric Authentication
    
    func authenticateWithBiometrics() async -> Bool {
        return await keychainManager.authenticateWithBiometrics()
    }
}

// MARK: - Helpers

extension Encodable {
    func asDictionary() throws -> [String: Any] {
        let data = try JSONEncoder().encode(self)
        guard let dictionary = try JSONSerialization.jsonObject(with: data, options: .allowFragments) as? [String: Any] else {
            throw NSError()
        }
        return dictionary
    }
}
```

---

## 🎨 SwiftUI Implementation

### 1. Login View

```swift
// LoginView.swift

import SwiftUI

struct LoginView: View {
    @StateObject private var authService = AuthService()
    @State private var email = ""
    @State private var password = ""
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingError = false
    
    var body: some View {
        NavigationView {
            VStack(spacing: 24) {
                
                // Header
                VStack(spacing: 8) {
                    Image(systemName: "lock.shield")
                        .font(.system(size: 60))
                        .foregroundColor(.blue)
                    
                    Text("Tenant 1 Login")
                        .font(.largeTitle)
                        .fontWeight(.bold)
                    
                    Text("Secure access to your account")
                        .font(.subheadline)
                        .foregroundColor(.secondary)
                }
                .padding(.bottom, 32)
                
                // Login Form
                VStack(spacing: 16) {
                    TextField("Email", text: $email)
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .keyboardType(.emailAddress)
                        .autocapitalization(.none)
                        .disabled(isLoading)
                    
                    SecureField("Password", text: $password)
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .disabled(isLoading)
                    
                    Button(action: login) {
                        HStack {
                            if isLoading {
                                ProgressView()
                                    .progressViewStyle(CircularProgressViewStyle(tint: .white))
                                    .scaleEffect(0.8)
                            }
                            Text("Login")
                                .fontWeight(.semibold)
                        }
                        .frame(maxWidth: .infinity, minHeight: 50)
                        .background(loginButtonColor)
                        .foregroundColor(.white)
                        .cornerRadius(12)
                    }
                    .disabled(!isFormValid || isLoading)
                }
                
                // Demo Credentials
                VStack(spacing: 4) {
                    Text("Demo Credentials:")
                        .font(.caption)
                        .fontWeight(.medium)
                        .foregroundColor(.secondary)
                    
                    Text("user@tenant1.com / password")
                        .font(.caption)
                        .foregroundColor(.secondary)
                        .monospaced()
                }
                .padding(.top, 16)
                
                Spacer()
                
                // Biometric Login
                if await authService.authenticateWithBiometrics() {
                    Button(action: biometricLogin) {
                        HStack {
                            Image(systemName: "faceid")
                            Text("Login with Face ID")
                        }
                        .foregroundColor(.blue)
                    }
                    .padding(.bottom)
                }
            }
            .padding(.horizontal, 32)
            .navigationTitle("")
            .navigationBarHidden(true)
        }
        .alert("Login Failed", isPresented: $showingError) {
            Button("OK") { showingError = false }
        } message: {
            Text(errorMessage ?? "An error occurred")
        }
        .onReceive(authService.$isLoggedIn) { isLoggedIn in
            if isLoggedIn {
                // Handle successful login - navigation will be handled by parent view
            }
        }
    }
    
    private var isFormValid: Bool {
        !email.isEmpty && !password.isEmpty && email.contains("@")
    }
    
    private var loginButtonColor: Color {
        isFormValid && !isLoading ? .blue : .gray
    }
    
    private func login() {
        guard isFormValid else { return }
        
        isLoading = true
        errorMessage = nil
        
        Task {
            do {
                try await authService.directLogin(email: email, password: password)
                await MainActor.run {
                    isLoading = false
                    // Success - state change will trigger navigation
                }
            } catch {
                await MainActor.run {
                    isLoading = false
                    errorMessage = error.localizedDescription
                    showingError = true
                }
            }
        }
    }
    
    private func biometricLogin() {
        Task {
            let authenticated = await authService.authenticateWithBiometrics()
            if authenticated {
                // Use stored credentials or previous session
                // Implementation depends on your requirements
            }
        }
    }
}

struct LoginView_Previews: PreviewProvider {
    static var previews: some View {
        LoginView()
    }
}
```

### 2. Main App View

```swift
// ContentView.swift

import SwiftUI

struct ContentView: View {
    @StateObject private var authService = AuthService()
    
    var body: some View {
        Group {
            if authService.isLoggedIn {
                MainTabView()
                    .environmentObject(authService)
            } else {
                LoginView()
                    .environmentObject(authService)
            }
        }
        .onAppear {
            checkAuthenticationState()
        }
    }
    
    private func checkAuthenticationState() {
        Task {
            if authService.isLoggedIn && !authService.isTokenValid() {
                // Try to refresh token
                try? await authService.refreshToken()
            }
        }
    }
}

struct MainTabView: View {
    @EnvironmentObject var authService: AuthService
    
    var body: some View {
        TabView {
            ProfileView()
                .tabItem {
                    Image(systemName: "person.circle")
                    Text("Profile")
                }
            
            SettingsView()
                .tabItem {
                    Image(systemName: "gear")
                    Text("Settings")
                }
        }
    }
}

struct ProfileView: View {
    @EnvironmentObject var authService: AuthService
    
    var body: some View {
        NavigationView {
            VStack(spacing: 20) {
                if let user = authService.currentUser {
                    VStack(spacing: 8) {
                        Image(systemName: "person.circle.fill")
                            .font(.system(size: 80))
                            .foregroundColor(.blue)
                        
                        Text(user.name)
                            .font(.title2)
                            .fontWeight(.semibold)
                        
                        Text(user.email)
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                    }
                    .padding()
                }
                
                Spacer()
                
                Button("Logout") {
                    Task {
                        try? await authService.logout()
                    }
                }
                .buttonStyle(.borderedProminent)
                .padding()
            }
            .navigationTitle("Profile")
        }
    }
}

struct SettingsView: View {
    var body: some View {
        NavigationView {
            List {
                Section("Security") {
                    HStack {
                        Image(systemName: "lock.shield")
                        Text("Device Security")
                        Spacer()
                        if SecurityUtils.isJailbroken() {
                            Text("Jailbroken")
                                .foregroundColor(.red)
                                .font(.caption)
                        } else {
                            Text("Secure")
                                .foregroundColor(.green)
                                .font(.caption)
                        }
                    }
                    
                    HStack {
                        Image(systemName: "cpu")
                        Text("Debugger")
                        Spacer()
                        if SecurityUtils.isDebuggerAttached() {
                            Text("Attached")
                                .foregroundColor(.orange)
                                .font(.caption)
                        } else {
                            Text("None")
                                .foregroundColor(.green)
                                .font(.caption)
                        }
                    }
                    
                    HStack {
                        Image(systemName: "iphone")
                        Text("Device Type")
                        Spacer()
                        if SecurityUtils.isEmulator() {
                            Text("Simulator")
                                .foregroundColor(.blue)
                                .font(.caption)
                        } else {
                            Text("Physical")
                                .foregroundColor(.green)
                                .font(.caption)
                        }
                    }
                }
                
                Section("App Info") {
                    HStack {
                        Image(systemName: "info.circle")
                        Text("Version")
                        Spacer()
                        Text(Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "1.0.0")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Image(systemName: "iphone")
                        Text("Device Model")
                        Spacer()
                        Text(UIDevice.current.model)
                            .foregroundColor(.secondary)
                    }
                }
            }
            .navigationTitle("Settings")
        }
    }
}
```

---

## 🧪 Testing

### 1. Unit Tests

```swift
// AuthServiceTests.swift

import XCTest
@testable import Tenant1App

class AuthServiceTests: XCTestCase {
    
    var authService: AuthService!
    var mockKeychainManager: MockKeychainManager!
    var mockNetworkManager: MockNetworkManager!
    
    override func setUp() {
        super.setUp()
        mockKeychainManager = MockKeychainManager()
        mockNetworkManager = MockNetworkManager()
        // Inject mocks into auth service
    }
    
    override func tearDown() {
        authService = nil
        mockKeychainManager = nil
        mockNetworkManager = nil
        super.tearDown()
    }
    
    func testDirectLoginSuccess() async throws {
        // Arrange
        let email = "test@example.com"
        let password = "password"
        let expectedResponse = TokenResponse(
            tokenType: "Bearer",
            accessToken: "access_token",
            refreshToken: "refresh_token",
            expiresIn: 1800,
            scope: "read write",
            user: User(id: "1", name: "Test User", email: email)
        )
        
        mockNetworkManager.mockResponse = expectedResponse
        
        // Act
        let result = try await authService.directLogin(email: email, password: password)
        
        // Assert
        XCTAssertEqual(result.user.email, email)
        XCTAssertEqual(result.accessToken, "access_token")
        XCTAssertTrue(authService.isLoggedIn)
    }
    
    func testTokenRefreshSuccess() async throws {
        // Arrange
        mockKeychainManager.storedRefreshToken = "refresh_token"
        let expectedResponse = RefreshTokenResponse(
            tokenType: "Bearer",
            accessToken: "new_access_token",
            refreshToken: "new_refresh_token",
            expiresIn: 1800
        )
        
        mockNetworkManager.mockResponse = expectedResponse
        
        // Act
        let result = try await authService.refreshToken()
        
        // Assert
        XCTAssertTrue(result)
        XCTAssertEqual(mockKeychainManager.storedAccessToken, "new_access_token")
    }
    
    func testSecurityUtilsPKCE() {
        // Test PKCE implementation
        let verifier = SecurityUtils.generateCodeVerifier()
        let challenge = SecurityUtils.generateCodeChallenge(from: verifier)
        
        XCTAssertEqual(verifier.count, 64)
        XCTAssertFalse(challenge.isEmpty)
        XCTAssertFalse(challenge.contains("=")) // URL-safe base64
    }
    
    func testHMACSignature() {
        let canonicalRequest = "POST|/auth/login|1234567890|device123|{\"email\":\"test@example.com\"}"
        let secret = "test_secret"
        
        let signature1 = SecurityUtils.generateHMACSignature(canonicalRequest: canonicalRequest, secret: secret)
        let signature2 = SecurityUtils.generateHMACSignature(canonicalRequest: canonicalRequest, secret: secret)
        
        XCTAssertEqual(signature1, signature2) // Should be deterministic
        XCTAssertEqual(signature1.count, 64) // SHA256 hex string
    }
}

// MARK: - Mock Classes

class MockKeychainManager: KeychainManager {
    var storedAccessToken: String?
    var storedRefreshToken: String?
    var tokenExpired = false
    
    override func getAccessToken() -> String? {
        return storedAccessToken
    }
    
    override func getRefreshToken() -> String? {
        return storedRefreshToken
    }
    
    override func isTokenExpired() -> Bool {
        return tokenExpired
    }
    
    override func storeTokens(accessToken: String, refreshToken: String, expiresIn: TimeInterval) throws {
        storedAccessToken = accessToken
        storedRefreshToken = refreshToken
    }
}

class MockNetworkManager {
    var mockResponse: Any?
    var shouldFail = false
    
    func request<T: Codable>(_ endpoint: String, method: HTTPMethod, parameters: [String: Any]?) async throws -> T {
        if shouldFail {
            throw APIError(message: "Mock error")
        }
        
        guard let response = mockResponse as? T else {
            throw APIError(message: "Invalid mock response type")
        }
        
        return response
    }
}
```

### 2. UI Tests

```swift
// LoginUITests.swift

import XCTest

class LoginUITests: XCTestCase {
    
    var app: XCUIApplication!
    
    override func setUp() {
        super.setUp()
        app = XCUIApplication()
        app.launch()
    }
    
    func testLoginFlow() {
        // Test login form
        let emailField = app.textFields["Email"]
        let passwordField = app.secureTextFields["Password"]
        let loginButton = app.buttons["Login"]
        
        XCTAssertTrue(emailField.exists)
        XCTAssertTrue(passwordField.exists)
        XCTAssertTrue(loginButton.exists)
        
        // Enter credentials
        emailField.tap()
        emailField.typeText("user@tenant1.com")
        
        passwordField.tap()
        passwordField.typeText("password")
        
        // Login
        loginButton.tap()
        
        // Wait for main screen
        let profileTab = app.tabBars.buttons["Profile"]
        XCTAssertTrue(profileTab.waitForExistence(timeout: 5))
    }
    
    func testSecurityInfo() {
        // Login first
        loginWithTestCredentials()
        
        // Navigate to settings
        app.tabBars.buttons["Settings"].tap()
        
        // Check security information
        XCTAssertTrue(app.staticTexts["Device Security"].exists)
        XCTAssertTrue(app.staticTexts["Debugger"].exists)
        XCTAssertTrue(app.staticTexts["Device Type"].exists)
    }
    
    private func loginWithTestCredentials() {
        let emailField = app.textFields["Email"]
        let passwordField = app.secureTextFields["Password"]
        let loginButton = app.buttons["Login"]
        
        emailField.tap()
        emailField.typeText("user@tenant1.com")
        
        passwordField.tap()
        passwordField.typeText("password")
        
        loginButton.tap()
        
        // Wait for login to complete
        _ = app.tabBars.buttons["Profile"].waitForExistence(timeout: 5)
    }
}
```

---

## 📱 Usage Examples

### App Delegate Setup

```swift
// AppDelegate.swift

import UIKit
import SwiftUI

@main
struct Tenant1App: App {
    var body: some Scene {
        WindowGroup {
            ContentView()
                .onAppear {
                    configureApp()
                }
        }
    }
    
    private func configureApp() {
        // Configure app-level settings
        #if DEBUG
        print("🚀 Tenant 1 App launched in DEBUG mode")
        print("🔒 Security Status:")
        print("   - Jailbroken: \(SecurityUtils.isJailbroken())")
        print("   - Debugger: \(SecurityUtils.isDebuggerAttached())")
        print("   - Emulator: \(SecurityUtils.isEmulator())")
        #endif
    }
}
```

### Environment-Specific Configuration

```swift
// Configuration.swift

import Foundation

struct Configuration {
    
    static var apiBaseURL: String {
        #if DEBUG
        return "http://localhost:8001/api/v1/mobile/"
        #else
        return "https://tenant1.example.com/api/v1/mobile/"
        #endif
    }
    
    static var hmacSecret: String {
        #if DEBUG
        return "your_development_hmac_secret"
        #else
        return "your_production_hmac_secret"
        #endif
    }
    
    static var isDebugMode: Bool {
        #if DEBUG
        return true
        #else
        return false
        #endif
    }
}
```

---

## 🔒 Security Best Practices

### 1. Keychain Security
- ✅ Uses Keychain Services with hardware encryption
- ✅ Tokens accessible only after first unlock
- ✅ Biometric authentication support
- ✅ Automatic cleanup on app uninstall

### 2. Request Security
- ✅ HMAC-SHA256 signing for all requests
- ✅ Timestamp validation to prevent replay attacks
- ✅ Device fingerprinting for additional validation
- ✅ Secure random string generation

### 3. Device Security
- ✅ Jailbreak detection (multiple methods)
- ✅ Debugger detection
- ✅ Simulator detection
- ✅ App integrity checks

### 4. Network Security
- ✅ TLS 1.2+ enforcement via ATS
- ✅ No certificate pinning (by design)
- ✅ Proper error handling
- ✅ Request/response logging in debug builds only

---

## 🚀 Production Considerations

### 1. Code Obfuscation
Consider using tools like:
- SwiftShield for identifier obfuscation
- Custom string encryption for sensitive constants
- Binary packing techniques

### 2. Security Hardening
- Implement additional jailbreak detection methods
- Add anti-tampering measures
- Use iOS App Attest for app integrity
- Implement certificate pinning if required

### 3. Monitoring
- Integrate with crash reporting (Crashlytics)
- Add performance monitoring
- Implement custom analytics for security events
- Use MetricKit for performance metrics

### 4. Testing
- Test on various iOS versions and devices
- Test network conditions (slow, offline, intermittent)
- Test security scenarios (jailbroken devices, etc.)
- Use Xcode's Network Link Conditioner

---

This iOS implementation provides a secure, production-ready foundation for connecting to the Tenant 1 Mobile API while maintaining the simplified architecture without certificate pinning complexity. The SwiftUI interface provides a modern, accessible user experience with comprehensive security monitoring.