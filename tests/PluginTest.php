<?php

/**
 * Plugin Test Class
 * 
 * Tests for the ContentGem WordPress Plugin
 */

class PluginTest extends WP_UnitTestCase
{
    private $plugin;

    public function setUp(): void
    {
        parent::setUp();
        $this->plugin = new ContentGemWP\Plugin();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization()
    {
        $this->assertInstanceOf('ContentGemWP\Plugin', $this->plugin);
    }

    /**
     * Test admin menu creation
     */
    public function test_admin_menu_creation()
    {
        // Mock admin user
        $this->set_current_user('administrator');

        // Trigger admin menu creation
        do_action('admin_menu');

        // Check if menu exists
        global $menu;
        $menu_slugs = wp_list_pluck($menu, 2);
        
        $this->assertContains('contentgem-wp', $menu_slugs);
    }

    /**
     * Test plugin activation
     */
    public function test_plugin_activation()
    {
        // Simulate plugin activation
        do_action('activate_contentgem-wp/contentgem-wp.php');

        // Check if options were created
        $this->assertNotFalse(get_option('contentgem_wp_api_key'));
        $this->assertNotFalse(get_option('contentgem_wp_base_url'));
        $this->assertNotFalse(get_option('contentgem_wp_enable_auto_generation'));
        $this->assertNotFalse(get_option('contentgem_wp_default_category'));
    }

    /**
     * Test plugin deactivation
     */
    public function test_plugin_deactivation()
    {
        // Simulate plugin deactivation
        do_action('deactivate_contentgem-wp/contentgem-wp.php');

        // Options should still exist (deactivation doesn't remove them)
        $this->assertNotFalse(get_option('contentgem_wp_api_key'));
    }

    /**
     * Test AJAX generate content
     */
    public function test_ajax_generate_content()
    {
        // Mock nonce
        $_POST['nonce'] = wp_create_nonce('contentgem_wp_nonce');
        $_POST['prompt'] = 'Write about AI in business';
        $_POST['company_info'] = [
            'name' => 'Test Company',
            'description' => 'Test description'
        ];

        // Mock current user
        $this->set_current_user('editor');

        // Mock API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
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

        // Trigger AJAX action
        try {
            do_action('wp_ajax_contentgem_generate_content');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        // Check response
        $response = json_decode($this->_last_response, true);
        $this->assertTrue($response['success']);
        $this->assertEquals('pub_123', $response['data']['publicationId']);
    }

    /**
     * Test AJAX check status
     */
    public function test_ajax_check_status()
    {
        // Mock nonce
        $_POST['nonce'] = wp_create_nonce('contentgem_wp_nonce');
        $_POST['session_id'] = 'sess_456';

        // Mock current user
        $this->set_current_user('editor');

        // Mock API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, '/generation-status/') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'publicationId' => 'pub_123',
                            'sessionId' => 'sess_456',
                            'status' => 'completed',
                            'content' => 'Generated content here...'
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);

        // Trigger AJAX action
        try {
            do_action('wp_ajax_contentgem_check_status');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        // Check response
        $response = json_decode($this->_last_response, true);
        $this->assertTrue($response['success']);
        $this->assertEquals('completed', $response['data']['status']);
    }

    /**
     * Test AJAX save post
     */
    public function test_ajax_save_post()
    {
        // Mock nonce
        $_POST['nonce'] = wp_create_nonce('contentgem_wp_nonce');
        $_POST['title'] = 'Test Post Title';
        $_POST['content'] = 'Test post content';
        $_POST['category_id'] = 1;

        // Mock current user
        $this->set_current_user('editor');

        // Trigger AJAX action
        try {
            do_action('wp_ajax_contentgem_save_post');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        // Check response
        $response = json_decode($this->_last_response, true);
        $this->assertTrue($response['success']);
        $this->assertIsInt($response['data']['post_id']);
    }

    /**
     * Test meta box rendering
     */
    public function test_meta_box_rendering()
    {
        // Create a test post
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Post',
            'post_content' => 'Test content'
        ]);

        // Mock API key
        update_option('contentgem_wp_api_key', 'test_key');

        // Capture output
        ob_start();
        $this->plugin->render_generator_meta_box(get_post($post_id));
        $output = ob_get_clean();

        // Check if meta box content is rendered
        $this->assertStringContainsString('ContentGem AI Generator', $output);
        $this->assertStringContainsString('Generate Content', $output);
    }

    /**
     * Test meta box without API key
     */
    public function test_meta_box_without_api_key()
    {
        // Create a test post
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Post',
            'post_content' => 'Test content'
        ]);

