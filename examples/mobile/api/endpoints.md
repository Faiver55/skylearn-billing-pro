# SkyLearn Billing Pro - Mobile API Endpoints

This document outlines the REST API endpoints available for mobile applications.

## Base URL

```
Production: https://yoursite.com/wp-json/skylearn-billing-pro/v1/
Development: http://localhost/wp-json/skylearn-billing-pro/v1/
```

## Authentication

All API requests (except public endpoints) require authentication using Bearer tokens in the Authorization header:

```
Authorization: Bearer {access_token}
```

## Endpoints

### Authentication

#### POST /auth/login
Login with username/password or OAuth

**Request Body:**
```json
{
  "username": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_token": "dGhpcyBpcyBhIHJlZnJlc2ggdG9rZW4...",
  "expires_in": 3600,
  "user": {
    "id": 123,
    "email": "user@example.com",
    "display_name": "John Doe"
  }
}
```

#### POST /auth/refresh
Refresh access token

**Request Body:**
```json
{
  "refresh_token": "dGhpcyBpcyBhIHJlZnJlc2ggdG9rZW4..."
}
```

#### POST /auth/logout
Logout and invalidate tokens

### Dashboard

#### GET /dashboard
Get dashboard overview data

**Response:**
```json
{
  "stats": {
    "total_spent": "$1,234.56",
    "active_subscriptions": 3,
    "enrolled_courses": 12,
    "completion_rate": "78%"
  },
  "recent_transactions": [...],
  "active_subscriptions": [...]
}
```

### Subscriptions

#### GET /subscriptions
Get user subscriptions

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `limit` (int): Items per page (default: 10)
- `status` (string): Filter by status (active, cancelled, expired)

**Response:**
```json
{
  "subscriptions": [
    {
      "id": "sub_123",
      "product_name": "Premium Course Access",
      "status": "active",
      "amount": "$29.99",
      "interval": "monthly",
      "next_billing_date": "2024-02-15T00:00:00Z",
      "created_at": "2024-01-15T00:00:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 25
  }
}
```

#### GET /subscriptions/{id}
Get subscription details

#### POST /subscriptions/{id}/cancel
Cancel subscription

**Request Body:**
```json
{
  "reason": "No longer needed"
}
```

#### POST /subscriptions/{id}/resume
Resume cancelled subscription

### Transactions

#### GET /transactions
Get transaction history

**Query Parameters:**
- `page` (int): Page number
- `limit` (int): Items per page
- `status` (string): Filter by status
- `start_date` (ISO 8601): Start date filter
- `end_date` (ISO 8601): End date filter

**Response:**
```json
{
  "transactions": [
    {
      "id": "txn_123",
      "amount": "$29.99",
      "status": "completed",
      "description": "Premium Course Access - Monthly",
      "date": "2024-01-15T10:30:00Z",
      "payment_method": "Visa ****1234",
      "invoice_url": "https://example.com/invoices/123.pdf"
    }
  ],
  "pagination": {...}
}
```

#### GET /transactions/{id}
Get transaction details

#### GET /transactions/{id}/invoice
Download invoice PDF

### Courses

#### GET /courses
Get available courses

**Query Parameters:**
- `page` (int): Page number
- `limit` (int): Items per page
- `category` (string): Filter by category
- `search` (string): Search query
- `enrolled` (boolean): Show only enrolled courses

**Response:**
```json
{
  "courses": [
    {
      "id": 456,
      "title": "Advanced WordPress Development",
      "description": "Learn advanced WordPress development techniques",
      "price": "$99.99",
      "category": "Development",
      "level": "Advanced",
      "duration": "8 weeks",
      "enrolled": false,
      "progress": null,
      "image_url": "https://example.com/course-image.jpg"
    }
  ],
  "categories": ["Development", "Design", "Marketing"],
  "pagination": {...}
}
```

#### GET /courses/{id}
Get course details

#### POST /courses/enroll
Enroll in course

**Request Body:**
```json
{
  "course_id": 456,
  "payment_method_id": "pm_123"
}
```

#### GET /courses/enrolled
Get enrolled courses with progress

### Payment Methods

#### GET /payment-methods
Get saved payment methods

**Response:**
```json
{
  "payment_methods": [
    {
      "id": "pm_123",
      "type": "card",
      "last4": "1234",
      "brand": "visa",
      "exp_month": 12,
      "exp_year": 2025,
      "is_default": true
    }
  ]
}
```

#### POST /payment-methods
Add payment method

#### DELETE /payment-methods/{id}
Remove payment method

#### PUT /payment-methods/{id}/default
Set as default payment method

### User Preferences

#### GET /preferences
Get user preferences

**Response:**
```json
{
  "notifications": {
    "email_receipts": true,
    "course_updates": true,
    "marketing": false
  },
  "display": {
    "theme": "auto",
    "language": "en"
  }
}
```

#### PUT /preferences
Update user preferences

### Search

#### GET /search
Search courses and content

**Query Parameters:**
- `q` (string): Search query
- `type` (string): Content type filter (courses, posts, etc.)
- `category` (string): Category filter

### Analytics

#### POST /analytics/events
Report analytics event

**Request Body:**
```json
{
  "event_type": "course_viewed",
  "event_data": {
    "course_id": 456,
    "duration": 120
  },
  "timestamp": "2024-01-15T10:30:00Z",
  "platform": "ios"
}
```

## Error Responses

All errors follow a consistent format:

```json
{
  "error": true,
  "message": "Detailed error message",
  "code": "ERROR_CODE",
  "details": {}
}
```

### Common Error Codes

- `UNAUTHORIZED` (401): Invalid or expired token
- `FORBIDDEN` (403): Insufficient permissions
- `NOT_FOUND` (404): Resource not found
- `VALIDATION_ERROR` (422): Invalid request data
- `RATE_LIMITED` (429): Too many requests
- `SERVER_ERROR` (500): Internal server error

## Rate Limiting

API requests are rate limited to prevent abuse:

- **Authenticated users**: 1000 requests per hour
- **Unauthenticated users**: 100 requests per hour

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1642259400
```

## Pagination

List endpoints support pagination with consistent parameters:

- `page`: Page number (starts at 1)
- `limit`: Items per page (default: 10, max: 100)

Pagination info is included in responses:

```json
{
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "total_items": 95,
    "has_next": true,
    "has_previous": false
  }
}
```

## Webhooks

For real-time updates, consider implementing webhooks. See [webhooks.md](webhooks.md) for details.

## SDKs and Examples

- [React Native SDK](../react-native/)
- [Flutter SDK](../flutter/)
- [iOS Swift Example](../ios/)
- [Android Kotlin Example](../android/)