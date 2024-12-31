<?php
namespace SmartAltText;

class Activator {
    /**
     * Plugin activation hook callback.
     * Sets up database tables and initial plugin options.
     */
    public static function activate() {
        global $wpdb;
        
        // Set default options
        add_option('smart_alt_text_api_key', '');
        add_option('smart_alt_text_char_limit', 125);
        add_option('smart_alt_text_formula', '{description} | {post_title}');
        
        // Create usage tracking table
        $table_name = $wpdb->prefix . 'smart_alt_text_usage';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            image_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            location_type varchar(50) NOT NULL,
            context text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY image_id (image_id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set version in options
        add_option('smart_alt_text_db_version', SMART_ALT_TEXT_VERSION);
        
        // Clear any existing transients
        delete_transient('smart_alt_text_processing');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
} 