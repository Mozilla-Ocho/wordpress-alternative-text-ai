<?php
namespace SoloAI;

/**
 * Logger class for handling plugin logging
 */
class Logger {
    private static $log_file;
    private static $max_size = 5242880; // 5MB

    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/solo-ai-alt-text-debug.log';
    }

    /**
     * Log a message
     *
     * @param string $message The message to log
     */
    public static function log($message) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        try {
            // Initialize WP_Filesystem
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
            global $wp_filesystem;

            if (!$wp_filesystem) {
                return;
            }

            // Create timestamp using gmdate
            $timestamp = gmdate('Y-m-d H:i:s');
            $formatted_message = sprintf("[%s] %s\n", $timestamp, $message);

            // Check file size
            if ($wp_filesystem->exists(self::$log_file)) {
                $size = $wp_filesystem->size(self::$log_file);
                if ($size > self::$max_size) {
                    // Create backup name using gmdate
                    $backup_file = WP_CONTENT_DIR . '/solo-ai-alt-text-debug-' . gmdate('Y-m-d-H-i-s') . '.log';
                    
                    // Move old log to backup
                    $wp_filesystem->move(self::$log_file, $backup_file, true);
                }
            }

            // Append to log file
            $wp_filesystem->put_contents(
                self::$log_file,
                $formatted_message,
                FILE_APPEND
            );

        } catch (\Exception $e) {
            // Fail silently in production
            return;
        }
    }
} 