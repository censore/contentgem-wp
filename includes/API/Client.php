<?php

namespace ContentGemWP\API;

/**
 * API Client for ContentGem WordPress plugin
 * Includes subscription checking and WordPress-specific functionality
 */
class Client {
    
    private $api_key;
    private $base_url;
    private $timeout = 30;
    private $subscription_checker;
    
    public function __construct() {
        $this->api_key = get_option('contentgem_wp_api_key', '');
        $this->base_url = get_option('contentgem_wp_base_url', 'https://your-domain.com/api/v1');
        $this->subscription_checker = new \ContentGemWP\Subscription\SubscriptionChecker();
    }
    
    /**
     * Make API request with subscription check
     * 
     * @param string $endpoint
     * @param array  $data
     * @param string $method
     * @return array
     */
    public function request($endpoint, $data = [], $method = 'GET') {
        // Check if request is from WordPress plugin
        if ($this->subscription_checker->is_wordpress_request()) {
            // Verify subscription access
            $access = $this->subscription_checker->check_user_access();
            
            if (!$access['can_use']) {
                return [
                    'success' => false,
                    'error' => 'SUBSCRIPTION_REQUIRED',
                    'message' => $access['error'],
                    'status_code' => $access['status_code']
                ];
            }
        }
        
        // Make the actual API request
        return $this->make_request($endpoint, $data, $method);
    }
    
    /**
     * Make HTTP request to ContentGem API
     * 
     * @param string $endpoint
     * @param array  $data
     * @param string $method
     * @return array
     */
    private function make_request($endpoint, $data = [], $method = 'GET') {
        $url = rtrim($this->base_url, '/') . '/' . ltrim($endpoint, '/');
        
        $args = [
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => [
                'X-API-Key' => $this->api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'ContentGem-WordPress-Plugin/' . CONTENTGEM_WP_VERSION,
                'X-Plugin-Version' => CONTENTGEM_WP_VERSION,
                'X-WordPress-Version' => get_bloginfo('version'),
                'X-Site-URL' => get_site_url()
            ]
        ];
        
        if (!empty($data)) {
            if ($method === 'GET') {
                $url = add_query_arg($data, $url);
            } else {
                $args['body'] = json_encode($data);
            }
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => 'REQUEST_FAILED',
                'message' => $response->get_error_message(),
                'status_code' => 500
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $result = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'INVALID_RESPONSE',
                'message' => 'Invalid JSON response from API',
                'status_code' => $status_code
            ];
        }
        
        $result['status_code'] = $status_code;
        
        return $result;
    }
    
    /**
     * Get subscription status
     * 
     * @return array
     */
    public function get_subscription_status() {
        return $this->request('subscription/status');
    }
    
    /**
     * Generate content
     * 
     * @param string $prompt
     * @param array  $company_info
     * @param array  $keywords
     * @return array
     */
    public function generate_content($prompt, $company_info = [], $keywords = []) {
        $data = [
            'prompt' => $prompt
        ];
        
        if (!empty($company_info)) {
            $data['company_info'] = $company_info;
        }
        
        if (!empty($keywords)) {
            $data['keywords'] = $keywords;
        }
        
        return $this->request('publications/generate', $data, 'POST');
    }
    
    /**
     * Check generation status
     * 
     * @param string $session_id
     * @return array
     */
    public function check_generation_status($session_id) {
        return $this->request("publications/generation-status/{$session_id}");
    }
    
    /**
     * Bulk generate content
     * 
     * @param array $prompts
     * @param array $company_info
     * @param array $common_settings
     * @return array
     */
    public function bulk_generate($prompts, $company_info = [], $common_settings = []) {
        $data = [
            'prompts' => $prompts
        ];
        
        if (!empty($company_info)) {
            $data['company_info'] = $company_info;
        }
        
        if (!empty($common_settings)) {
            $data['common_settings'] = $common_settings;
        }
        
        return $this->request('publications/bulk-generate', $data, 'POST');
    }
    
    /**
     * Check bulk generation status
     * 
     * @param string $bulk_session_id
     * @return array
     */
    public function check_bulk_status($bulk_session_id) {
        return $this->request('publications/bulk-status', [
            'bulk_session_id' => $bulk_session_id
        ], 'POST');
    }
    
    /**
     * Get company information
     * 
     * @return array
     */
    public function get_company_info() {
        return $this->request('company');
    }
    
    /**
     * Update company information
     * 
     * @param array $company_data
     * @return array
     */
    public function update_company_info($company_data) {
        return $this->request('company', $company_data, 'PUT');
    }
    
    /**
     * Parse company website
     * 
     * @param string $website_url
     * @return array
     */
    public function parse_company_website($website_url) {
        return $this->request('company/parse', [
            'website_url' => $website_url
        ], 'POST');
    }
    
    /**
     * Get company parsing status
     * 
     * @return array
     */
    public function get_company_parsing_status() {
        return $this->request('company/parsing-status');
    }
    
    /**
     * Upload image
     * 
     * @param string $file_path
     * @param string $publication_id
     * @return array
     */
    public function upload_image($file_path, $publication_id = '') {
        if (!file_exists($file_path)) {
            return [
                'success' => false,
                'error' => 'FILE_NOT_FOUND',
                'message' => 'File not found: ' . $file_path
            ];
        }
        
        $url = rtrim($this->base_url, '/') . '/images/upload';
        
        $args = [
            'method' => 'POST',
            'timeout' => $this->timeout,
            'headers' => [
                'X-API-Key' => $this->api_key,
                'User-Agent' => 'ContentGem-WordPress-Plugin/' . CONTENTGEM_WP_VERSION
            ],
            'body' => [
                'image' => new \CURLFile($file_path)
            ]
        ];
        
        if (!empty($publication_id)) {
            $args['body']['publication_id'] = $publication_id;
        }
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => 'UPLOAD_FAILED',
                'message' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $result = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'INVALID_RESPONSE',
                'message' => 'Invalid JSON response from API'
            ];
        }
        
        $result['status_code'] = $status_code;
        
        return $result;
    }
    
    /**
     * Get publications list
     * 
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function get_publications($page = 1, $limit = 10) {
        return $this->request('publications', [
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    /**
     * Get specific publication
     * 
     * @param string $publication_id
     * @return array
     */
    public function get_publication($publication_id) {
        return $this->request("publications/{$publication_id}");
    }
    
    /**
     * Test API connection
     * 
     * @return array
     */
    public function test_connection() {
        $result = $this->request('health');
        
        if ($result['success']) {
            // Also test subscription access
            $subscription = $this->get_subscription_status();
            
            if ($subscription['success']) {
                $result['subscription'] = $subscription['data']['subscription'] ?? [];
            }
        }
        
        return $result;
    }
    
    /**
     * Get subscription checker instance
     * 
     * @return \ContentGemWP\Subscription\SubscriptionChecker
     */
    public function get_subscription_checker() {
        return $this->subscription_checker;
    }
}
