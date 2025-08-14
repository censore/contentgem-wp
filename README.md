# ContentGem AI WordPress Plugin

Official WordPress plugin for ContentGem AI content generation.

## Features

- ✅ **AI Content Generation** - Generate high-quality blog posts using AI
- ✅ **Bulk Content Generation** - Generate multiple posts simultaneously
- ✅ **Company Management** - Manage company information and website parsing
- ✅ **Subscription Verification** - Automatic checking of Business/Pro/Enterprise plans
- ✅ **Admin Interface** - Easy-to-use admin panel for content generation
- ✅ **Meta Box Integration** - Generate content directly from post editor
- ✅ **Shortcodes** - Display generated content anywhere on your site
- ✅ **Settings Panel** - Configure API key and other options
- ✅ **Subscription Status Page** - Monitor subscription and access status
- ✅ **AJAX Support** - Non-blocking content generation
- ✅ **Category Integration** - Automatically assign categories to generated posts
- ✅ **Draft Creation** - Generated content saved as drafts for review
- ✅ **Real-time Status Tracking** - Monitor generation progress

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `contentgem-wp` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to 'ContentGem AI' > 'Settings' to configure your API key

### Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- ContentGem API key
- **ContentGem Business, Pro, or Enterprise subscription** (required for plugin access)

## Configuration

### API Key Setup

1. Go to **ContentGem AI** > **Settings**
2. Enter your ContentGem API key
3. Set the base URL (default: `https://your-domain.com/api/v1`)
4. Save settings

### Default Settings

- **Default Category**: Choose which category to assign to generated posts
- **Auto Generation**: Enable/disable automatic content generation
- **Content Length**: Set preferred content length (short, medium, long)

## Usage

### Admin Panel

1. Go to **ContentGem AI** > **Generate Content**
2. Enter your content prompt
3. Configure company information (optional)
4. Click "Generate Content"
5. Wait for generation to complete
6. Review and save the generated content

### Post Editor Integration

1. Create a new post or edit existing post
2. Look for the "ContentGem AI Generator" meta box
3. Enter your prompt and click "Generate"
4. The generated content will appear in the editor

### Bulk Generation

1. Go to **ContentGem AI** > **Bulk Generate**
2. Enter multiple prompts (one per line)
3. Configure company information and common settings
4. Click "Start Bulk Generation"
5. Monitor progress in real-time
6. Review and save generated content

### Company Management

1. Go to **ContentGem AI** > **Company Settings**
2. View current company information
3. Update company details manually
4. Or use "Parse Website" to automatically extract information
5. Save changes for use in content generation

### Subscription Status

1. Go to **ContentGem AI** > **Subscription Status**
2. View current subscription plan and access status
3. Check API configuration status
4. Clear cache if needed after subscription changes
5. Get troubleshooting help and support links

### Shortcodes

Use shortcodes to display generated content anywhere:

```php
[contentgem_generator prompt="Write about AI in business"]
```

```php
[contentgem_display post_id="123"]
```

## API Integration

The plugin uses the ContentGem API for content generation:

- **Content Generation**: Create blog posts, articles, and other content
- **Bulk Generation**: Generate multiple publications simultaneously
- **Company Management**: Manage company information and website parsing
- **Subscription Verification**: Automatic checking of Business/Pro/Enterprise plans
- **Image Generation**: Generate AI images for your content
- **Status Checking**: Monitor generation progress
- **Error Handling**: Comprehensive error handling and user feedback

## Subscription Requirements

The ContentGem AI WordPress plugin requires a **Business, Pro, or Enterprise subscription** to function. The plugin automatically:

- ✅ **Verifies subscription status** before allowing access
- ✅ **Checks plan level** (Business/Pro/Enterprise required)
- ✅ **Validates API key** and configuration
- ✅ **Caches results** for 5 minutes to improve performance
- ✅ **Provides clear error messages** when access is denied
- ✅ **Offers upgrade links** for users with insufficient plans

### Supported Plans

- **Business Plan** - Full plugin access
- **Pro Plan** - Full plugin access
- **Enterprise Plan** - Full plugin access
- **Basic Plan** - ❌ Plugin access not included

### Troubleshooting

If you encounter subscription-related issues:

1. Check your subscription status in **ContentGem AI** > **Subscription Status**
2. Verify your API key is correctly configured
3. Ensure your subscription is active and not expired
4. Clear the subscription cache if you recently upgraded
5. Contact support if issues persist

## Hooks and Filters

### Actions

```php
// Fired when content generation starts
do_action('contentgem_wp_generation_started', $prompt, $company_info);

// Fired when content generation completes
do_action('contentgem_wp_generation_completed', $result, $post_id);

// Fired when content generation fails
do_action('contentgem_wp_generation_failed', $error, $prompt);
```

### Filters

```php
// Modify company information before generation
add_filter('contentgem_wp_company_info', function($company_info) {
    $company_info['name'] = 'My Custom Company';
    return $company_info;
});

// Modify generated content before saving
add_filter('contentgem_wp_generated_content', function($content) {
    return $content . "\n\n<!-- Generated by ContentGem AI -->";
});
```

## Development

### File Structure

```
contentgem-wp/
├── contentgem-wp.php          # Main plugin file
├── includes/                  # PHP classes
│   ├── Plugin.php            # Main plugin class
│   ├── API/                  # API integration
│   ├── Admin/                # Admin interface
│   ├── Generator/            # Content generation
│   └── Shortcodes/           # Shortcode handlers
├── assets/                   # CSS, JS, images
├── templates/                # Template files
├── languages/                # Translation files
└── README.md                 # This file
```

### Adding Custom Features

1. Create new classes in the `includes/` directory
2. Follow PSR-4 autoloading standards
3. Use WordPress coding standards
4. Add proper documentation

### Testing

```bash
# Run PHPUnit tests
composer test

# Run code style checks
composer cs-check

# Fix code style issues
composer cs-fix
```

## Troubleshooting

### Common Issues

1. **API Key Not Working**

   - Verify your API key is correct
   - Check if your subscription is active
   - Ensure the base URL is correct

2. **Content Not Generating**

   - Check your internet connection
   - Verify API endpoint is accessible
   - Check error logs for details

3. **Plugin Not Loading**
   - Ensure PHP version is 7.4 or higher
   - Check WordPress version compatibility
   - Verify plugin files are complete

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

- **Documentation**: https://docs.contentgem.com/wordpress
- **Support**: support@contentgem.com
- **GitHub**: https://github.com/contentgem/contentgem-wp

## License

GPL v2 or later

## Changelog

### 1.0.0

- Initial release
- Basic content generation
- Admin interface
- Meta box integration
- Shortcode support
