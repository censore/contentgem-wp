<?php
/**
 * Test file for ContentGem WordPress Plugin AJAX functions
 * 
 * This file contains tests for the new AJAX endpoints added to support
 * bulk generation and company management features.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentGem_WP_AJAX_Test extends WP_Ajax_UnitTestCase
{
    private $plugin;
    private $api_key = 'test_api_key_123';

    public function setUp(): void
    {
        parent::setUp();
        
        // Set up test API key
        update_option('contentgem_wp_api_key', $this->api_key);
        
        // Initialize plugin
        $this->plugin = new ContentGemWP\Plugin();
    }

    public function test_bulk_generate_ajax()
    {
        // Mock the API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/publications/bulk-generate') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'bulk_session_id' => 'bulk_sess_123',
                            'total_prompts' => 2,
                            'status' => 'processing'
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);

        // Set up AJAX request
        $_POST['action'] = 'contentgem_bulk_generate';
        $_POST['nonce'] = wp_create_nonce('contentgem_wp_nonce');
        $_POST['prompts'] = [
            'Write about AI in business',
            'Explain machine learning basics'
        ];
        $_POST['company_info'] = [
            'name' => 'Test Company'
        ];
        $_POST['common_settings'] = [
            'length' => 'medium'
        ];

        // Make AJAX request
        try {
            $this->_handleAjax('contentgem_bulk_generate');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        // Get response
        $response = json_decode($this->_last_response, true);

        // Assertions
        $this->assertTrue($response['success']);
        $this->assertEquals('bulk_sess_123', $response['data']['bulk_session_id']);
        $this->assertEquals(2, $response['data']['total_prompts']);
    }

    public function test_check_bulk_status_ajax()
    {
        // Mock the API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/publications/bulk-status') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'bulk_session_id' => 'bulk_sess_123',
                            'status' => 'completed',
                            'completed_prompts' => 2,
                            'failed_prompts' => 0
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);

        // Set up AJAX request
        $_POST['action'] = 'contentgem_check_bulk_status';
        $_POST['nonce'] = wp_create_nonce('contentgem_wp_nonce');
        $_POST['bulk_session_id'] = 'bulk_sess_123';

        // Make AJAX request
        try {
            $this->_handleAjax('contentgem_check_bulk_status');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        // Get response
        $response = json_decode($this->_last_response, true);

        // Assertions
        $this->assertTrue($response['success']);
        $this->assertEquals('completed', $response['data']['status']);
        $this->assertEquals(2, $response['data']['completed_prompts']);
    }

    public function test_get_company_info_ajax()
    {
        // Mock the API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/company') !== false && $args['method'] === 'GET') {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'company' => [
                                'name' => 'Test Company',
                                'description' => 'Test description',
                                'website' => 'https://testcompany.com'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);

        // Set up AJAX request
        $_POST['action'] = 'contentgem_get_company_info';
        $_POST['nonce'] = wp_create_nonce('contentgem_wp_nonce');

        // Make AJAX request
        try {
            $this->_handleAjax('contentgem_get_company_info');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        // Get response
        $response = json_decode($this->_last_response, true);

        // Assertions
        $this->assertTrue($response['success']);
        $this->assertEquals('Test Company', $response['data']['company']['name']);
        $this->assertEquals('https://testcompany.com', $response['data']['company']['website']);
    }

    public function test_update_company_info_ajax()
    {
        // Mock the API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/company') !== false && $args['method'] === 'PUT') {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'company' => [
                                'name' => 'Updated Company',
                                'description' => 'Updated description',
                                'website' => 'https://updatedcompany.com'
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);

        // Set up AJAX request
        $_POST['action'] = 'contentgem_update_company_info';
        $_POST['nonce'] = wp_create_nonce('contentgem_wp_nonce');
        $_POST['company_data'] = [
            'name' => 'Updated Company',
            'description' => 'Updated description',
            'website' => 'https://updatedcompany.com'
        ];

        // Make AJAX request
        try {
            $this->_handleAjax('contentgem_update_company_info');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        // Get response
        $response = json_decode($this->_last_response, true);

        // Assertions
        $this->assertTrue($response['success']);
        $this->assertEquals('Updated Company', $response['data']['company']['name']);
    }

    public function test_parse_company_website_ajax()
    {
        // Mock the API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/company/parse') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'parsing_session_id' => 'parse_sess_123',
                            'status' => 'processing',
                            'message' => 'Parsing started'
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);

        // Set up AJAX request
        $_POST['action'] = 'contentgem_parse_company_website';
        $_POST['nonce'] = wp_create_nonce('contentgem_wp_nonce');
        $_POST['website_url'] = 'https://example.com';

        // Make AJAX request
        try {
            $this->_handleAjax('contentgem_parse_company_website');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        // Get response
        $response = json_decode($this->_last_response, true);

        // Assertions
        $this->assertTrue($response['success']);
        $this->assertEquals('parse_sess_123', $response['data']['parsing_session_id']);
        $this->assertEquals('processing', $response['data']['status']);
    }

    public function test_ajax_nonce_validation()
    {
        // Test without nonce
        $_POST['action'] = 'contentgem_bulk_generate';
        $_POST['prompts'] = ['Test prompt'];

        // Should fail without nonce
        $this->expectException('WPAjaxDieStopException');
        $this->_handleAjax('contentgem_bulk_generate');
    }

    public function test_ajax_permissions()
    {
        // Test with non-admin user
        wp_set_current_user(0); // No user logged in

        $_POST['action'] = 'contentgem_bulk_generate';
        $_POST['nonce'] = wp_create_nonce('contentgem_wp_nonce');
        $_POST['prompts'] = ['Test prompt'];

        // Should fail without proper permissions
        $this->expectException('WPAjaxDieStopException');
        $this->_handleAjax('contentgem_bulk_generate');
    }

    public function tearDown(): void
    {
        // Clean up
        delete_option('contentgem_wp_api_key');
        parent::tearDown();
    }
}
