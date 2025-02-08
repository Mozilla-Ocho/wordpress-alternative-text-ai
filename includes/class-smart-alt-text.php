<?php
namespace SmartAltText;

class SmartAltText {
    /**
     * The unique instance of the plugin.
     *
     * @var SmartAltText
     */
    private static $instance;

    /**
     * Gets an instance of our plugin.
     *
     * @return SmartAltText
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize the plugin.
     */
    public function init() {
        // Load plugin dependencies
        $this->load_dependencies();

        // Add hooks and filters
        $this->add_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Core plugin class files
        require_once SMART_ALT_TEXT_PLUGIN_DIR . 'includes/class-admin.php';
        require_once SMART_ALT_TEXT_PLUGIN_DIR . 'includes/class-image-analyzer.php';
    }

    /**
     * Register all hooks for the plugin.
     */
    private function add_hooks() {
        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(SMART_ALT_TEXT_PLUGIN_DIR . 'smart-alt-text.php'), 
            [$this, 'add_plugin_links']);
    }

    /**
     * Add plugin action links.
     *
     * @param array $links Plugin action links.
     * @return array Modified plugin action links.
     */
    public function add_plugin_links($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=smart-alt-text') . '">' . __('Settings', 'solo-ai-website-creator-alt-text-generator') . '</a>',
        ];
        return array_merge($plugin_links, $links);
    }
} 