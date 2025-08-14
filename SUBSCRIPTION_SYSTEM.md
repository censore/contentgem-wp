# ContentGem WordPress Plugin - Subscription System

## Overview

The ContentGem WordPress plugin includes a comprehensive subscription verification system that ensures only users with Business, Pro, or Enterprise subscriptions can access the plugin functionality.

## How It Works

### 1. Request Detection

The system automatically detects when requests are coming from the WordPress plugin by checking:

- **User Agent**: WordPress-specific headers
- **Referer**: Requests from WordPress site
- **AJAX**: WordPress AJAX requests
- **REST API**: WordPress REST API calls

### 2. Subscription Verification

When a WordPress request is detected, the system:

1. **Checks user authentication** - User must be logged in
2. **Validates API key** - API key must be configured
3. **Queries ContentGem API** - Gets current subscription status
4. **Verifies plan level** - Ensures Business/Pro/Enterprise plan
5. **Caches results** - Stores results for 5 minutes

### 3. Access Control

Based on subscription status:

- ✅ **Valid subscription** - Full plugin access
- ❌ **Invalid subscription** - Access denied with upgrade prompt
- ⚠️ **API errors** - Graceful error handling

## Implementation Details

### Core Classes

#### `SubscriptionChecker`

Main class for subscription verification:

```php
$checker = new ContentGemWP\Subscription\SubscriptionChecker();

// Check user access
$result = $checker->check_user_access();

// Require access (throws exception if denied)
$checker->require_access('edit_posts');

// Get subscription info for display
$info = $checker->get_subscription_info();
```

#### `API\Client`

Enhanced API client with subscription checking:

```php
$client = new ContentGemWP\API\Client();

// All requests automatically check subscription
$result = $client->generate_content('Test prompt');
```

### Integration Points

#### AJAX Handlers

All AJAX endpoints include subscription verification:

```php
public function ajax_generate_content() {
    check_ajax_referer('contentgem_wp_nonce', 'nonce');

    // Check subscription access
    $this->subscription_checker->require_access('edit_posts');

    // Continue with generation...
}
```

#### Admin Pages

Subscription status page for monitoring:

- **Location**: ContentGem AI > Subscription Status
- **Features**:
  - Current subscription status
  - Plan information
  - API configuration status
  - Troubleshooting help
  - Cache management

## Supported Plans

| Plan           | Plugin Access | Features                      |
| -------------- | ------------- | ----------------------------- |
| **Basic**      | ❌ No         | Core ContentGem features only |
| **Business**   | ✅ Full       | All plugin features           |
| **Pro**        | ✅ Full       | All plugin features           |
| **Enterprise** | ✅ Full       | All plugin features           |

## Error Handling

### Common Error Scenarios

1. **User Not Logged In**

   ```
   Error: You must be logged in to use ContentGem AI plugin.
   Status: 401 Unauthorized
   ```

2. **API Key Not Configured**

   ```
   Error: ContentGem API key is not configured. Please contact your administrator.
   Status: 400 Bad Request
   ```

3. **Insufficient Plan**

   ```
   Error: ContentGem AI plugin requires Business, Pro, or Enterprise subscription.
   Your current plan: Basic
   Status: 403 Forbidden
   ```

4. **Inactive Subscription**

   ```
   Error: Your ContentGem subscription is not active.
   Status: 403 Forbidden
   ```

5. **API Error**
   ```
   Error: Failed to verify subscription status.
   Status: 500 Internal Server Error
   ```

### Error Responses

#### AJAX Responses

```json
{
  "success": false,
  "error": "SUBSCRIPTION_REQUIRED",
  "message": "ContentGem AI plugin requires Business, Pro, or Enterprise subscription.",
  "upgrade_url": "https://your-domain.com/pricing"
}
```

#### Page Responses

- **Admin pages**: WordPress error page with upgrade link
- **AJAX requests**: JSON error response
- **Frontend**: Graceful degradation

## Caching System

### Cache Strategy

- **Duration**: 5 minutes
- **Key**: `contentgem_wp_subscription_{user_id}`
- **Storage**: WordPress transients
- **Clear triggers**: Manual clear, subscription changes

### Cache Management

```php
// Clear cache for current user
$checker->clear_cache();

// Clear cache via admin interface
// ContentGem AI > Subscription Status > Clear Cache
```

## Security Features

### Request Validation

- ✅ **Nonce verification** for all AJAX requests
- ✅ **Capability checking** for WordPress permissions
- ✅ **API key validation** before subscription check
- ✅ **User authentication** verification

### Data Protection

- ✅ **Secure API communication** via HTTPS
- ✅ **API key masking** in admin interface
- ✅ **Error message sanitization**
- ✅ **Input validation** and sanitization

## Performance Optimization

### Caching Benefits

- **Reduced API calls** - Only check subscription every 5 minutes
- **Faster response times** - Cached results for immediate access
- **Reduced server load** - Fewer external API requests

### Optimization Features

- **Lazy loading** - Only check when needed
- **Conditional checking** - Skip for non-WordPress requests
- **Error caching** - Cache error states to prevent repeated failures

## Troubleshooting

### Common Issues

1. **"Subscription Required" Error**

   - Check your ContentGem subscription plan
   - Verify subscription is active
   - Clear subscription cache
   - Contact support if issue persists

2. **"API Key Not Configured" Error**

   - Configure API key in ContentGem AI > Settings
   - Verify API key is correct
   - Check API key permissions

3. **"Failed to Verify" Error**
   - Check network connectivity
   - Verify API endpoint is accessible
   - Check server firewall settings
   - Contact support for API issues

### Debug Information

Enable WordPress debug mode to see detailed error information:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## API Endpoints

### Subscription Status

```
GET /api/v1/subscription/status
Headers: X-API-Key: {api_key}
```

### Response Format

```json
{
  "success": true,
  "data": {
    "subscription": {
      "planName": "Business",
      "planSlug": "business",
      "status": "active",
      "currentPeriodStart": "2024-01-01T00:00:00Z",
      "currentPeriodEnd": "2024-02-01T00:00:00Z"
    }
  }
}
```

## Development

### Adding Subscription Check to New Features

```php
// In your AJAX handler
public function ajax_new_feature() {
    check_ajax_referer('contentgem_wp_nonce', 'nonce');

    // Add subscription check
    $this->subscription_checker->require_access('edit_posts');

    // Your feature code here...
}
```

### Customizing Allowed Plans

```php
// In SubscriptionChecker.php
private $allowed_plans = ['business', 'pro', 'enterprise'];

// Add new plan
private $allowed_plans = ['business', 'pro', 'enterprise', 'premium'];
```

### Testing Subscription System

```bash
# Run subscription tests
./vendor/bin/phpunit tests/test-subscription.php

# Test specific scenarios
./vendor/bin/phpunit --filter test_subscription_check_with_valid_business_plan
```

## Support

For subscription-related issues:

- **Email**: support@contentgem.com
- **Documentation**: https://docs.contentgem.com/api
- **Status Page**: ContentGem AI > Subscription Status

---

**Version**: 1.1.0
**Last Updated**: January 2024
