/**
 * SkyLearn Billing Pro - React Native API Service
 * Handles all API communications with the WordPress backend
 */

import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';

class SkyLearnAPI {
  constructor(baseUrl) {
    this.baseUrl = baseUrl.replace(/\/$/, ''); // Remove trailing slash
    this.token = null;
    this.refreshToken = null;
  }

  /**
   * Initialize the API service with stored tokens
   */
  async initialize() {
    try {
      this.token = await AsyncStorage.getItem('slbp_access_token');
      this.refreshToken = await AsyncStorage.getItem('slbp_refresh_token');
    } catch (error) {
      console.error('Failed to initialize API service:', error);
    }
  }

  /**
   * Set authentication tokens
   */
  async setTokens(accessToken, refreshToken) {
    this.token = accessToken;
    this.refreshToken = refreshToken;
    
    try {
      await AsyncStorage.setItem('slbp_access_token', accessToken);
      if (refreshToken) {
        await AsyncStorage.setItem('slbp_refresh_token', refreshToken);
      }
    } catch (error) {
      console.error('Failed to store tokens:', error);
    }
  }

  /**
   * Clear stored tokens
   */
  async clearTokens() {
    this.token = null;
    this.refreshToken = null;
    
    try {
      await AsyncStorage.removeItem('slbp_access_token');
      await AsyncStorage.removeItem('slbp_refresh_token');
    } catch (error) {
      console.error('Failed to clear tokens:', error);
    }
  }

  /**
   * Check if user is authenticated
   */
  isAuthenticated() {
    return !!this.token;
  }

  /**
   * Make authenticated API request
   */
  async request(endpoint, options = {}) {
    const url = `${this.baseUrl}/wp-json/skylearn-billing-pro/v1/${endpoint}`;
    
    const defaultHeaders = {
      'Content-Type': 'application/json',
      'User-Agent': `SkyLearn-Mobile/${Platform.OS}`,
    };

    if (this.token) {
      defaultHeaders['Authorization'] = `Bearer ${this.token}`;
    }

    const requestOptions = {
      ...options,
      headers: {
        ...defaultHeaders,
        ...options.headers,
      },
    };

    try {
      const response = await fetch(url, requestOptions);
      
      // Handle token expiration
      if (response.status === 401 && this.refreshToken) {
        const refreshed = await this.refreshTokens();
        if (refreshed) {
          // Retry the request with new token
          requestOptions.headers['Authorization'] = `Bearer ${this.token}`;
          return await fetch(url, requestOptions);
        }
      }

      return response;
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  /**
   * Refresh access tokens
   */
  async refreshTokens() {
    if (!this.refreshToken) {
      return false;
    }

    try {
      const response = await fetch(`${this.baseUrl}/wp-json/skylearn-billing-pro/v1/auth/refresh`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          refresh_token: this.refreshToken,
        }),
      });

      if (response.ok) {
        const data = await response.json();
        await this.setTokens(data.access_token, data.refresh_token);
        return true;
      }
    } catch (error) {
      console.error('Token refresh failed:', error);
    }

