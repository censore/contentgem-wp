<?php

namespace ContentGemWP\Admin;

/**
 * Subscription status admin page
 */
class SubscriptionStatus {
    
    private $subscription_checker;
    
    public function __construct() {
        $this->subscription_checker = new \ContentGemWP\Subscription\SubscriptionChecker();
    }
    
    /**
     * Render subscription status page
     */
    public function render_page() {
        // Check if user can manage options
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'contentgem-wp'));
        }
        
        // Handle cache clear
        if (isset($_POST['clear_cache']) && wp_verify_nonce($_POST['_wpnonce'], 'contentgem_clear_cache')) {
            $this->subscription_checker->clear_cache();
            echo '<div class="notice notice-success"><p>' . __('Cache cleared successfully.', 'contentgem-wp') . '</p></div>';
        }
        
        // Get subscription info
        $subscription_info = $this->subscription_checker->get_subscription_info();
        
        ?>
        <div class="wrap">
            <h1><?php _e('ContentGem Subscription Status', 'contentgem-wp'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Current Subscription Status', 'contentgem-wp'); ?></h2>
                
                <?php if ($subscription_info['status'] === 'success'): ?>
                    <div class="notice notice-success">
                        <p><strong><?php _e('✅ Active Subscription', 'contentgem-wp'); ?></strong></p>
                        <p><?php echo esc_html($subscription_info['message']); ?></p>
                    </div>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Plan', 'contentgem-wp'); ?></th>
                            <td><strong><?php echo esc_html($subscription_info['plan_name']); ?></strong></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Plugin Access', 'contentgem-wp'); ?></th>
                            <td><span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e('Full Access', 'contentgem-wp'); ?></td>
                        </tr>
                    </table>
                    
                <?php else: ?>
                    <div class="notice notice-error">
                        <p><strong><?php _e('❌ Subscription Required', 'contentgem-wp'); ?></strong></p>
                        <p><?php echo esc_html($subscription_info['message']); ?></p>
                        
                        <?php if (!empty($subscription_info['plan_name'])): ?>
                            <p><strong><?php _e('Current Plan:', 'contentgem-wp'); ?></strong> <?php echo esc_html($subscription_info['plan_name']); ?></p>
                        <?php endif; ?>
                        
                        <p>
                            <a href="<?php echo esc_url($subscription_info['upgrade_url']); ?>" class="button button-primary" target="_blank">
                                <?php _e('Upgrade Subscription', 'contentgem-wp'); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2><?php _e('API Configuration', 'contentgem-wp'); ?></h2>
                
                <?php
                $api_key = get_option('contentgem_wp_api_key');
                $base_url = get_option('contentgem_wp_base_url');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('API Key', 'contentgem-wp'); ?></th>
                        <td>
                            <?php if (!empty($api_key)): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span> 
                                <?php _e('Configured', 'contentgem-wp'); ?>
                                <br>
                                <small><?php echo esc_html(substr($api_key, 0, 8) . '...'); ?></small>
                            <?php else: ?>
                                <span class="dashicons dashicons-no-alt" style="color: red;"></span> 
                                <?php _e('Not configured', 'contentgem-wp'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Base URL', 'contentgem-wp'); ?></th>
                        <td>
                            <?php if (!empty($base_url)): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span> 
                                <?php echo esc_html($base_url); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-no-alt" style="color: red;"></span> 
                                <?php _e('Not configured', 'contentgem-wp'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=contentgem-wp-settings'); ?>" class="button">
                        <?php _e('Configure API Settings', 'contentgem-wp'); ?>
                    </a>
                </p>
            </div>
            
            <div class="card">
                <h2><?php _e('Troubleshooting', 'contentgem-wp'); ?></h2>
                
                <h3><?php _e('Common Issues', 'contentgem-wp'); ?></h3>
                
                <ul>
                    <li><strong><?php _e('Subscription not active:', 'contentgem-wp'); ?></strong> 
                        <?php _e('Make sure your ContentGem subscription is active and you have a Business, Pro, or Enterprise plan.', 'contentgem-wp'); ?>
                    </li>
                    <li><strong><?php _e('API key not working:', 'contentgem-wp'); ?></strong> 
                        <?php _e('Verify your API key is correct and has the necessary permissions.', 'contentgem-wp'); ?>
                    </li>
                    <li><strong><?php _e('Network issues:', 'contentgem-wp'); ?></strong> 
                        <?php _e('Check your server can reach the ContentGem API endpoint.', 'contentgem-wp'); ?>
                    </li>
                </ul>
                
                <h3><?php _e('Cache Management', 'contentgem-wp'); ?></h3>
                
                <p><?php _e('Subscription status is cached for 5 minutes to improve performance. If you recently upgraded your subscription, you may need to clear the cache.', 'contentgem-wp'); ?></p>
                
                <form method="post">
                    <?php wp_nonce_field('contentgem_clear_cache'); ?>
                    <input type="submit" name="clear_cache" class="button" value="<?php _e('Clear Cache', 'contentgem-wp'); ?>">
                </form>
            </div>
            
            <div class="card">
                <h2><?php _e('Support', 'contentgem-wp'); ?></h2>
                
                <p><?php _e('If you continue to have issues with your subscription or plugin access, please contact our support team:', 'contentgem-wp'); ?></p>
                
                <ul>
                    <li><strong><?php _e('Email:', 'contentgem-wp'); ?></strong> support@contentgem.com</li>
                    <li><strong><?php _e('Documentation:', 'contentgem-wp'); ?></strong> 
                        <a href="https://docs.contentgem.com" target="_blank">https://docs.contentgem.com</a>
                    </li>
                    <li><strong><?php _e('Pricing:', 'contentgem-wp'); ?></strong> 
                        <a href="<?php echo esc_url($this->get_pricing_url()); ?>" target="_blank"><?php _e('View Plans', 'contentgem-wp'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .card h3 {
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .card ul {
            margin-left: 20px;
        }
        .card li {
            margin-bottom: 8px;
        }
        </style>
        <?php
    }
    
    /**
     * Get pricing URL
     * 
     * @return string
     */
    private function get_pricing_url() {
        $base_url = get_option('contentgem_wp_base_url', 'https://your-domain.com/api/v1');
        $site_url = str_replace('/api/v1', '', $base_url);
        
        return $site_url . '/pricing';
    }
}
