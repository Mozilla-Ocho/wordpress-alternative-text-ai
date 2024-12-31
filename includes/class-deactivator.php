<?php
namespace SmartAltText;

class Deactivator {
    /**
     * Plugin deactivation hook callback.
     * Cleans up temporary data and caches.
     */
    public static function deactivate() {
        // Clear any processing flags
        delete_transient('smart_alt_text_processing');
        
        // Clear scheduled hooks if any
        wp_clear_scheduled_hook('smart_alt_text_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
} 