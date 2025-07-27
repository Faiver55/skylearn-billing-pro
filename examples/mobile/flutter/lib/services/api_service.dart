/**
 * SkyLearn Billing Pro - Flutter API Service
 * Handles all API communications with the WordPress backend
 */

import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/api_response.dart';
import '../models/dashboard_data.dart';
import '../models/subscription.dart';
import '../models/transaction.dart';
import '../models/course.dart';

class SkyLearnApiService {
  static const String _baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://yoursite.com',
  );
  
  static const String _apiPath = '/wp-json/skylearn-billing-pro/v1';
  
  String? _accessToken;
  String? _refreshToken;
  
  static final SkyLearnApiService _instance = SkyLearnApiService._internal();
  factory SkyLearnApiService() => _instance;
  SkyLearnApiService._internal();

  /// Initialize the service with stored tokens
  Future<void> initialize() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _accessToken = prefs.getString('slbp_access_token');
      _refreshToken = prefs.getString('slbp_refresh_token');
    } catch (e) {
      print('Failed to initialize API service: $e');
    }
  }

  /// Set authentication tokens
  Future<void> setTokens(String accessToken, [String? refreshToken]) async {
    _accessToken = accessToken;
    _refreshToken = refreshToken;
    
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('slbp_access_token', accessToken);
      if (refreshToken != null) {
        await prefs.setString('slbp_refresh_token', refreshToken);
      }
    } catch (e) {
      print('Failed to store tokens: $e');
    }
  }

  /// Clear stored tokens
  Future<void> clearTokens() async {
    _accessToken = null;
    _refreshToken = null;
    
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('slbp_access_token');
      await prefs.remove('slbp_refresh_token');
    } catch (e) {
      print('Failed to clear tokens: $e');
    }
  }

  /// Check if user is authenticated
  bool get isAuthenticated => _accessToken != null;

  /// Get default headers for API requests
  Map<String, String> get _defaultHeaders => {
    'Content-Type': 'application/json',
    'User-Agent': 'SkyLearn-Flutter/${Platform.operatingSystem}',
    if (_accessToken != null) 'Authorization': 'Bearer $_accessToken',
  };

  /// Make authenticated API request
  Future<ApiResponse<T>> _request<T>(
    String endpoint, {
    String method = 'GET',
    Map<String, dynamic>? body,
    Map<String, String>? headers,
    T Function(Map<String, dynamic>)? parser,
  }) async {
    final url = Uri.parse('$_baseUrl$_apiPath/$endpoint');
    
    final requestHeaders = {
      ..._defaultHeaders,
      ...?headers,
    };

    http.Response response;

    try {
      switch (method.toUpperCase()) {
        case 'GET':
          response = await http.get(url, headers: requestHeaders);
          break;
        case 'POST':
          response = await http.post(
            url,
            headers: requestHeaders,
            body: body != null ? jsonEncode(body) : null,
          );
          break;
        case 'PUT':
          response = await http.put(
            url,
            headers: requestHeaders,
            body: body != null ? jsonEncode(body) : null,
          );
          break;
        case 'DELETE':
          response = await http.delete(url, headers: requestHeaders);
          break;
        default:
          throw Exception('Unsupported HTTP method: $method');
      }

      // Handle token expiration
      if (response.statusCode == 401 && _refreshToken != null) {
        final refreshed = await _refreshTokens();
        if (refreshed) {
          // Retry the request with new token
          requestHeaders['Authorization'] = 'Bearer $_accessToken';
          return _request<T>(
            endpoint,
            method: method,
            body: body,
            headers: headers,
            parser: parser,
          );
        }
      }

      return _parseResponse<T>(response, parser);
    } catch (e) {
      return ApiResponse<T>.error('Network error: $e');
    }
  }

  /// Parse HTTP response
  ApiResponse<T> _parseResponse<T>(
    http.Response response,
    T Function(Map<String, dynamic>)? parser,
  ) {
    try {
      final data = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode >= 200 && response.statusCode < 300) {
        if (parser != null) {
          return ApiResponse<T>.success(parser(data));
        } else {
          return ApiResponse<T>.success(data as T);
        }
      } else {
        final error = data['message'] ?? 'Unknown error';
        return ApiResponse<T>.error(error);
      }
    } catch (e) {
      return ApiResponse<T>.error('Failed to parse response: $e');
    }
  }

  /// Refresh access tokens
  Future<bool> _refreshTokens() async {
    if (_refreshToken == null) return false;

    try {
      final response = await http.post(
        Uri.parse('$_baseUrl$_apiPath/auth/refresh'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'refresh_token': _refreshToken}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body) as Map<String, dynamic>;
        await setTokens(
          data['access_token'] as String,
          data['refresh_token'] as String?,
        );
        return true;
      }
    } catch (e) {
      print('Token refresh failed: $e');
    }

    // Refresh failed, clear tokens
    await clearTokens();
    return false;
  }

  /// Get dashboard data
  Future<ApiResponse<DashboardData>> getDashboardData() async {
    return _request<DashboardData>(
      'dashboard',
      parser: (data) => DashboardData.fromJson(data),
    );
  }

  /// Get user subscriptions
  Future<ApiResponse<List<Subscription>>> getSubscriptions({
    int page = 1,
    int limit = 10,
  }) async {
    return _request<List<Subscription>>(
      'subscriptions?page=$page&limit=$limit',
      parser: (data) {
        final subscriptions = data['subscriptions'] as List;
        return subscriptions
            .map((s) => Subscription.fromJson(s as Map<String, dynamic>))
            .toList();
      },
    );
  }

  /// Get subscription details
  Future<ApiResponse<Subscription>> getSubscription(String subscriptionId) async {
    return _request<Subscription>(
      'subscriptions/$subscriptionId',
      parser: (data) => Subscription.fromJson(data),
    );
  }

  /// Cancel subscription
  Future<ApiResponse<Map<String, dynamic>>> cancelSubscription(
    String subscriptionId, {
    String? reason,
  }) async {
    return _request<Map<String, dynamic>>(
      'subscriptions/$subscriptionId/cancel',
      method: 'POST',
      body: {'reason': reason ?? ''},
    );
  }

  /// Get transaction history
  Future<ApiResponse<List<Transaction>>> getTransactions({
    int page = 1,
    int limit = 10,
    String? status,
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    final params = <String, String>{
      'page': page.toString(),
      'limit': limit.toString(),
      if (status != null) 'status': status,
      if (startDate != null) 'start_date': startDate.toIso8601String(),
      if (endDate != null) 'end_date': endDate.toIso8601String(),
    };

    final query = params.entries.map((e) => '${e.key}=${e.value}').join('&');
    
    return _request<List<Transaction>>(
      'transactions?$query',
      parser: (data) {
        final transactions = data['transactions'] as List;
        return transactions
            .map((t) => Transaction.fromJson(t as Map<String, dynamic>))
            .toList();
      },
    );
  }

  /// Get transaction details
  Future<ApiResponse<Transaction>> getTransaction(String transactionId) async {
    return _request<Transaction>(
      'transactions/$transactionId',
      parser: (data) => Transaction.fromJson(data),
    );
  }

  /// Get available courses
  Future<ApiResponse<List<Course>>> getCourses({
    int page = 1,
    int limit = 10,
    String? category,
    String? search,
  }) async {
    final params = <String, String>{
      'page': page.toString(),
      'limit': limit.toString(),
      if (category != null) 'category': category,
      if (search != null) 'search': search,
    };

    final query = params.entries.map((e) => '${e.key}=${e.value}').join('&');
    
    return _request<List<Course>>(
      'courses?$query',
      parser: (data) {
        final courses = data['courses'] as List;
        return courses
            .map((c) => Course.fromJson(c as Map<String, dynamic>))
            .toList();
      },
    );
  }

  /// Enroll in course
  Future<ApiResponse<Map<String, dynamic>>> enrollInCourse({
    required String courseId,
    required String paymentMethodId,
  }) async {
    return _request<Map<String, dynamic>>(
      'courses/enroll',
      method: 'POST',
      body: {
        'course_id': courseId,
        'payment_method_id': paymentMethodId,
      },
    );
  }

  /// Get user preferences
  Future<ApiResponse<Map<String, dynamic>>> getPreferences() async {
    return _request<Map<String, dynamic>>('preferences');
  }

  /// Update user preferences
  Future<ApiResponse<Map<String, dynamic>>> updatePreferences(
    Map<String, dynamic> preferences,
  ) async {
    return _request<Map<String, dynamic>>(
      'preferences',
      method: 'PUT',
      body: preferences,
    );
  }

  /// Get payment methods
  Future<ApiResponse<List<Map<String, dynamic>>>> getPaymentMethods() async {
    return _request<List<Map<String, dynamic>>>(
      'payment-methods',
      parser: (data) {
        final methods = data['payment_methods'] as List;
        return methods.cast<Map<String, dynamic>>();
      },
    );
  }

  /// Add payment method
  Future<ApiResponse<Map<String, dynamic>>> addPaymentMethod(
    Map<String, dynamic> paymentMethodData,
  ) async {
    return _request<Map<String, dynamic>>(
      'payment-methods',
      method: 'POST',
      body: paymentMethodData,
    );
  }

  /// Remove payment method
  Future<ApiResponse<Map<String, dynamic>>> removePaymentMethod(
    String paymentMethodId,
  ) async {
    return _request<Map<String, dynamic>>(
      'payment-methods/$paymentMethodId',
      method: 'DELETE',
    );
  }

  /// Search content
  Future<ApiResponse<Map<String, dynamic>>> search({
    required String query,
    Map<String, String>? filters,
  }) async {
    final params = <String, String>{
      'q': query,
      ...?filters,
    };

    final queryString = params.entries.map((e) => '${e.key}=${e.value}').join('&');
    
    return _request<Map<String, dynamic>>('search?$queryString');
  }

  /// Report analytics event
  Future<void> reportEvent(String eventType, [Map<String, dynamic>? eventData]) async {
    try {
      await _request<Map<String, dynamic>>(
        'analytics/events',
        method: 'POST',
        body: {
          'event_type': eventType,
          'event_data': eventData ?? {},
          'timestamp': DateTime.now().toIso8601String(),
          'platform': Platform.operatingSystem,
        },
      );
    } catch (e) {
      // Analytics errors shouldn't break the app
      print('Failed to report analytics event: $e');
    }
  }
}

/// Convenience getter for the API service
SkyLearnApiService get apiService => SkyLearnApiService();