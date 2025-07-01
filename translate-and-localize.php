<?php
/**
 * Plugin Name: Translate and Localize with Grok
 * Plugin URI: https://github.com/winnersmedia/translate-and-localize
 * Description: Translate and localize WordPress content using Grok API with Polylang integration and background processing to avoid timeouts.
 * Version: 1.0.0
 * Author: Winners Media Limited
 * Author URI: https://www.winnersmedia.co.uk
 * Text Domain: translate-and-localize
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TAL_VERSION', '1.0.0');
define('TAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TAL_PLUGIN_FILE', __FILE__);

// Database table names
global $wpdb;
define('TAL_QUEUE_TABLE', $wpdb->prefix . 'tal_translation_queue');

// Load required files
require_once TAL_PLUGIN_DIR . 'includes/class-translate-and-localize.php';
require_once TAL_PLUGIN_DIR . 'includes/class-settings.php';
require_once TAL_PLUGIN_DIR . 'includes/class-queue-processor.php';
require_once TAL_PLUGIN_DIR . 'includes/class-grok-api.php';

// Initialize the plugin
add_action('plugins_loaded', function() {
    $plugin = new Translate_And_Localize();
    $plugin->init();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create database table for queue
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE " . TAL_QUEUE_TABLE . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        source_lang varchar(10) NOT NULL,
        target_lang varchar(10) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        prompt text NOT NULL,
        response text NULL,
        error_message text NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        processed_at datetime NULL,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Set default options
    add_option('tal_grok_api_key', '');
    add_option('tal_grok_model', 'grok-beta');
    add_option('tal_default_prompt', 'Translate and localize the following content from {source_lang} to {target_lang}. Maintain the tone and style while adapting cultural references, idioms, and expressions to be appropriate for the target audience. Preserve all HTML formatting.

Content to translate:
{content}');
    
    // Schedule cron event for queue processing
    if (!wp_next_scheduled('tal_process_queue')) {
        wp_schedule_event(time(), 'tal_every_minute', 'tal_process_queue');
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clear scheduled events
    wp_clear_scheduled_hook('tal_process_queue');
});

// Add custom cron interval
add_filter('cron_schedules', function($schedules) {
    $schedules['tal_every_minute'] = array(
        'interval' => 60,
        'display' => __('Every Minute', 'translate-and-localize')
    );
    return $schedules;
});