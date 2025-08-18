# React Native Mobile SDK Implementation

Complete React Native implementation for secure mobile API connectivity with Tenant 1 SSO system.

## 📋 Table of Contents

- [Installation & Setup](#installation--setup)
- [Core SDK Structure](#core-sdk-structure)
- [Security Implementation](#security-implementation)
- [Authentication Service](#authentication-service)
- [API Client](#api-client)
- [Secure Storage](#secure-storage)
- [Device Security](#device-security)
- [Usage Examples](#usage-examples)
- [Error Handling](#error-handling)
- [Testing](#testing)

## 🚀 Installation & Setup

### Dependencies

```bash
npm install @react-native-async-storage/async-storage
npm install react-native-keychain
npm install react-native-device-info
npm install crypto-js
npm install react-native-get-random-values

# For jailbreak/root detection
npm install jail-monkey

# For biometric authentication (optional)
npm install react-native-biometrics

# Development dependencies
npm install --save-dev @types/crypto-js
```

### Platform Configuration

#### iOS Setup (ios/Podfile)
```ruby
# Add to Podfile
pod 'RNKeychain', :path => '../node_modules/react-native-keychain'
```

#### Android Setup (android/app/build.gradle)
```gradle
android {
    compileSdkVersion 34
    
    defaultConfig {
        minSdkVersion 21
        targetSdkVersion 34
    }
}

dependencies {
    implementation project(':react-native-keychain')
}
```

### Metro Configuration (metro.config.js)
```javascript
const { getDefaultConfig } = require('expo/metro-config');

const config = getDefaultConfig(__dirname);

// Add polyfill for crypto
config.resolver.alias = {
  ...config.resolver.alias,
  'crypto': 'react-native-quick-crypto',
};

module.exports = config;
```

## 🏗️ Core SDK Structure

### Project Structure
```
src/
├── services/
│   ├── AuthService.ts
│   ├── ApiClient.ts
│   ├── SecurityService.ts
│   └── StorageService.ts
├── utils/
│   ├── CryptoUtils.ts
│   ├── DeviceUtils.ts
│   └── ValidationUtils.ts
├── types/
│   ├── auth.types.ts
│   ├── api.types.ts
│   └── security.types.ts
├── constants/
│   └── config.ts
└── hooks/
    ├── useAuth.ts
    └── useSecureApi.ts
```

## 🔒 Security Implementation

### CryptoUtils.ts
```typescript
import CryptoJS from 'crypto-js';
import { getRandomValues } from 'react-native-get-random-values';
import base64 from 'base-64';

export class CryptoUtils {
  
  /**
   * Generate PKCE code verifier
   */
  static generateCodeVerifier(): string {
    const array = new Uint8Array(32);
    getRandomValues(array);
    return this.base64URLEncode(array);
  }

  /**
   * Generate PKCE code challenge
   */
  static generateCodeChallenge(verifier: string): string {
    const hash = CryptoJS.SHA256(verifier);
    return this.base64URLEncode(new Uint8Array(hash.words.map(word => [
      (word >> 24) & 0xff,
      (word >> 16) & 0xff,
      (word >> 8) & 0xff,
      word & 0xff
    ]).flat()));
  }

  /**
   * Generate HMAC signature for API requests
   */
  static generateHMACSignature(
    method: string,
    path: string,
    body: string,
    timestamp: string,
    hmacSecret: string
  ): string {
    const canonicalRequest = `${method.toUpperCase()}\n${path}\n${body}\n${timestamp}`;
    return CryptoJS.HmacSHA256(canonicalRequest, hmacSecret).toString();
  }

  /**
   * Generate device fingerprint
   */
  static async generateDeviceFingerprint(): Promise<string> {
    const DeviceInfo = require('react-native-device-info');
    
    const deviceData = {
      deviceId: await DeviceInfo.getUniqueId(),
      brand: DeviceInfo.getBrand(),
      model: DeviceInfo.getModel(),
      systemVersion: DeviceInfo.getSystemVersion(),
      buildNumber: DeviceInfo.getBuildNumber(),
      bundleId: DeviceInfo.getBundleId(),
    };

    const dataString = JSON.stringify(deviceData);
    return CryptoJS.SHA256(dataString).toString();
  }

  /**
   * Base64 URL encoding (RFC 4648)
   */
  private static base64URLEncode(buffer: Uint8Array): string {
    const base64String = base64.encode(String.fromCharCode.apply(null, Array.from(buffer)));
    return base64String
      .replace(/\+/g, '-')
      .replace(/\//g, '_')
      .replace(/=/g, '');
  }

  /**
   * Encrypt sensitive data for storage
   */
  static encryptForStorage(data: string, key: string): string {
    return CryptoJS.AES.encrypt(data, key).toString();
  }

  /**
   * Decrypt sensitive data from storage
   */
  static decryptFromStorage(encryptedData: string, key: string): string {
    const bytes = CryptoJS.AES.decrypt(encryptedData, key);
    return bytes.toString(CryptoJS.enc.Utf8);
  }
}
```

### DeviceUtils.ts
```typescript
import DeviceInfo from 'react-native-device-info';
import JailMonkey from 'jail-monkey';

export interface DeviceSecurityInfo {
  isJailbroken: boolean;
  isRooted: boolean;
  isDebugging: boolean;
  isEmulator: boolean;
  hasHooks: boolean;
  deviceId: string;
  securityLevel: 'HIGH' | 'MEDIUM' | 'LOW' | 'CRITICAL';
}

export class DeviceUtils {
  
  /**
   * Comprehensive device security check
   */
  static async getDeviceSecurityInfo(): Promise<DeviceSecurityInfo> {
    const isJailbroken = JailMonkey.isJailBroken();
    const isRooted = JailMonkey.isJailBroken(); // Works for both iOS and Android
    const isDebugging = await JailMonkey.isDebuggedMode();
    const isEmulator = await DeviceInfo.isEmulator();
    const hasHooks = JailMonkey.hookDetected();
    const deviceId = await DeviceInfo.getUniqueId();

    const securityLevel = this.calculateSecurityLevel({
      isJailbroken,
      isRooted,
      isDebugging,
      isEmulator,
      hasHooks
    });

    return {
      isJailbroken,
      isRooted,
      isDebugging,
      isEmulator,
      hasHooks,
      deviceId,
      securityLevel
    };
  }

  /**
   * Calculate overall security level
   */
  private static calculateSecurityLevel(checks: Partial<DeviceSecurityInfo>): 'HIGH' | 'MEDIUM' | 'LOW' | 'CRITICAL' {
    let riskScore = 0;

    if (checks.isJailbroken || checks.isRooted) riskScore += 3;
    if (checks.isDebugging) riskScore += 2;
    if (checks.isEmulator) riskScore += 2;
    if (checks.hasHooks) riskScore += 3;

    if (riskScore >= 5) return 'CRITICAL';
    if (riskScore >= 3) return 'LOW';
    if (riskScore >= 1) return 'MEDIUM';
    return 'HIGH';
  }

  /**
   * Check if device meets minimum security requirements
   */
  static async meetsSecurityRequirements(): Promise<boolean> {
    const securityInfo = await this.getDeviceSecurityInfo();
    return securityInfo.securityLevel !== 'CRITICAL';
  }

  /**
   * Get device information for API requests
   */
  static async getDeviceInfo() {
    return {
      deviceType: await DeviceInfo.getDeviceType(),
      systemName: DeviceInfo.getSystemName(),
      systemVersion: DeviceInfo.getSystemVersion(),
      appVersion: DeviceInfo.getVersion(),
      buildNumber: DeviceInfo.getBuildNumber(),
      brand: DeviceInfo.getBrand(),
      model: DeviceInfo.getModel(),
    };
  }
}
```

## 🔐 Secure Storage Service

### StorageService.ts
```typescript
import AsyncStorage from '@react-native-async-storage/async-storage';
import Keychain from 'react-native-keychain';
import { CryptoUtils } from '../utils/CryptoUtils';

export interface SecureStorageOptions {
  accessGroup?: string;
  accessible?: Keychain.ACCESSIBLE;
  authenticationType?: Keychain.AUTHENTICATION_TYPE;
}

export class StorageService {
  private static readonly ENCRYPTION_KEY = 'tenant1_mobile_key';
  
  /**
   * Store sensitive data in iOS Keychain / Android Keystore
   */
  static async setSecureItem(
    key: string, 
    value: string, 
    options: SecureStorageOptions = {}
  ): Promise<void> {
    try {
      await Keychain.setInternetCredentials(
        key,
        key,
        value,
        {
          accessible: options.accessible || Keychain.ACCESSIBLE.WHEN_UNLOCKED,
          authenticationType: options.authenticationType || Keychain.AUTHENTICATION_TYPE.DEVICE_PASSCODE_OR_BIOMETRICS,
          accessGroup: options.accessGroup,
        }
      );
    } catch (error) {
      console.error('Failed to store secure item:', error);
      throw new Error('Secure storage failed');
    }
  }

  /**
   * Retrieve sensitive data from secure storage
   */
  static async getSecureItem(key: string): Promise<string | null> {
    try {
      const credentials = await Keychain.getInternetCredentials(key);
      if (credentials && credentials.password) {
        return credentials.password;
      }
      return null;
    } catch (error) {
      console.error('Failed to retrieve secure item:', error);
      return null;
    }
  }

  /**
   * Remove item from secure storage
   */
  static async removeSecureItem(key: string): Promise<void> {
    try {
      await Keychain.resetInternetCredentials(key);
    } catch (error) {
      console.error('Failed to remove secure item:', error);
    }
  }

  /**
   * Store encrypted data in AsyncStorage
   */
  static async setEncryptedItem(key: string, value: string): Promise<void> {
    try {
      const encrypted = CryptoUtils.encryptForStorage(value, this.ENCRYPTION_KEY);
      await AsyncStorage.setItem(key, encrypted);
    } catch (error) {
      console.error('Failed to store encrypted item:', error);
      throw new Error('Encrypted storage failed');
    }
  }

  /**
   * Retrieve and decrypt data from AsyncStorage
   */
  static async getEncryptedItem(key: string): Promise<string | null> {
    try {
      const encrypted = await AsyncStorage.getItem(key);
      if (encrypted) {
        return CryptoUtils.decryptFromStorage(encrypted, this.ENCRYPTION_KEY);
      }
      return null;
    } catch (error) {
      console.error('Failed to retrieve encrypted item:', error);
      return null;
    }
  }

  /**
   * Clear all app data
   */
  static async clearAllData(): Promise<void> {
    try {
      await AsyncStorage.clear();
      await Keychain.resetGenericPassword();
    } catch (error) {
      console.error('Failed to clear app data:', error);
    }
  }

  /**
   * Token storage methods
   */
  static async storeTokens(accessToken: string, refreshToken: string): Promise<void> {
    await Promise.all([
      this.setSecureItem('access_token', accessToken),
      this.setSecureItem('refresh_token', refreshToken),
    ]);
  }

  static async getTokens(): Promise<{ accessToken: string | null; refreshToken: string | null }> {
    const [accessToken, refreshToken] = await Promise.all([
      this.getSecureItem('access_token'),
      this.getSecureItem('refresh_token'),
    ]);
    
    return { accessToken, refreshToken };
  }

  static async clearTokens(): Promise<void> {
    await Promise.all([
      this.removeSecureItem('access_token'),
      this.removeSecureItem('refresh_token'),
    ]);
  }
}
```

## 🔑 Authentication Service

### AuthService.ts
```typescript
import { CryptoUtils } from '../utils/CryptoUtils';
import { DeviceUtils } from '../utils/DeviceUtils';
import { StorageService } from './StorageService';
import { ApiClient } from './ApiClient';

export interface AuthResponse {
  success: boolean;
  user?: any;
  accessToken?: string;
  refreshToken?: string;
  error?: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export class AuthService {
  private static apiClient = new ApiClient();
  
  /**
   * OAuth 2.0 with PKCE authentication flow
   */
  static async authenticateWithPKCE(credentials: LoginCredentials): Promise<AuthResponse> {
    try {
      // Step 1: Device security check
      const securityInfo = await DeviceUtils.getDeviceSecurityInfo();
      if (securityInfo.securityLevel === 'CRITICAL') {
        return {
          success: false,
          error: 'Device security requirements not met'
        };
      }

      // Step 2: Generate PKCE parameters
      const codeVerifier = CryptoUtils.generateCodeVerifier();
      const codeChallenge = CryptoUtils.generateCodeChallenge(codeVerifier);
      const deviceFingerprint = await CryptoUtils.generateDeviceFingerprint();

      // Step 3: Initial authentication request
      const authResponse = await this.apiClient.post('/auth/mobile/login', {
        email: credentials.email,
        password: credentials.password,
        code_challenge: codeChallenge,
        code_challenge_method: 'S256',
        device_fingerprint: deviceFingerprint,
        device_info: await DeviceUtils.getDeviceInfo(),
      });

      if (!authResponse.success) {
        return {
          success: false,
          error: authResponse.error || 'Authentication failed'
        };
      }

      // Step 4: Exchange authorization code for tokens
      const tokenResponse = await this.apiClient.post('/auth/mobile/token', {
        authorization_code: authResponse.data.authorization_code,
        code_verifier: codeVerifier,
        device_fingerprint: deviceFingerprint,
      });

      if (!tokenResponse.success) {
        return {
          success: false,
          error: 'Token exchange failed'
        };
      }

      // Step 5: Store tokens securely
      await StorageService.storeTokens(
        tokenResponse.data.access_token,
        tokenResponse.data.refresh_token
      );

      // Step 6: Store device binding
      await StorageService.setEncryptedItem('device_fingerprint', deviceFingerprint);
      await StorageService.setEncryptedItem('user_profile', JSON.stringify(tokenResponse.data.user));

      return {
        success: true,
        user: tokenResponse.data.user,
        accessToken: tokenResponse.data.access_token,
        refreshToken: tokenResponse.data.refresh_token,
      };

    } catch (error) {
      console.error('Authentication error:', error);
      return {
        success: false,
        error: 'Network error or server unavailable'
      };
    }
  }

  /**
   * Refresh access token
   */
  static async refreshToken(): Promise<AuthResponse> {
    try {
      const { refreshToken } = await StorageService.getTokens();
      const deviceFingerprint = await StorageService.getEncryptedItem('device_fingerprint');

      if (!refreshToken || !deviceFingerprint) {
        return {
          success: false,
          error: 'No refresh token or device binding found'
        };
      }

      const response = await this.apiClient.post('/auth/mobile/refresh', {
        refresh_token: refreshToken,
        device_fingerprint: deviceFingerprint,
      });

      if (!response.success) {
        await this.logout(); // Clear invalid tokens
        return {
          success: false,
          error: 'Token refresh failed'
        };
      }

      // Store new tokens
      await StorageService.storeTokens(
        response.data.access_token,
        response.data.refresh_token
      );

      return {
        success: true,
        accessToken: response.data.access_token,
        refreshToken: response.data.refresh_token,
      };

    } catch (error) {
      console.error('Token refresh error:', error);
      await this.logout();
      return {
        success: false,
        error: 'Token refresh failed'
      };
    }
  }

  /**
   * Check if user is authenticated
   */
  static async isAuthenticated(): Promise<boolean> {
    try {
      const { accessToken } = await StorageService.getTokens();
      
      if (!accessToken) {
        return false;
      }

      // Verify token with server
      const response = await this.apiClient.get('/auth/mobile/verify');
      return response.success;

    } catch (error) {
      return false;
    }
  }

  /**
   * Get current user profile
   */
  static async getCurrentUser(): Promise<any | null> {
    try {
      const userProfile = await StorageService.getEncryptedItem('user_profile');
      return userProfile ? JSON.parse(userProfile) : null;
    } catch (error) {
      return null;
    }
  }

  /**
   * Logout user
   */
  static async logout(): Promise<void> {
    try {
      // Notify server of logout
      await this.apiClient.post('/auth/mobile/logout');
    } catch (error) {
      console.error('Logout API call failed:', error);
    } finally {
      // Clear all stored data
      await StorageService.clearAllData();
    }
  }

  /**
   * Biometric authentication (optional)
   */
  static async authenticateWithBiometrics(): Promise<boolean> {
    try {
      const ReactNativeBiometrics = require('react-native-biometrics');
      const rnBiometrics = new ReactNativeBiometrics();

      const { available } = await rnBiometrics.isSensorAvailable();
      
      if (!available) {
        return false;
      }

      const { success } = await rnBiometrics.simplePrompt({
        promptMessage: 'Authenticate to access your account'
      });

      return success;
    } catch (error) {
      console.error('Biometric authentication error:', error);
      return false;
    }
  }
}
```

## 🌐 API Client

### ApiClient.ts
```typescript
import { CryptoUtils } from '../utils/CryptoUtils';
import { StorageService } from './StorageService';
import { CONFIG } from '../constants/config';

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  error?: string;
  status?: number;
}

export class ApiClient {
  private baseURL: string;
  private hmacSecret: string;

  constructor() {
    this.baseURL = CONFIG.API_BASE_URL;
    this.hmacSecret = CONFIG.HMAC_SECRET;
  }

  /**
   * Make authenticated API request
   */
  async request<T>(
    method: string,
    endpoint: string,
    data?: any,
    requiresAuth: boolean = true
  ): Promise<ApiResponse<T>> {
    try {
      const url = `${this.baseURL}${endpoint}`;
      const timestamp = Date.now().toString();
      const body = data ? JSON.stringify(data) : '';

      // Prepare headers
      const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'X-Timestamp': timestamp,
        'X-Request-ID': this.generateRequestId(),
      };

      // Add authentication if required
      if (requiresAuth) {
        const { accessToken } = await StorageService.getTokens();
        if (accessToken) {
          headers['Authorization'] = `Bearer ${accessToken}`;
        }
      }

      // Add device information
      const deviceFingerprint = await StorageService.getEncryptedItem('device_fingerprint');
      if (deviceFingerprint) {
        headers['X-Device-Fingerprint'] = deviceFingerprint;
      }

      // Generate HMAC signature
      const signature = CryptoUtils.generateHMACSignature(
        method,
        endpoint,
        body,
        timestamp,
        this.hmacSecret
      );
      headers['X-Signature'] = signature;

      // Make request
      const response = await fetch(url, {
        method: method.toUpperCase(),
        headers,
        body: body || undefined,
        timeout: CONFIG.REQUEST_TIMEOUT,
      });

      const responseData = await response.json();

      if (!response.ok) {
        // Handle token expiration
        if (response.status === 401 && requiresAuth) {
          const refreshResult = await this.handleTokenRefresh();
          if (refreshResult) {
            // Retry original request with new token
            return this.request(method, endpoint, data, requiresAuth);
          }
        }

        return {
          success: false,
          error: responseData.message || 'Request failed',
          status: response.status,
        };
      }

      return {
        success: true,
        data: responseData,
        status: response.status,
      };

    } catch (error) {
      console.error('API request error:', error);
      return {
        success: false,
        error: 'Network error or server unavailable',
      };
    }
  }

  /**
   * Handle token refresh
   */
  private async handleTokenRefresh(): Promise<boolean> {
    try {
      const AuthService = require('./AuthService').AuthService;
      const refreshResult = await AuthService.refreshToken();
      return refreshResult.success;
    } catch (error) {
      return false;
    }
  }

  /**
   * Generate unique request ID
   */
  private generateRequestId(): string {
    return `rn_${Date.now()}_${Math.random().toString(36).substring(2, 15)}`;
  }

  // Convenience methods
  async get<T>(endpoint: string, requiresAuth: boolean = true): Promise<ApiResponse<T>> {
    return this.request('GET', endpoint, undefined, requiresAuth);
  }

  async post<T>(endpoint: string, data?: any, requiresAuth: boolean = true): Promise<ApiResponse<T>> {
    return this.request('POST', endpoint, data, requiresAuth);
  }

  async put<T>(endpoint: string, data?: any, requiresAuth: boolean = true): Promise<ApiResponse<T>> {
    return this.request('PUT', endpoint, data, requiresAuth);
  }

  async delete<T>(endpoint: string, requiresAuth: boolean = true): Promise<ApiResponse<T>> {
    return this.request('DELETE', endpoint, undefined, requiresAuth);
  }
}
```

## ⚙️ Configuration

### config.ts
```typescript
export const CONFIG = {
  // API Configuration
  API_BASE_URL: __DEV__ 
    ? 'http://localhost:8001/api/mobile' 
    : 'https://tenant1.yourdomain.com/api/mobile',
  
  SSO_BASE_URL: __DEV__
    ? 'http://localhost:8000'
    : 'https://sso.yourdomain.com',

  // Security Configuration
  HMAC_SECRET: 'your_64_character_hmac_secret_here',
  REQUEST_TIMEOUT: 30000, // 30 seconds

  // OAuth Configuration
  CLIENT_ID: 'tenant1_mobile_app',
  REDIRECT_URI: 'tenant1app://auth/callback',
  SCOPE: 'read write',

  // Security Settings
  MAX_LOGIN_ATTEMPTS: 5,
  TOKEN_REFRESH_THRESHOLD: 300000, // 5 minutes before expiry
  
  // Biometric Settings
  BIOMETRIC_PROMPT_TITLE: 'Authenticate',
  BIOMETRIC_PROMPT_SUBTITLE: 'Use your biometric to access your account',
  BIOMETRIC_PROMPT_DESCRIPTION: 'Place your finger on the sensor or look at the camera',
};
```

## 🎣 React Hooks

### useAuth.ts
```typescript
import { useState, useEffect, useContext, createContext, ReactNode } from 'react';
import { AuthService, AuthResponse } from '../services/AuthService';

interface AuthContextType {
  isAuthenticated: boolean;
  user: any | null;
  login: (email: string, password: string) => Promise<AuthResponse>;
  logout: () => Promise<void>;
  refreshToken: () => Promise<AuthResponse>;
  isLoading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [user, setUser] = useState<any | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    checkAuthStatus();
  }, []);

  const checkAuthStatus = async () => {
    try {
      const authenticated = await AuthService.isAuthenticated();
      setIsAuthenticated(authenticated);
      
      if (authenticated) {
        const userProfile = await AuthService.getCurrentUser();
        setUser(userProfile);
      }
    } catch (error) {
      console.error('Auth status check failed:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (email: string, password: string): Promise<AuthResponse> => {
    setIsLoading(true);
    try {
      const response = await AuthService.authenticateWithPKCE({ email, password });
      
      if (response.success) {
        setIsAuthenticated(true);
        setUser(response.user);
      }
      
      return response;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async (): Promise<void> => {
    setIsLoading(true);
    try {
      await AuthService.logout();
      setIsAuthenticated(false);
      setUser(null);
    } finally {
      setIsLoading(false);
    }
  };

  const refreshToken = async (): Promise<AuthResponse> => {
    return AuthService.refreshToken();
  };

  return (
    <AuthContext.Provider value={{
      isAuthenticated,
      user,
      login,
      logout,
      refreshToken,
      isLoading,
    }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
```

## 📱 Usage Examples

### Login Screen Component
```typescript
import React, { useState } from 'react';
import { View, TextInput, TouchableOpacity, Text, Alert } from 'react-native';
import { useAuth } from '../hooks/useAuth';

export const LoginScreen: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const { login } = useAuth();

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Error', 'Please enter both email and password');
      return;
    }

    setIsLoading(true);
    try {
      const response = await login(email, password);
      
      if (!response.success) {
        Alert.alert('Login Failed', response.error || 'Authentication failed');
      }
      // Navigation will be handled by auth state change
    } catch (error) {
      Alert.alert('Error', 'An unexpected error occurred');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <View style={{ padding: 20 }}>
      <TextInput
        placeholder="Email"
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
        autoCapitalize="none"
        style={{
          borderWidth: 1,
          borderColor: '#ccc',
          padding: 10,
          marginBottom: 10,
          borderRadius: 5,
        }}
      />
      
      <TextInput
        placeholder="Password"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        style={{
          borderWidth: 1,
          borderColor: '#ccc',
          padding: 10,
          marginBottom: 20,
          borderRadius: 5,
        }}
      />
      
      <TouchableOpacity
        onPress={handleLogin}
        disabled={isLoading}
        style={{
          backgroundColor: isLoading ? '#ccc' : '#007AFF',
          padding: 15,
          borderRadius: 5,
          alignItems: 'center',
        }}
      >
        <Text style={{ color: 'white', fontWeight: 'bold' }}>
          {isLoading ? 'Logging in...' : 'Login'}
        </Text>
      </TouchableOpacity>
    </View>
  );
};
```

### API Usage Example
```typescript
import React, { useState, useEffect } from 'react';
import { View, Text, FlatList } from 'react-native';
import { ApiClient } from '../services/ApiClient';

export const UserDataScreen: React.FC = () => {
  const [userData, setUserData] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const apiClient = new ApiClient();

  useEffect(() => {
    fetchUserData();
  }, []);

  const fetchUserData = async () => {
    try {
      const response = await apiClient.get('/user/profile');
      
      if (response.success) {
        setUserData(response.data);
      }
    } catch (error) {
      console.error('Failed to fetch user data:', error);
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <Text>Loading...</Text>
      </View>
    );
  }

  return (
    <View style={{ flex: 1, padding: 20 }}>
      <Text style={{ fontSize: 18, fontWeight: 'bold', marginBottom: 20 }}>
        User Profile
      </Text>
      {/* Render user data */}
    </View>
  );
};
```

## 🧪 Testing

### Authentication Service Test
```typescript
import { AuthService } from '../services/AuthService';
import { StorageService } from '../services/StorageService';

// Mock dependencies
jest.mock('../services/StorageService');
jest.mock('../utils/DeviceUtils');

describe('AuthService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should authenticate with valid credentials', async () => {
    // Mock successful API response
    const mockResponse = {
      success: true,
      data: {
        authorization_code: 'test_code',
        user: { id: 1, email: 'test@example.com' }
      }
    };

    // Test authentication
    const result = await AuthService.authenticateWithPKCE({
      email: 'test@example.com',
      password: 'password'
    });

    expect(result.success).toBe(true);
    expect(result.user).toBeDefined();
  });

  test('should handle authentication failure', async () => {
    // Mock failed API response
    const result = await AuthService.authenticateWithPKCE({
      email: 'invalid@example.com',
      password: 'wrongpassword'
    });

    expect(result.success).toBe(false);
    expect(result.error).toBeDefined();
  });

  test('should refresh token successfully', async () => {
    // Mock stored refresh token
    (StorageService.getTokens as jest.Mock).mockResolvedValue({
      refreshToken: 'valid_refresh_token'
    });

    const result = await AuthService.refreshToken();
    
    expect(result.success).toBe(true);
  });
});
```

### Component Testing
```typescript
import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import { LoginScreen } from '../components/LoginScreen';
import { AuthProvider } from '../hooks/useAuth';

const MockAuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <AuthProvider>{children}</AuthProvider>
);

describe('LoginScreen', () => {
  test('should render login form', () => {
    const { getByPlaceholderText, getByText } = render(
      <MockAuthProvider>
        <LoginScreen />
      </MockAuthProvider>
    );

    expect(getByPlaceholderText('Email')).toBeTruthy();
    expect(getByPlaceholderText('Password')).toBeTruthy();
    expect(getByText('Login')).toBeTruthy();
  });

  test('should call login function on form submission', async () => {
    const { getByPlaceholderText, getByText } = render(
      <MockAuthProvider>
        <LoginScreen />
      </MockAuthProvider>
    );

    const emailInput = getByPlaceholderText('Email');
    const passwordInput = getByPlaceholderText('Password');
    const loginButton = getByText('Login');

    fireEvent.changeText(emailInput, 'test@example.com');
    fireEvent.changeText(passwordInput, 'password');
    fireEvent.press(loginButton);

    await waitFor(() => {
      // Verify login process initiated
      expect(getByText('Logging in...')).toBeTruthy();
    });
  });
});
```

## 🔒 Security Best Practices

### Key Security Features Implemented

1. **PKCE (Proof Key for Code Exchange)**
   - Prevents authorization code interception attacks
   - Cryptographically secure code generation

2. **HMAC Request Signing**
   - All API requests signed with HMAC-SHA256
   - Prevents request tampering and replay attacks

3. **Device Binding**
   - Unique device fingerprinting
   - Prevents token theft across devices

4. **Secure Storage**
   - iOS Keychain and Android Keystore integration
   - Biometric authentication support

5. **Jailbreak/Root Detection**
   - Comprehensive device security checks
   - Configurable security level enforcement

6. **Certificate Pinning Alternative**
   - Multiple security layers compensate for no cert pinning
   - HMAC signing provides request integrity

## 📋 Deployment Checklist

- [ ] Configure production API endpoints
- [ ] Set up proper HMAC secrets
- [ ] Test on physical devices
- [ ] Verify biometric authentication
- [ ] Test jailbreak/root detection
- [ ] Validate secure storage
- [ ] Performance testing
- [ ] Security penetration testing

## 🔗 Integration with Backend

Ensure your Laravel backend implements the corresponding mobile API endpoints:

- `POST /api/mobile/auth/login` - PKCE authentication
- `POST /api/mobile/auth/token` - Token exchange
- `POST /api/mobile/auth/refresh` - Token refresh
- `GET /api/mobile/auth/verify` - Token verification
- `POST /api/mobile/auth/logout` - Logout

See the [implementation guide](../implementation-guide.md) for backend setup details.

---

This React Native implementation provides enterprise-grade security while maintaining excellent developer experience and user interface responsiveness.