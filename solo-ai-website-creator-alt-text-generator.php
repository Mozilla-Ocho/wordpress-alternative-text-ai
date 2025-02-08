<?php
/**
 * Plugin Name: Solo AI Website Creator - Alt text generator
 * Plugin URI: https://soloist.ai/alt-text-generator
 * Description: Part of Solo AI Website Creator suite - AI-powered alt text generation for images to improve accessibility and SEO automatically.
 * Version: 1.0.0
 * Author: Solo AI
 * Author URI: https://soloist.ai
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: solo-ai-website-creator-alt-text-generator
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_VERSION', '1.0.0');
define('SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Create languages directory if it doesn't exist
$languages_dir = SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'languages';
if (!file_exists($languages_dir)) {
    wp_mkdir_p($languages_dir);
}

// Direct include of required files
require_once SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'includes/class-admin.php';
require_once SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'includes/class-logger.php';
require_once SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'includes/class-image-analyzer.php';

// Initialize plugin
function solo_ai_website_creator_alt_text_init() {
    try {
        if (!class_exists('SmartAltText\\Admin')) {
            SoloAI\Logger::log('Admin class not found');
            return;
        }
        
        $admin = new SmartAltText\Admin();
        $admin->init();
        
    } catch (Exception $e) {
        SoloAI\Logger::log('Initialization error: ' . $e->getMessage());
    }
}

// Hook into WordPress init for better compatibility
add_action('init', 'solo_ai_website_creator_alt_text_init');

// Register activation hook
register_activation_hook(__FILE__, function() {
    try {
        // Create required directories
        wp_mkdir_p(SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'languages');
        
        // Ensure required options exist
        add_option('solo_ai_website_creator_alt_text_settings', array(
            'api_key' => '',
            'prefix' => '',
            'suffix' => '',
            'auto_generate' => false,
            'update_title' => false,
            'update_caption' => false,
            'update_description' => false
        ));
        
        // Verify Admin class exists
        if (!class_exists('SmartAltText\\Admin')) {
            throw new Exception('Admin class not found during activation');
        }
        
    } catch (Exception $e) {
        SoloAI\Logger::log('Activation error: ' . $e->getMessage());
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Plugin activation failed. Please check error logs or contact support.');
    }
});

// Register deactivation hook
register_deactivation_hook(__FILE__, function() {
    try {
        if (class_exists('SmartAltText\\Admin')) {
            SmartAltText\Admin::deactivate();
        }
    } catch (Exception $e) {
        SoloAI\Logger::log('Deactivation error: ' . $e->getMessage());
    }
}); 