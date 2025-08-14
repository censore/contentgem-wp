<?php

namespace ContentGemWP;

/**
 * Main plugin class
 */
class Plugin {
    
    private $api_client;
    private $admin;
    private $generator;
    private $shortcodes;
    private $subscription_checker;
    
    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('admin_init', [$this, 'init_admin']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('init', [$this, 'init_shortcodes']);
        
        // AJAX hooks
        add_action('wp_ajax_contentgem_generate_content', [$this, 'ajax_generate_content']);
        add_action('wp_ajax_contentgem_check_status', [$this, 'ajax_check_status']);
        add_action('wp_ajax_contentgem_save_post', [$this, 'ajax_save_post']);
        add_action('wp_ajax_contentgem_bulk_generate', [$this, 'ajax_bulk_generate']);
        add_action('wp_ajax_contentgem_check_bulk_status', [$this, 'ajax_check_bulk_status']);
        add_action('wp_ajax_contentgem_get_company_info', [$this, 'ajax_get_company_info']);
        add_action('wp_ajax_contentgem_update_company_info', [$this, 'ajax_update_company_info']);
        add_action('wp_ajax_contentgem_parse_company_website', [$this, 'ajax_parse_company_website']);
        
        // Meta box hooks
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_post_meta']);
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        $this->api_client = new API\Client();
        $this->subscription_checker = new Subscription\SubscriptionChecker();
        $this->admin = new Admin\Admin();
        $this->generator = new Generator\ContentGenerator();
        $this->shortcodes = new Shortcodes\Shortcodes();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('ContentGem AI', 'contentgem-wp'),
            __('ContentGem AI', 'contentgem-wp'),
            'manage_options',
            'contentgem-wp',
            [$this->admin, 'render_main_page'],
            'dashicons-edit',
            30
        );
        
        add_submenu_page(
            'contentgem-wp',
            __('Settings', 'contentgem-wp'),
            __('Settings', 'contentgem-wp'),
            'manage_options',
            'contentgem-wp-settings',
            [$this->admin, 'render_settings_page']
        );
        
        add_submenu_page(
            'contentgem-wp',
            __('Generate Content', 'contentgem-wp'),
            __('Generate Content', 'contentgem-wp'),
            'edit_posts',
            'contentgem-wp-generate',
            [$this->admin, 'render_generator_page']
        );
        
