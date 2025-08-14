<?php

namespace ContentGemWP\Subscription;

/**
 * Subscription checker for WordPress plugin
 * Verifies if user has Business or Pro subscription to use the plugin
 */
class SubscriptionChecker {
    
    private $api_client;
    private $cache_duration = 300; // 5 minutes cache
    
    public function __construct() {
        $this->api_client = new \ContentGemWP\API\Client();
    }
    
    /**
     * Check if current user can use the plugin
     * 
     * @return array {
     *     @type bool   $can_use     Whether user can use the plugin
     *     @type string $plan_name   Current plan name (if available)
     *     @type string $error       Error message (if any)
     *     @type int    $status_code HTTP status code
     * }
     */
    public function check_user_access() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return [
                'can_use' => false,
                'plan_name' => '',
                'error' => __('You must be logged in to use ContentGem AI plugin.', 'contentgem-wp'),
                'status_code' => 401
            ];
        }
        
        // Check if API key is configured
        $api_key = get_option('contentgem_wp_api_key');
        if (empty($api_key)) {
            return [
                'can_use' => false,
                'plan_name' => '',
                'error' => __('ContentGem API key is not configured. Please contact your administrator.', 'contentgem-wp'),
                'status_code' => 400
            ];
        }
        
        // Check cache first
        $cache_key = 'contentgem_wp_subscription_' . get_current_user_id();
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        // Check subscription via API
        $result = $this->check_subscription_via_api();
        
        // Cache the result
        set_transient($cache_key, $result, $this->cache_duration);
        
        return $result;
    }
    
    /**
     * Check subscription via ContentGem API
     * 
     * @return array
     */
    private function check_subscription_via_api() {
        try {
            // Get subscription status from API
            $response = $this->api_client->get_subscription_status();
            
            if (!$response['success']) {
                return [
                    'can_use' => false,
                    'plan_name' => '',
                    'error' => __('Failed to verify subscription status.', 'contentgem-wp'),
                    'status_code' => $response['status_code'] ?? 500
                ];
            }
            
            $subscription = $response['data']['subscription'] ?? [];
            $plan_slug = $subscription['planSlug'] ?? '';
            $plan_name = $subscription['planName'] ?? '';
            $status = $subscription['status'] ?? '';
            
            // Check if subscription is active
            if ($status !== 'active') {
                return [
                    'can_use' => false,
                    'plan_name' => $plan_name,
                    'error' => __('Your ContentGem subscription is not active.', 'contentgem-wp'),
                    'status_code' => 403
                ];
            }
            
            // Check if plan allows plugin usage
            $allowed_plans = ['business', 'pro', 'enterprise'];
            $can_use = in_array(strtolower($plan_slug), $allowed_plans);
            
            if (!$can_use) {
                return [
                    'can_use' => false,
                    'plan_name' => $plan_name,
                    'error' => sprintf(
                        __('ContentGem AI plugin requires Business, Pro, or Enterprise subscription. Your current plan: %s', 'contentgem-wp'),
                        $plan_name
                    ),
                    'status_code' => 403
                ];
            }
            
            return [
                'can_use' => true,
                'plan_name' => $plan_name,
                'error' => '',
                'status_code' => 200
            ];
            
        } catch (\Exception $e) {
            return [
                'can_use' => false,
                'plan_name' => '',
                'error' => __('Error checking subscription status: ' . $e->getMessage(), 'contentgem-wp'),
                'status_code' => 500
            ];
        }
    }
    
    /**
     * Clear subscription cache for current user
     */
    public function clear_cache() {
        $cache_key = 'contentgem_wp_subscription_' . get_current_user_id();
        delete_transient($cache_key);
    }
    
    /**
     * Check if request is coming from WordPress plugin
     * 
     * @return bool
     */
    public function is_wordpress_request() {
        // Check for WordPress specific headers
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Check if request is from WordPress admin or frontend
        if (strpos($user_agent, 'WordPress') !== false) {
            return true;
        }
        
        // Check if referer is from WordPress site
        if (!empty($referer) && strpos($referer, get_site_url()) === 0) {
            return true;
        }
        
        // Check if request is AJAX from WordPress
        if (wp_doing_ajax()) {
            return true;
        }
        
        // Check if request is from WordPress REST API
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get subscription info for display
     * 
     * @return array
     */
    public function get_subscription_info() {
        $result = $this->check_user_access();
        
        if (!$result['can_use']) {
            return [
                'status' => 'error',
                'message' => $result['error'],
                'plan_name' => $result['plan_name'],
                'upgrade_url' => $this->get_upgrade_url()
            ];
        }
        
        return [
            'status' => 'success',
            'plan_name' => $result['plan_name'],
            'message' => sprintf(
                __('You have access to ContentGem AI plugin with %s plan.', 'contentgem-wp'),
                $result['plan_name']
            )
        ];
    }
    
    /**
     * Get upgrade URL for users without proper subscription
     * 
     * @return string
     */
    private function get_upgrade_url() {
        $base_url = get_option('contentgem_wp_base_url', 'https://your-domain.com/api/v1');
        $site_url = str_replace('/api/v1', '', $base_url);
        
        return $site_url . '/pricing';
    }
    
    /**
     * Check if user has specific capability
     * 
     * @param string $capability
     * @return bool
     */
    public function user_can($capability) {
        // First check WordPress capabilities
        if (!current_user_can($capability)) {
            return false;
        }
        
        // Then check ContentGem subscription
        $access = $this->check_user_access();
        return $access['can_use'];
    }
    
    /**
     * Require subscription access or die
     * 
     * @param string $capability WordPress capability to check
     * @return void
     */
    public function require_access($capability = 'edit_posts') {
        if (!$this->user_can($capability)) {
            $access = $this->check_user_access();
            
            if (wp_doing_ajax()) {
                wp_die(
                    json_encode([
                        'success' => false,
                        'error' => 'SUBSCRIPTION_REQUIRED',
                        'message' => $access['error'],
                        'upgrade_url' => $this->get_upgrade_url()
                    ]),
                    'ContentGem Subscription Required',
                    ['response' => 403]
                );
            } else {
                wp_die(
                    '<h1>' . __('ContentGem Subscription Required', 'contentgem-wp') . '</h1>' .
                    '<p>' . esc_html($access['error']) . '</p>' .
                    '<p><a href="' . esc_url($this->get_upgrade_url()) . '" class="button button-primary">' . 
                    __('Upgrade Subscription', 'contentgem-wp') . '</a></p>',
                    __('ContentGem Subscription Required', 'contentgem-wp'),
                    ['response' => 403]
                );
            }
        }
    }
}