    // Refresh failed, clear tokens
    await this.clearTokens();
    return false;
  }

  /**
   * Get user dashboard data
   */
  async getDashboardData() {
    const response = await this.request('dashboard');
    if (!response.ok) {
      throw new Error('Failed to fetch dashboard data');
    }
    return await response.json();
  }

  /**
   * Get user subscriptions
   */
  async getSubscriptions(page = 1, limit = 10) {
    const response = await this.request(`subscriptions?page=${page}&limit=${limit}`);
    if (!response.ok) {
      throw new Error('Failed to fetch subscriptions');
    }
    return await response.json();
  }

  /**
   * Get subscription details
   */
  async getSubscription(subscriptionId) {
    const response = await this.request(`subscriptions/${subscriptionId}`);
    if (!response.ok) {
      throw new Error('Failed to fetch subscription details');
    }
    return await response.json();
  }

  /**
   * Cancel subscription
   */
  async cancelSubscription(subscriptionId, reason = '') {
    const response = await this.request(`subscriptions/${subscriptionId}/cancel`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    });
    if (!response.ok) {
      throw new Error('Failed to cancel subscription');
    }
    return await response.json();
  }

  /**
   * Get transaction history
   */
  async getTransactions(filters = {}) {
    const queryParams = new URLSearchParams(filters).toString();
    const endpoint = `transactions${queryParams ? `?${queryParams}` : ''}`;
    
    const response = await this.request(endpoint);
    if (!response.ok) {
      throw new Error('Failed to fetch transactions');
    }
    return await response.json();
  }

  /**
   * Get transaction details
   */
  async getTransaction(transactionId) {
    const response = await this.request(`transactions/${transactionId}`);
    if (!response.ok) {
      throw new Error('Failed to fetch transaction details');
    }
    return await response.json();
  }

  /**
   * Download invoice
   */
  async downloadInvoice(transactionId) {
    const response = await this.request(`transactions/${transactionId}/invoice`);
    if (!response.ok) {
      throw new Error('Failed to download invoice');
    }
    return await response.blob();
  }

  /**
   * Get available courses
   */
  async getCourses(page = 1, limit = 10) {
    const response = await this.request(`courses?page=${page}&limit=${limit}`);
    if (!response.ok) {
      throw new Error('Failed to fetch courses');
    }
    return await response.json();
  }

  /**
   * Enroll in course
   */
  async enrollInCourse(courseId, paymentMethodId) {
    const response = await this.request('courses/enroll', {
      method: 'POST',
      body: JSON.stringify({
        course_id: courseId,
        payment_method_id: paymentMethodId,
      }),
    });
    if (!response.ok) {
      throw new Error('Failed to enroll in course');
    }
    return await response.json();
  }

  /**
   * Get user preferences
   */
  async getPreferences() {
    const response = await this.request('preferences');
    if (!response.ok) {
      throw new Error('Failed to fetch preferences');
    }
    return await response.json();
  }

  /**
   * Update user preferences
   */
  async updatePreferences(preferences) {
    const response = await this.request('preferences', {
      method: 'PUT',
      body: JSON.stringify(preferences),
    });
    if (!response.ok) {
      throw new Error('Failed to update preferences');
    }
    return await response.json();
  }

  /**
   * Get payment methods
   */
  async getPaymentMethods() {
    const response = await this.request('payment-methods');
    if (!response.ok) {
      throw new Error('Failed to fetch payment methods');
    }
    return await response.json();
  }

  /**
   * Add payment method
   */
  async addPaymentMethod(paymentMethodData) {
    const response = await this.request('payment-methods', {
      method: 'POST',
      body: JSON.stringify(paymentMethodData),
    });
    if (!response.ok) {
      throw new Error('Failed to add payment method');
    }
    return await response.json();
  }

  /**
   * Remove payment method
   */
  async removePaymentMethod(paymentMethodId) {
    const response = await this.request(`payment-methods/${paymentMethodId}`, {
      method: 'DELETE',
    });
    if (!response.ok) {
      throw new Error('Failed to remove payment method');
    }
    return await response.json();
  }

  /**
   * Search content
   */
  async search(query, filters = {}) {
    const params = new URLSearchParams({
      q: query,
      ...filters,
    }).toString();
    
    const response = await this.request(`search?${params}`);
    if (!response.ok) {
      throw new Error('Search failed');
    }
    return await response.json();
  }

  /**
   * Report analytics event
   */
  async reportEvent(eventType, eventData = {}) {
    try {
      await this.request('analytics/events', {
        method: 'POST',
        body: JSON.stringify({
          event_type: eventType,
          event_data: eventData,
          timestamp: new Date().toISOString(),
          platform: Platform.OS,
        }),
      });
    } catch (error) {
      // Analytics errors shouldn't break the app
      console.warn('Failed to report analytics event:', error);
    }
  }
}

// Create singleton instance
const apiService = new SkyLearnAPI(__DEV__ 
  ? 'http://localhost' 
  : 'https://yoursite.com'
);

export default apiService;