        add_submenu_page(
            'contentgem-wp',
            __('Subscription Status', 'contentgem-wp'),
            __('Subscription Status', 'contentgem-wp'),
            'manage_options',
            'contentgem-wp-subscription',
            [$this, 'render_subscription_status_page']
        );
    }
    
    /**
     * Initialize admin
     */
    public function init_admin() {
        $this->admin->init();
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentgem-wp') !== false) {
            wp_enqueue_script(
                'contentgem-wp-admin',
                CONTENTGEM_WP_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                CONTENTGEM_WP_VERSION,
                true
            );
            
            wp_enqueue_style(
                'contentgem-wp-admin',
                CONTENTGEM_WP_PLUGIN_URL . 'assets/css/admin.css',
                [],
                CONTENTGEM_WP_VERSION
            );
            
            wp_localize_script('contentgem-wp-admin', 'contentgem_wp', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentgem_wp_nonce'),
                'strings' => [
                    'generating' => __('Generating content...', 'contentgem-wp'),
                    'error' => __('Error occurred', 'contentgem-wp'),
                    'success' => __('Content generated successfully', 'contentgem-wp'),
                ]
            ]);
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'contentgem-wp-frontend',
            CONTENTGEM_WP_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            CONTENTGEM_WP_VERSION,
            true
        );
        
        wp_enqueue_style(
            'contentgem-wp-frontend',
            CONTENTGEM_WP_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            CONTENTGEM_WP_VERSION
        );
    }
    
    /**
     * Initialize shortcodes
     */
    public function init_shortcodes() {
        $this->shortcodes->init();
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'contentgem-wp-generator',
            __('ContentGem AI Generator', 'contentgem-wp'),
            [$this, 'render_generator_meta_box'],
            'post',
            'side',
            'high'
        );
    }
    
    /**
     * Render generator meta box
     */
    public function render_generator_meta_box($post) {
        wp_nonce_field('contentgem_wp_meta_box', 'contentgem_wp_meta_box_nonce');
        
        $api_key = get_option('contentgem_wp_api_key');
        if (empty($api_key)) {
            echo '<p>' . __('Please configure your API key in ContentGem AI settings.', 'contentgem-wp') . '</p>';
            return;
        }
        
        include CONTENTGEM_WP_PLUGIN_DIR . 'templates/meta-box-generator.php';
    }
    
    /**
     * Save post meta
     */
    public function save_post_meta($post_id) {
        if (!isset($_POST['contentgem_wp_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['contentgem_wp_meta_box_nonce'], 'contentgem_wp_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['contentgem_wp_generated_content'])) {
            update_post_meta($post_id, '_contentgem_wp_generated_content', sanitize_textarea_field($_POST['contentgem_wp_generated_content']));
        }
    }
    
    /**
     * AJAX: Generate content
     */
    public function ajax_generate_content() {
        check_ajax_referer('contentgem_wp_nonce', 'nonce');
        
        // Check subscription access
        $this->subscription_checker->require_access('edit_posts');
        
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $company_info = isset($_POST['company_info']) ? $_POST['company_info'] : [];
        
        if (empty($prompt)) {
            wp_send_json_error(__('Prompt is required', 'contentgem-wp'));
        }
        
        try {
            $result = $this->generator->generate_content($prompt, $company_info);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Check generation status
     */
    public function ajax_check_status() {
        check_ajax_referer('contentgem_wp_nonce', 'nonce');
        
        // Check subscription access
        $this->subscription_checker->require_access('edit_posts');
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        if (empty($session_id)) {
            wp_send_json_error(__('Session ID is required', 'contentgem-wp'));
        }
        
        try {
            $status = $this->generator->check_status($session_id);
            wp_send_json_success($status);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Save generated post
     */
    public function ajax_save_post() {
        check_ajax_referer('contentgem_wp_nonce', 'nonce');
        
        // Check subscription access
        $this->subscription_checker->require_access('edit_posts');
        
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $category_id = intval($_POST['category_id']);
        
        if (empty($title) || empty($content)) {
            wp_send_json_error(__('Title and content are required', 'contentgem-wp'));
        }
        
        $post_data = [
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft',
            'post_type' => 'post',
            'post_category' => [$category_id]
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }
        
        // Add generated content meta
        update_post_meta($post_id, '_contentgem_wp_generated_content', $content);
        
        wp_send_json_success([
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw')
        ]);
    }

    /**
     * AJAX: Bulk generate content
     */
    public function ajax_bulk_generate() {
        check_ajax_referer('contentgem_wp_nonce', 'nonce');
        
        // Check subscription access
        $this->subscription_checker->require_access('edit_posts');
        
        $prompts = isset($_POST['prompts']) ? $_POST['prompts'] : [];
        $company_info = isset($_POST['company_info']) ? $_POST['company_info'] : [];
        $common_settings = isset($_POST['common_settings']) ? $_POST['common_settings'] : [];
        
        if (empty($prompts) || !is_array($prompts)) {
            wp_send_json_error(__('Prompts array is required', 'contentgem-wp'));
        }
        
        try {
            $result = $this->generator->bulk_generate_content($prompts, $company_info, $common_settings);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX: Check bulk generation status
     */
    public function ajax_check_bulk_status() {
        check_ajax_referer('contentgem_wp_nonce', 'nonce');
        
        // Check subscription access
        $this->subscription_checker->require_access('edit_posts');
        
        $bulk_session_id = sanitize_text_field($_POST['bulk_session_id']);
        
        if (empty($bulk_session_id)) {
            wp_send_json_error(__('Bulk session ID is required', 'contentgem-wp'));
        }
        
        try {
            $status = $this->generator->check_bulk_status($bulk_session_id);
            wp_send_json_success($status);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX: Get company information
     */
    public function ajax_get_company_info() {
        check_ajax_referer('contentgem_wp_nonce', 'nonce');
        
        // Check subscription access
        $this->subscription_checker->require_access('edit_posts');
        
        try {
            $company_info = $this->generator->get_company_info();
            wp_send_json_success($company_info);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX: Update company information
     */
    public function ajax_update_company_info() {
        check_ajax_referer('contentgem_wp_nonce', 'nonce');
        
        // Check subscription access
        $this->subscription_checker->require_access('edit_posts');
        
        $company_data = isset($_POST['company_data']) ? $_POST['company_data'] : [];
        
        if (empty($company_data)) {
            wp_send_json_error(__('Company data is required', 'contentgem-wp'));
        }
        
        try {
            $result = $this->generator->update_company_info($company_data);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX: Parse company website
     */
    public function ajax_parse_company_website() {
        check_ajax_referer('contentgem_wp_nonce', 'nonce');
        
        // Check subscription access
        $this->subscription_checker->require_access('edit_posts');
        
        $website_url = esc_url_raw($_POST['website_url']);
        
        if (empty($website_url)) {
            wp_send_json_error(__('Website URL is required', 'contentgem-wp'));
        }
        
        try {
            $result = $this->generator->parse_company_website($website_url);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Render subscription status page
     */
    public function render_subscription_status_page() {
        $subscription_status = new Admin\SubscriptionStatus();
        $subscription_status->render_page();
    }
} 