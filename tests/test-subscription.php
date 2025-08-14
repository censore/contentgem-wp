<?php
/**
 * Test file for ContentGem WordPress Plugin Subscription functionality
 * 
 * This file contains tests for subscription checking and access control
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentGem_WP_Subscription_Test extends WP_UnitTestCase
{
    private $subscription_checker;
    private $api_client;

    public function setUp(): void
    {
        parent::setUp();
        
        // Set up test API key
        update_option('contentgem_wp_api_key', 'test_api_key_123');
        update_option('contentgem_wp_base_url', 'https://api.test.com/v1');
        
        // Initialize subscription checker
        $this->subscription_checker = new ContentGemWP\Subscription\SubscriptionChecker();
        $this->api_client = new ContentGemWP\API\Client();
    }

    public function test_subscription_checker_initialization()
    {
        $this->assertInstanceOf(ContentGemWP\Subscription\SubscriptionChecker::class, $this->subscription_checker);
    }

    public function test_user_not_logged_in()
    {
        // Simulate user not logged in
        wp_set_current_user(0);
        
        $result = $this->subscription_checker->check_user_access();
        
        $this->assertFalse($result['can_use']);
        $this->assertEquals(401, $result['status_code']);
        $this->assertStringContainsString('logged in', $result['error']);
    }

    public function test_api_key_not_configured()
    {
        // Remove API key
        delete_option('contentgem_wp_api_key');
        
        wp_set_current_user(1);
        
        $result = $this->subscription_checker->check_user_access();
        
        $this->assertFalse($result['can_use']);
        $this->assertEquals(400, $result['status_code']);
        $this->assertStringContainsString('not configured', $result['error']);
    }

    public function test_subscription_check_with_valid_business_plan()
    {
        wp_set_current_user(1);
        
        // Mock successful API response for Business plan
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Business',
                                'planSlug' => 'business',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        $result = $this->subscription_checker->check_user_access();
        
        $this->assertTrue($result['can_use']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('Business', $result['plan_name']);
    }

    public function test_subscription_check_with_valid_pro_plan()
    {
        wp_set_current_user(1);
        
        // Mock successful API response for Pro plan
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Pro',
                                'planSlug' => 'pro',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        $result = $this->subscription_checker->check_user_access();
        
        $this->assertTrue($result['can_use']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('Pro', $result['plan_name']);
    }

    public function test_subscription_check_with_invalid_plan()
    {
        wp_set_current_user(1);
        
        // Mock API response for Basic plan (not allowed)
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Basic',
                                'planSlug' => 'basic',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        $result = $this->subscription_checker->check_user_access();
        
        $this->assertFalse($result['can_use']);
        $this->assertEquals(403, $result['status_code']);
        $this->assertStringContainsString('requires Business', $result['error']);
    }

    public function test_subscription_check_with_inactive_subscription()
    {
        wp_set_current_user(1);
        
        // Mock API response for inactive subscription
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Business',
                                'planSlug' => 'business',
                                'status' => 'cancelled'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        $result = $this->subscription_checker->check_user_access();
        
        $this->assertFalse($result['can_use']);
        $this->assertEquals(403, $result['status_code']);
        $this->assertStringContainsString('not active', $result['error']);
    }

    public function test_subscription_check_with_api_error()
    {
        wp_set_current_user(1);
        
        // Mock API error response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 500],
                    'body' => json_encode([
                        'success' => false,
                        'error' => 'API_ERROR',
                        'message' => 'Internal server error'
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        $result = $this->subscription_checker->check_user_access();
        
        $this->assertFalse($result['can_use']);
        $this->assertEquals(500, $result['status_code']);
        $this->assertStringContainsString('Failed to verify', $result['error']);
    }

    public function test_subscription_cache_functionality()
    {
        wp_set_current_user(1);
        
        // Mock successful API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Business',
                                'planSlug' => 'business',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        // First call should hit the API
        $result1 = $this->subscription_checker->check_user_access();
        $this->assertTrue($result1['can_use']);
        
        // Second call should use cache
        $result2 = $this->subscription_checker->check_user_access();
        $this->assertTrue($result2['can_use']);
        
        // Clear cache
        $this->subscription_checker->clear_cache();
        
        // Third call should hit the API again
        $result3 = $this->subscription_checker->check_user_access();
        $this->assertTrue($result3['can_use']);
    }

    public function test_wordpress_request_detection()
    {
        // Test AJAX request
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($this->subscription_checker->is_wordpress_request());
        
        // Test WordPress user agent
        $_SERVER['HTTP_USER_AGENT'] = 'WordPress/6.0';
        $this->assertTrue($this->subscription_checker->is_wordpress_request());
        
        // Test referer from WordPress site
        $_SERVER['HTTP_REFERER'] = get_site_url() . '/wp-admin/';
        $this->assertTrue($this->subscription_checker->is_wordpress_request());
        
        // Test non-WordPress request
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SERVER['HTTP_REFERER'] = 'https://external-site.com';
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $this->assertFalse($this->subscription_checker->is_wordpress_request());
    }

    public function test_subscription_info_for_display()
    {
        wp_set_current_user(1);
        
        // Mock successful API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Business',
                                'planSlug' => 'business',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        $info = $this->subscription_checker->get_subscription_info();
        
        $this->assertEquals('success', $info['status']);
        $this->assertEquals('Business', $info['plan_name']);
        $this->assertStringContainsString('have access', $info['message']);
    }

    public function test_subscription_info_for_invalid_plan()
    {
        wp_set_current_user(1);
        
        // Mock API response for Basic plan
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Basic',
                                'planSlug' => 'basic',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        $info = $this->subscription_checker->get_subscription_info();
        
        $this->assertEquals('error', $info['status']);
        $this->assertEquals('Basic', $info['plan_name']);
        $this->assertStringContainsString('requires Business', $info['message']);
        $this->assertNotEmpty($info['upgrade_url']);
    }

    public function test_user_can_method()
    {
        wp_set_current_user(1);
        
        // Mock successful API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Business',
                                'planSlug' => 'business',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        // Test with valid capability and subscription
        $this->assertTrue($this->subscription_checker->user_can('edit_posts'));
        
        // Test with invalid capability
        $this->assertFalse($this->subscription_checker->user_can('manage_network'));
    }

    public function test_require_access_method()
    {
        wp_set_current_user(1);
        
        // Mock API response for Basic plan (not allowed)
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Basic',
                                'planSlug' => 'basic',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
        
        // This should trigger wp_die
        $this->expectException('WPAjaxDieStopException');
        $this->subscription_checker->require_access('edit_posts');
    }

    public function test_api_client_with_subscription_check()
    {
        wp_set_current_user(1);
        
        // Mock successful API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Business',
                                'planSlug' => 'business',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            
            if (strpos($url, '/publications/generate') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'publicationId' => 'pub_123',
                            'sessionId' => 'sess_456',
                            'status' => 'generating'
                        ]
                    ])
                ];
            }
            
            return $preempt;
        }, 10, 3);
        
        // Test API request with subscription check
        $result = $this->api_client->generate_content('Test prompt');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('pub_123', $result['data']['publicationId']);
    }

    public function test_api_client_without_subscription()
    {
        wp_set_current_user(1);
        
        // Mock API response for Basic plan (not allowed)
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/subscription/status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'subscription' => [
                                'planName' => 'Basic',
                                'planSlug' => 'basic',
                                'status' => 'active'
                            ]
                        ]
                    ])
                ];
            }
            
            return $preempt;
        }, 10, 3);
        
        // Test API request without proper subscription
        $result = $this->api_client->generate_content('Test prompt');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('SUBSCRIPTION_REQUIRED', $result['error']);
        $this->assertEquals(403, $result['status_code']);
    }

    public function tearDown(): void
    {
        // Clean up
        delete_option('contentgem_wp_api_key');
        delete_option('contentgem_wp_base_url');
        
        // Clear any transients
        $this->subscription_checker->clear_cache();
        
        parent::tearDown();
    }
}
