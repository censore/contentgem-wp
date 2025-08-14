<?php
/**
 * Plugin Name: ContentGem AI Content Generator
 * Plugin URI: https://contentgem.com
 * Description: Generate high-quality blog posts and content using AI with ContentGem API
 * Version: 1.0.0
 * Author: ContentGem Team
 * Author URI: https://contentgem.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: contentgem-wp
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CONTENTGEM_WP_VERSION', '1.0.0');
define('CONTENTGEM_WP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTGEM_WP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONTENTGEM_WP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'ContentGemWP\\';
    $base_dir = CONTENTGEM_WP_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
function contentgem_wp_init() {
    // Load text domain
    load_plugin_textdomain('contentgem-wp', false, dirname(CONTENTGEM_WP_PLUGIN_BASENAME) . '/languages');
    
    // Initialize main plugin class
    new ContentGemWP\Plugin();
}
add_action('plugins_loaded', 'contentgem_wp_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create default options
    add_option('contentgem_wp_api_key', '');
    add_option('contentgem_wp_base_url', 'https://your-domain.com/api/v1');
    add_option('contentgem_wp_enable_auto_generation', false);
    add_option('contentgem_wp_default_category', 1);
    
    // Create database tables if needed
    ContentGemWP\Database::create_tables();
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Uninstall hook
register_uninstall_hook(__FILE__, function() {
    // Remove options
    delete_option('contentgem_wp_api_key');
    delete_option('contentgem_wp_base_url');
    delete_option('contentgem_wp_enable_auto_generation');
    delete_option('contentgem_wp_default_category');
    
    // Remove database tables
    ContentGemWP\Database::drop_tables();
}); 