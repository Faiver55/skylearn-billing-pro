# SkyLearn Billing Pro API Documentation

Complete API reference for SkyLearn Billing Pro with endpoints, authentication, and usage examples.

## Overview

The SkyLearn Billing Pro API provides programmatic access to all billing, subscription, and course management functionality. Built on WordPress REST API architecture, it offers comprehensive endpoints for payment processing, user management, and analytics.

### Base URL
```
https://yoursite.com/wp-json/slbp/v1/
```

### API Version
Current API version: **v1.0.0**

## Quick Start

### 1. Get Your API Key
```
1. Login to WordPress Admin
2. Go to SkyLearn Billing Pro → Settings → API
3. Generate a new API key
4. Copy and secure your API key
```

### 2. Make Your First Request
```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/slbp/v1/auth/verify
```

### 3. Expected Response
```json
{
  "valid": true,
  "key_id": "ak_1234567890",
  "permissions": ["read", "write", "admin"]
}
```

## Authentication

### API Key Authentication

All API requests require authentication using an API key in the Authorization header:

```http
Authorization: Bearer YOUR_API_KEY
```

#### Getting an API Key

1. **Admin Dashboard**: SkyLearn Billing Pro → Settings → API Keys
2. **Click "Generate New Key"**
3. **Set Permissions**: Choose read, write, or admin access
4. **Copy Key**: Save securely (shown only once)

#### Key Permissions

| Permission | Access Level |
|------------|--------------|
| `read` | View transactions, subscriptions, and reports |
| `write` | Create and update transactions and subscriptions |
| `admin` | Full access including user management and settings |

### WordPress Authentication

For WordPress-authenticated requests, you can also use:
- **Nonce-based authentication** for AJAX requests
- **Application passwords** for external applications
- **OAuth 2.0** (if configured)

## Rate Limiting

### Limits
- **Standard**: 1,000 requests per hour
- **Premium**: 5,000 requests per hour
- **Enterprise**: 10,000 requests per hour

### Headers
Rate limit information is included in response headers:

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 987
X-RateLimit-Reset: 1690545600
```

### Exceeding Limits
When rate limits are exceeded, the API returns:

```http
HTTP/1.1 429 Too Many Requests
```

```json
{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Try again in 3600 seconds.",
    "retry_after": 3600
  }
}
```

## Endpoints Overview

### Core Resources

| Resource | Description | Endpoints |
|----------|-------------|-----------|
| **Transactions** | Payment processing and management | `/transactions` |
| **Subscriptions** | Recurring billing management | `/subscriptions` |
| **Courses** | Course and product management | `/courses` |
| **Users** | User enrollment and access | `/users` |
| **Reports** | Analytics and revenue data | `/reports` |
| **Webhooks** | Payment gateway notifications | `/webhook` |

## Transactions API

### List Transactions

Get a paginated list of transactions with optional filtering.

```http
GET /transactions
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number (default: 1) |
| `per_page` | integer | Items per page (default: 20, max: 100) |
| `status` | string | Filter by status: `pending`, `completed`, `failed`, `cancelled`, `refunded` |
| `user_id` | integer | Filter by user ID |
| `course_id` | integer | Filter by course ID |
| `date_from` | date | Start date (YYYY-MM-DD) |
| `date_to` | date | End date (YYYY-MM-DD) |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
  "https://yoursite.com/wp-json/slbp/v1/transactions?status=completed&page=1&per_page=10"
