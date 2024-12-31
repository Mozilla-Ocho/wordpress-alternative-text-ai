<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('smart_alt_text_api_key');
delete_option('smart_alt_text_char_limit');
delete_option('smart_alt_text_formula');
delete_option('smart_alt_text_db_version');

// Drop custom tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}smart_alt_text_usage");

// Clear any remaining transients
delete_transient('smart_alt_text_processing');

// Clear scheduled hooks
wp_clear_scheduled_hook('smart_alt_text_cleanup');
?> 