<?php
/**
 * Plugin Name: Smart Alt Text
 * Plugin URI: https://example.com/smart-alt-text
 * Description: Automatically generate alt text for images using X.AI's Image Understanding API.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-alt-text
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SMART_ALT_TEXT_VERSION', '1.0.0');
define('SMART_ALT_TEXT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMART_ALT_TEXT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Require necessary files
require_once SMART_ALT_TEXT_PLUGIN_DIR . 'includes/class-admin.php';
require_once SMART_ALT_TEXT_PLUGIN_DIR . 'includes/class-image-analyzer.php';
require_once SMART_ALT_TEXT_PLUGIN_DIR . 'includes/class-activator.php';
require_once SMART_ALT_TEXT_PLUGIN_DIR . 'includes/class-deactivator.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['SmartAltText\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['SmartAltText\Deactivator', 'deactivate']);

// Initialize the plugin
function smart_alt_text_init() {
    try {
        error_log('Smart Alt Text: Initializing plugin');
        
        // Initialize admin functionality
        $admin = new SmartAltText\Admin();
        $admin->init();

        // Add hook for auto-generation of alt text
        add_action('add_attachment', [$admin, 'handle_new_image']);

        error_log('Smart Alt Text: Plugin initialized successfully');
    } catch (Exception $e) {
        error_log('Smart Alt Text Error: ' . $e->getMessage());
    }
}

// Hook into WordPress init
add_action('init', 'smart_alt_text_init'); 