```

#### Example Response

```json
{
  "transactions": [
    {
      "id": 12345,
      "user_id": 789,
      "course_id": 456,
      "transaction_id": "txn_1234567890",
      "amount": 99.99,
      "currency": "USD",
      "status": "completed",
      "payment_method": "credit_card",
      "gateway": "lemon_squeezy",
      "gateway_transaction_id": "ls_txn_1234567890",
      "created_at": "2024-07-28T10:30:00Z",
      "updated_at": "2024-07-28T10:30:00Z",
      "metadata": {
        "customer_ip": "192.168.1.1",
        "user_agent": "Mozilla/5.0..."
      }
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total_items": 150,
    "total_pages": 15,
    "has_next": true,
    "has_previous": false
  }
}
```

### Create Transaction

Process a new payment transaction.

```http
POST /transactions
```

#### Request Body

```json
{
  "user_id": 789,
  "course_id": 456,
  "amount": 99.99,
  "currency": "USD",
  "payment_method": "credit_card",
  "metadata": {
    "source": "api",
    "campaign": "summer_sale"
  }
}
```

#### Example Response

```json
{
  "id": 12346,
  "user_id": 789,
  "course_id": 456,
  "transaction_id": "txn_1234567891",
  "amount": 99.99,
  "currency": "USD",
  "status": "pending",
  "payment_method": "credit_card",
  "gateway": "lemon_squeezy",
  "gateway_transaction_id": null,
  "created_at": "2024-07-28T11:00:00Z",
  "updated_at": "2024-07-28T11:00:00Z",
  "payment_url": "https://checkout.lemonsqueezy.com/checkout/xyz"
}
```

### Get Transaction

Retrieve details of a specific transaction.

```http
GET /transactions/{id}
```

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
  "https://yoursite.com/wp-json/slbp/v1/transactions/12345"
```

### Refund Transaction

Process a refund for a completed transaction.

```http
POST /transactions/{id}/refund
```

#### Request Body

```json
{
  "amount": 50.00,
  "reason": "Customer requested partial refund",
  "notify_customer": true
}
```

#### Example Response

```json
{
  "refund_id": "ref_1234567890",
  "amount": 50.00,
  "status": "processed",
  "processed_at": "2024-07-28T12:00:00Z"
}
```

## Subscriptions API

### List Subscriptions

Get a list of all subscriptions with filtering options.

```http
GET /subscriptions
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status: `active`, `cancelled`, `paused`, `expired` |
| `user_id` | integer | Filter by user ID |
| `course_id` | integer | Filter by course ID |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
  "https://yoursite.com/wp-json/slbp/v1/subscriptions?status=active"
```

### Create Subscription

Create a new subscription for a user.

```http
POST /subscriptions
```

#### Request Body

```json
{
  "user_id": 789,
  "course_id": 456,
  "plan_id": "monthly_99",
  "trial_days": 7,
  "metadata": {
    "source": "website",
    "referrer": "google"
  }
}
```

### Cancel Subscription

Cancel an active subscription.

```http
POST /subscriptions/{id}/cancel
```

#### Request Body

```json
{
  "reason": "Customer request",
  "cancel_at_period_end": true,
  "notify_customer": true
}
```

## Users & Enrollments API

### Get User Enrollments

Retrieve courses a user is enrolled in.

```http
GET /users/{id}/enrollments
```

#### Example Response

```json
{
  "enrollments": [
    {
      "id": 123,
      "user_id": 789,
      "course_id": 456,
      "enrolled_at": "2024-07-28T10:30:00Z",
      "expires_at": "2025-07-28T10:30:00Z",
      "status": "active",
      "progress": {
        "completed_lessons": 5,
        "total_lessons": 20,
        "completion_percentage": 25.0
      }
    }
  ]
}
```

### Enroll User

Enroll a user in a course.

```http
POST /users/{id}/enrollments
```

#### Request Body

```json
{
  "course_id": 456,
  "access_duration": 365,
  "enrollment_date": "2024-07-28T10:30:00Z"
}
```

## Reports API

### Revenue Report

Get revenue analytics and metrics.

```http
GET /reports/revenue
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `period` | string | Time period: `today`, `week`, `month`, `year`, `custom` |
| `date_from` | date | Start date for custom period |
| `date_to` | date | End date for custom period |
| `group_by` | string | Group results by: `day`, `week`, `month` |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
  "https://yoursite.com/wp-json/slbp/v1/reports/revenue?period=month&group_by=day"
```

#### Example Response

```json
{
  "total_revenue": 15750.00,
  "transaction_count": 158,
  "average_order_value": 99.68,
  "revenue_by_period": [
    {
      "period": "2024-07-01",
      "revenue": 1250.00,
      "transactions": 12
    },
    {
      "period": "2024-07-02",
      "revenue": 980.00,
      "transactions": 10
    }
  ]
}
```

## Webhooks

### Webhook Endpoint

Receive notifications from payment gateways.

```http
POST /webhook
```

The webhook endpoint automatically processes notifications from configured payment gateways including:

- Payment completions
- Subscription renewals
- Failed payments
- Refund notifications
- Subscription cancellations

#### Webhook Security

Webhooks are secured using:
- **Signature verification** using gateway-specific secrets
- **IP whitelisting** for known gateway IPs
- **Rate limiting** to prevent abuse
- **Duplicate detection** to handle retries

## Error Handling

### Standard HTTP Status Codes

| Code | Description |
|------|-------------|
| `200` | Success |
| `201` | Created |
| `400` | Bad Request |
| `401` | Unauthorized |
| `403` | Forbidden |
| `404` | Not Found |
| `429` | Too Many Requests |
| `500` | Internal Server Error |

### Error Response Format

```json
{
  "error": {
    "code": "INVALID_REQUEST",
    "message": "The request is invalid or malformed",
    "details": {
      "field": "user_id",
      "issue": "User ID is required"
    }
  }
}
```

### Common Error Codes

| Code | Description |
|------|-------------|
| `INVALID_REQUEST` | Request data is invalid |
| `RESOURCE_NOT_FOUND` | Requested resource doesn't exist |
| `AUTHENTICATION_FAILED` | Invalid or missing API key |
| `PERMISSION_DENIED` | Insufficient permissions |
| `RATE_LIMIT_EXCEEDED` | Too many requests |
| `PAYMENT_FAILED` | Payment processing failed |
| `GATEWAY_ERROR` | Payment gateway error |

## Code Examples

### PHP Example

```php
<?php
class SkyLearnBillingAPI {
    private $api_key;
    private $base_url;
    
    public function __construct($api_key, $base_url) {
        $this->api_key = $api_key;
        $this->base_url = rtrim($base_url, '/');
    }
    
    public function getTransactions($params = []) {
        $url = $this->base_url . '/transactions';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    public function createTransaction($data) {
        $response = wp_remote_post($this->base_url . '/transactions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}

// Usage
$api = new SkyLearnBillingAPI('your_api_key', 'https://yoursite.com/wp-json/slbp/v1');

// Get recent transactions
$transactions = $api->getTransactions(['status' => 'completed', 'per_page' => 10]);

// Create a new transaction
$transaction = $api->createTransaction([
    'user_id' => 123,
    'course_id' => 456,
    'amount' => 99.99,
    'currency' => 'USD'
]);
?>
```

### JavaScript Example

```javascript
class SkyLearnBillingAPI {
    constructor(apiKey, baseUrl) {
        this.apiKey = apiKey;
        this.baseUrl = baseUrl.replace(/\/$/, '');
    }
    
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };
        
        const response = await fetch(url, config);
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(`API Error: ${error.error.message}`);
        }
        
        return response.json();
    }
    
    async getTransactions(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = `/transactions${queryString ? `?${queryString}` : ''}`;
        return this.request(endpoint);
    }
    
    async createTransaction(data) {
        return this.request('/transactions', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    async refundTransaction(id, data) {
        return this.request(`/transactions/${id}/refund`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
}

// Usage
const api = new SkyLearnBillingAPI('your_api_key', 'https://yoursite.com/wp-json/slbp/v1');

// Get transactions
api.getTransactions({ status: 'completed' })
    .then(data => console.log(data))
    .catch(error => console.error(error));

// Create transaction
api.createTransaction({
    user_id: 123,
    course_id: 456,
    amount: 99.99,
    currency: 'USD'
}).then(transaction => {
    console.log('Transaction created:', transaction);
});
```

### Python Example

```python
import requests
import json

class SkyLearnBillingAPI:
    def __init__(self, api_key, base_url):
        self.api_key = api_key
        self.base_url = base_url.rstrip('/')
        self.headers = {
            'Authorization': f'Bearer {api_key}',
            'Content-Type': 'application/json'
        }
    
    def get_transactions(self, **params):
        url = f"{self.base_url}/transactions"
        response = requests.get(url, headers=self.headers, params=params)
        response.raise_for_status()
        return response.json()
    
    def create_transaction(self, data):
        url = f"{self.base_url}/transactions"
        response = requests.post(url, headers=self.headers, json=data)
        response.raise_for_status()
        return response.json()
    
    def get_subscription(self, subscription_id):
        url = f"{self.base_url}/subscriptions/{subscription_id}"
        response = requests.get(url, headers=self.headers)
        response.raise_for_status()
        return response.json()

# Usage
api = SkyLearnBillingAPI('your_api_key', 'https://yoursite.com/wp-json/slbp/v1')

# Get completed transactions
transactions = api.get_transactions(status='completed', per_page=10)
print(f"Found {len(transactions['transactions'])} transactions")

# Create new transaction
transaction_data = {
    'user_id': 123,
    'course_id': 456,
    'amount': 99.99,
    'currency': 'USD'
}

new_transaction = api.create_transaction(transaction_data)
print(f"Created transaction: {new_transaction['id']}")
```

## Testing

### Test Environment

Use the test mode for development and testing:

```
Base URL: https://staging.yoursite.com/wp-json/slbp/v1/
Test API Key: Use test mode keys only
```

### Test Data

The API provides test endpoints with sample data:

```bash
# Get test transactions
curl -H "Authorization: Bearer TEST_API_KEY" \
  "https://staging.yoursite.com/wp-json/slbp/v1/test/transactions"
```

## SDK and Tools

### Official SDKs

- **PHP SDK**: Available in WordPress plugin
- **JavaScript SDK**: npm package (coming soon)
- **Python SDK**: PyPI package (planned)

### Third-Party Tools

- **Postman Collection**: Available for API testing
- **OpenAPI Spec**: For generating custom SDKs
- **Swagger UI**: Interactive API documentation

## Support

### API Support

- **Documentation**: This guide and OpenAPI specification
- **GitHub Issues**: Report bugs and request features
- **Email Support**: contact@skyianllc.com
- **Community**: Developer forum (coming soon)

### Response Times

- **Critical Issues**: 4-8 hours
- **Bug Reports**: 24-48 hours
- **Feature Requests**: Reviewed monthly
- **General Questions**: 2-3 business days

---

## Changelog

### v1.0.0 (2024-07-28)
- Initial API release
- Core transaction and subscription endpoints
- Authentication and rate limiting
- Webhook support
- Basic reporting endpoints

---

*For the complete OpenAPI specification, see [openapi.yaml](./openapi.yaml)*