        // Remove API key
        delete_option('contentgem_wp_api_key');

        // Capture output
        ob_start();
        $this->plugin->render_generator_meta_box(get_post($post_id));
        $output = ob_get_clean();

        // Check if warning message is displayed
        $this->assertStringContainsString('Please configure your API key', $output);
    }

    /**
     * Test post meta saving
     */
    public function test_post_meta_saving()
    {
        // Create a test post
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Post',
            'post_content' => 'Test content'
        ]);

        // Mock nonce
        $_POST['contentgem_wp_meta_box_nonce'] = wp_create_nonce('contentgem_wp_meta_box');
        $_POST['contentgem_wp_generated_content'] = 'Generated content from AI';

        // Trigger save action
        $this->plugin->save_post_meta($post_id);

        // Check if meta was saved
        $saved_content = get_post_meta($post_id, '_contentgem_wp_generated_content', true);
        $this->assertEquals('Generated content from AI', $saved_content);
    }

    /**
     * Test script enqueuing
     */
    public function test_script_enqueuing()
    {
        // Mock admin page
        set_current_screen('admin_page_contentgem-wp');

        // Trigger script enqueuing
        do_action('admin_enqueue_scripts', 'admin_page_contentgem-wp');

        // Check if scripts are enqueued
        $this->assertTrue(wp_script_is('contentgem-wp-admin', 'enqueued'));
        $this->assertTrue(wp_style_is('contentgem-wp-admin', 'enqueued'));
    }

    /**
     * Test frontend script enqueuing
     */
    public function test_frontend_script_enqueuing()
    {
        // Trigger frontend script enqueuing
        do_action('wp_enqueue_scripts');

        // Check if scripts are enqueued
        $this->assertTrue(wp_script_is('contentgem-wp-frontend', 'enqueued'));
        $this->assertTrue(wp_style_is('contentgem-wp-frontend', 'enqueued'));
    }

    /**
     * Test shortcode initialization
     */
    public function test_shortcode_initialization()
    {
        // Trigger shortcode initialization
        do_action('init');

        // Check if shortcodes are registered
        $this->assertTrue(shortcode_exists('contentgem_generator'));
        $this->assertTrue(shortcode_exists('contentgem_display'));
    }

    /**
     * Test API client creation
     */
    public function test_api_client_creation()
    {
        // Mock API key
        update_option('contentgem_wp_api_key', 'test_key');
        update_option('contentgem_wp_base_url', 'https://api.test.com/v1');

        // Create API client
        $api_client = new ContentGemWP\API\Client();

        $this->assertInstanceOf('ContentGemWP\API\Client', $api_client);
    }

    /**
     * Test content generator
     */
    public function test_content_generator()
    {
        // Create content generator
        $generator = new ContentGemWP\Generator\ContentGenerator();

        $this->assertInstanceOf('ContentGemWP\Generator\ContentGenerator', $generator);
    }

    /**
     * Test shortcodes
     */
    public function test_shortcodes()
    {
        // Create shortcodes instance
        $shortcodes = new ContentGemWP\Shortcodes\Shortcodes();

        $this->assertInstanceOf('ContentGemWP\Shortcodes\Shortcodes', $shortcodes);
    }

    /**
     * Helper method to set current user
     */
    private function set_current_user($role)
    {
        $user_id = $this->factory->user->create(['role' => $role]);
        wp_set_current_user($user_id);
    }
} 