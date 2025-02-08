<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
$options_to_delete = [
    'solo_ai_website_creator_alt_text_settings',
    'solo_ai_website_creator_alt_text_encryption_key',
    'solo_ai_website_creator_alt_text_db_version'
];

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Clear any remaining transients
delete_transient('solo_ai_website_creator_alt_text_processing');

// Clear scheduled hooks
wp_clear_scheduled_hook('solo_ai_website_creator_alt_text_cleanup');
?> 