# SkyLearn Billing Pro - Mobile Integration Examples

This directory contains example code and documentation for integrating SkyLearn Billing Pro with mobile applications.

## Available Examples

### React Native
- `/react-native/` - Complete React Native integration example
- `/react-native/components/` - Reusable components
- `/react-native/services/` - API service layer
- `/react-native/hooks/` - Custom React hooks

### Flutter
- `/flutter/` - Complete Flutter integration example
- `/flutter/lib/` - Flutter source code
- `/flutter/lib/services/` - API service layer
- `/flutter/lib/models/` - Data models
- `/flutter/lib/widgets/` - Reusable widgets

### API Documentation
- `/api/` - Mobile-specific API documentation
- `/api/endpoints.md` - Available API endpoints
- `/api/authentication.md` - Authentication flow
- `/api/webhooks.md` - Webhook integration

## Quick Start

### For React Native
```bash
cd react-native
npm install
npm start
```

### For Flutter
```bash
cd flutter
flutter pub get
flutter run
```

## Authentication

All mobile integrations use OAuth 2.0 with PKCE for secure authentication:

1. Generate code verifier and challenge
2. Redirect to WordPress OAuth endpoint
3. Exchange authorization code for access token
4. Use access token for API requests

## API Base URL

```
Production: https://yoursite.com/wp-json/skylearn-billing-pro/v1/
Development: http://localhost/wp-json/skylearn-billing-pro/v1/
```

## Security Considerations

- Always use HTTPS in production
- Store tokens securely (iOS Keychain, Android Keystore)
- Implement token refresh logic
- Validate SSL certificates
- Use certificate pinning for enhanced security

## Support

For mobile integration support, please refer to the main plugin documentation or contact support.