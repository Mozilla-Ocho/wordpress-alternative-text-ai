<?php
namespace SmartAltText;

use SoloAI\Logger;

class Admin {
    /**
     * Initialize the admin functionality.
     */
    public function init() {
        try {
            // Initialize Logger
            Logger::init();
            
            // Add menu pages
            add_action('admin_menu', [$this, 'add_menu_page']);
            
            // Register settings
            add_action('admin_init', [$this, 'register_settings']);
            
            // Add settings link to plugins page
            add_filter('plugin_action_links_' . plugin_basename(SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'solo-ai-website-creator-alt-text-generator.php'), 
                      [$this, 'add_settings_link']);
            
            // Enqueue admin scripts and styles
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
            
            // Register AJAX handlers
            add_action('wp_ajax_save_alt_text', [$this, 'handle_save_alt_text']);
            add_action('wp_ajax_analyze_image', [$this, 'handle_analyze_image']);
            add_action('wp_ajax_get_api_key_debug_info', [$this, 'handle_get_api_key_debug_info']);

            // Add admin notice for missing API key
            add_action('admin_notices', [$this, 'display_api_key_notice']);

            // Handle new image uploads
            add_action('add_attachment', [$this, 'handle_new_image']);
            
        } catch (\Exception $e) {
            Logger::log('Initialization error: ' . $e->getMessage());
        }
    }

    /**
     * Add settings link to plugin listing.
     *
     * @param array $links Array of plugin action links.
     * @return array Modified array of plugin action links.
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=solo-ai-website-creator-alt-text')),
            esc_html__('Settings', 'solo-ai-website-creator-alt-text-generator')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Add menu pages to WordPress admin.
     */
    public function add_menu_page() {
        $parent_slug = 'solo-ai-website-creator';
        
        // Check if the menu already exists using the global $menu array
        global $menu;
        $menu_exists = false;
        if (is_array($menu)) {
            foreach ($menu as $item) {
                if (isset($item[2]) && $item[2] === $parent_slug) {
                    $menu_exists = true;
                    break;
                }
            }
        }
        
        // Add main menu if it doesn't exist
        if (!$menu_exists) {
            add_menu_page(
                esc_html__('Solo AI Website Creator', 'solo-ai-website-creator-alt-text-generator'),
                esc_html__('Solo AI', 'solo-ai-website-creator-alt-text-generator'),
                'manage_options',
                $parent_slug,
                array($this, 'render_dashboard_page'),
                'data:image/svg+xml;base64,' . base64_encode(file_get_contents(SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'assets/images/solo-ai-icon.svg')),
                30
            );
        }

        // Add submenu pages
        add_submenu_page(
            $parent_slug,
            esc_html__('Alt Text Generator', 'solo-ai-website-creator-alt-text-generator'),
            esc_html__('Settings', 'solo-ai-website-creator-alt-text-generator'),
            'manage_options',
            $parent_slug . '-alt-text',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            $parent_slug,
            esc_html__('Bulk Process Images', 'solo-ai-website-creator-alt-text-generator'),
            esc_html__('Bulk Process', 'solo-ai-website-creator-alt-text-generator'),
            'manage_options',
            $parent_slug . '-bulk-process',
            array($this, 'render_bulk_page')
        );

        add_submenu_page(
            $parent_slug,
            esc_html__('Usage Statistics', 'solo-ai-website-creator-alt-text-generator'),
            esc_html__('Statistics', 'solo-ai-website-creator-alt-text-generator'),
            'manage_options',
            $parent_slug . '-stats',
            array($this, 'render_stats_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'solo_ai_website_creator_alt_text_options',
            'solo_ai_website_creator_alt_text_settings',
            array(
                'type' => 'object',
                'description' => 'Settings for Solo AI Alt Text Generator',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'validate_callback' => array($this, 'validate_settings'),
                'default' => array(
                    'api_key' => '',
                    'prefix' => '',
                    'suffix' => '',
                    'auto_generate' => false,
                    'update_title' => false,
                    'update_caption' => false,
                    'update_description' => false
                ),
                'show_in_rest' => false,
                'single' => true
            )
        );

        // Add settings section
        add_settings_section(
            'solo_ai_website_creator_alt_text_main_section',
            esc_html__('API Settings', 'solo-ai-website-creator-alt-text-generator'),
            array($this, 'render_section_main'),
            'solo_ai_website_creator_alt_text_options'
        );

        // Add API Key field
        add_settings_field(
            'api_key',
            esc_html__('API Key', 'solo-ai-website-creator-alt-text-generator'),
            array($this, 'render_api_key_field'),
            'solo_ai_website_creator_alt_text_options',
            'solo_ai_website_creator_alt_text_main_section'
        );

        // Add Auto Generate field
        add_settings_field(
            'auto_generate',
            esc_html__('Auto Generate', 'solo-ai-website-creator-alt-text-generator'),
            array($this, 'render_auto_generate_field'),
            'solo_ai_website_creator_alt_text_options',
            'solo_ai_website_creator_alt_text_main_section'
        );

        // Add Prefix/Suffix fields
        add_settings_field(
            'prefix_suffix',
            esc_html__('Prefix/Suffix', 'solo-ai-website-creator-alt-text-generator'),
            array($this, 'render_prefix_suffix_fields'),
            'solo_ai_website_creator_alt_text_options',
            'solo_ai_website_creator_alt_text_main_section'
        );

        // Add Update Fields
        add_settings_field(
            'update_fields',
            esc_html__('Update Fields', 'solo-ai-website-creator-alt-text-generator'),
            array($this, 'render_update_fields'),
            'solo_ai_website_creator_alt_text_options',
            'solo_ai_website_creator_alt_text_main_section'
        );
    }

    /**
     * Validate settings before sanitization
     * 
     * @param mixed $value The value to validate
     * @param array $args The args passed to register_setting
     * @param string $key The key of the field to validate
     * @return mixed The validated value or WP_Error
     */
    public function validate_settings($value, $args, $key) {
        if (!is_array($value)) {
            return new \WP_Error('invalid_type', __('Settings must be an array.', 'solo-ai-website-creator-alt-text-generator'));
        }

        // Validate API key if present and not encrypted
        if (isset($value['api_key']) && !empty($value['api_key']) && strlen($value['api_key']) <= 100) {
            if (!$this->validate_api_key($value['api_key'])) {
                return new \WP_Error('invalid_api_key', __('Invalid API key format.', 'solo-ai-website-creator-alt-text-generator'));
            }
        }

        // Validate boolean fields
        $bool_fields = array('auto_generate', 'update_title', 'update_caption', 'update_description');
        foreach ($bool_fields as $field) {
            if (isset($value[$field]) && !is_bool($value[$field])) {
                $value[$field] = (bool) $value[$field];
            }
        }

        return $value;
    }

    /**
     * Sanitize settings before saving
     *
     * @param array $input The settings array to sanitize
     * @return array The sanitized settings
     */
    public function sanitize_settings($input) {
        if (!is_array($input)) {
            return $this->get_default_settings();
        }

        $sanitized = [];
        
        // API Key - Minimal sanitization to preserve the key format
        if (isset($input['api_key']) && !empty($input['api_key'])) {
            // Only remove whitespace and slashes
            $api_key = trim(stripslashes($input['api_key']));
            
            // Check if this is an already encrypted key (they are always longer than 100 chars)
            if (strlen($api_key) > 100) {
                Logger::log('Detected encrypted key, skipping validation');
                $sanitized['api_key'] = $api_key;
            } else {
                // First validate the key format
                $is_valid = $this->validate_api_key($api_key);
                
                if (!$is_valid) {
                    add_settings_error(
                        'solo_ai_website_creator_alt_text_settings',
                        'invalid_api_key',
                        __('Invalid API key format. Please check your API key.', 'solo-ai-website-creator-alt-text-generator')
                    );
                    $sanitized['api_key'] = '';
                } else {
                    // If validation passes, try to encrypt
                    $encrypted_key = $this->encrypt_api_key($api_key);
                    
                    if (empty($encrypted_key)) {
                        add_settings_error(
                            'solo_ai_website_creator_alt_text_settings',
                            'encryption_error',
                            __('Error encrypting API key. Please try again.', 'solo-ai-website-creator-alt-text-generator')
                        );
                        $sanitized['api_key'] = '';
                    } else {
                        add_settings_error(
                            'solo_ai_website_creator_alt_text_settings',
                            'success',
                            __('API key saved successfully.', 'solo-ai-website-creator-alt-text-generator'),
                            'success'
                        );
                        $sanitized['api_key'] = $encrypted_key;
                    }
                }
            }
        } else {
            $existing_settings = get_option('solo_ai_website_creator_alt_text_settings', []);
            $sanitized['api_key'] = isset($existing_settings['api_key']) ? $existing_settings['api_key'] : '';
        }

        // Rest of the settings remain unchanged
        $sanitized['prefix'] = isset($input['prefix']) ? sanitize_text_field($input['prefix']) : '';
        $sanitized['suffix'] = isset($input['suffix']) ? sanitize_text_field($input['suffix']) : '';
        $sanitized['auto_generate'] = isset($input['auto_generate']) ? (bool) $input['auto_generate'] : false;
        $sanitized['update_title'] = isset($input['update_title']) ? (bool) $input['update_title'] : false;
        $sanitized['update_caption'] = isset($input['update_caption']) ? (bool) $input['update_caption'] : false;
        $sanitized['update_description'] = isset($input['update_description']) ? (bool) $input['update_description'] : false;

        return $sanitized;
    }

    /**
     * Get default settings
     *
     * @return array Default settings
     */
    private function get_default_settings() {
        return [
            'api_key' => '',
            'prefix' => '',
            'suffix' => '',
            'auto_generate' => false,
            'update_title' => false,
            'update_caption' => false,
            'update_description' => false
        ];
    }

    /**
     * Log debug information if WP_DEBUG is enabled
     */
    private function log_debug($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($data !== null) {
                $message .= ' | Data: ' . wp_json_encode($data);
            }
            // Use WordPress debug log if enabled
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                Logger::log($message);
            }
        }
    }

    /**
     * Validate API key format
     *
     * @param string $value The API key to validate
     * @return bool Whether the API key is valid
     */
    private function validate_api_key($value) {
        // Debug info
        $debug_info = array(
            'key_length' => strlen($value),
            'starts_with_sk' => strpos($value, 'sk-') === 0,
            'raw_key' => substr($value, 0, 10) . '...',
            'contains_whitespace' => preg_match('/\s/', $value) === 1,
            'contains_special_chars' => preg_match('/[^a-zA-Z0-9\-]/', $value) === 1
        );

        $this->log_debug('API Key validation info', $debug_info);

        // Check if key is empty
        if (empty($value)) {
            $this->log_debug('Empty key');
            return false;
        }

        // Check if key starts with sk-
        if (strpos($value, 'sk-') !== 0) {
            $this->log_debug('Key does not start with sk-');
            return false;
        }

        // Check key length (OpenAI keys are typically around 51 characters)
        if (strlen($value) < 45 || strlen($value) > 60) {
            $this->log_debug('Invalid key length: ' . strlen($value));
            return false;
        }

        // Store debug info
        update_option('solo_ai_alt_text_debug_info', $debug_info, false);

        $this->log_debug('Key is valid');
        return true;
    }

    /**
     * Display admin notices
     */
    public function display_admin_notice($message, $type = 'success') {
        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr($type),
            esc_html($message)
        );
    }

    /**
     * Display error message
     */
    public function display_error($message) {
        printf(
            '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
            esc_html($message)
        );
    }

    /**
     * Display success message
     */
    public function display_success($message) {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html($message)
        );
    }

    /**
     * Render API settings section
     */
    public function render_api_settings_section($args) {
        echo '<p>' . esc_html__('Configure your X.AI API key to enable image analysis.', 'solo-ai-website-creator-alt-text-generator') . '</p>';
    }

    /**
     * Render generation settings section
     */
    public function render_generation_settings_section($args) {
        echo '<p>' . esc_html__('Configure how alt text is generated and applied.', 'solo-ai-website-creator-alt-text-generator') . '</p>';
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        try {
            $settings = get_option('solo_ai_website_creator_alt_text_settings', []);
            $api_key = isset($settings['api_key']) ? $this->get_api_key() : '';
            ?>
            <input type="password" 
                   id="solo_ai_alt_text_api_key" 
                   name="solo_ai_website_creator_alt_text_settings[api_key]" 
                   value="<?php echo esc_attr($api_key); ?>" 
                   class="regular-text"
                   autocomplete="off">
            <p class="description">
                <?php echo wp_kses(
                    sprintf(
                        /* translators: %s: X.AI Dashboard URL */
                        __('Enter your OpenAI API key. Get one from <a href="%s" target="_blank">OpenAI Dashboard</a>', 'solo-ai-website-creator-alt-text-generator'),
                        'https://platform.openai.com/account/api-keys'
                    ),
                    array('a' => array('href' => array(), 'target' => array()))
                ); ?>
            </p>
            <?php
        } catch (\Exception $e) {
            Logger::log('Error rendering API key field: ' . $e->getMessage());
        }
    }

    /**
     * Render auto-generate field
     */
    public function render_auto_generate_field() {
        $settings = get_option('solo_ai_website_creator_alt_text_settings', []);
        $auto_generate = isset($settings['auto_generate']) ? $settings['auto_generate'] : false;
        ?>
        <label>
            <input type="checkbox" 
                   name="solo_ai_website_creator_alt_text_settings[auto_generate]" 
                   value="1" 
                   <?php checked($auto_generate, true); ?>>
            <?php esc_html_e('Automatically generate alt text when images are uploaded', 'solo-ai-website-creator-alt-text-generator'); ?>
        </label>
        <?php
    }

    /**
     * Render prefix/suffix fields
     */
    public function render_prefix_suffix_fields() {
        $settings = get_option('solo_ai_website_creator_alt_text_settings', []);
        $prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
        $suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
        ?>
        <p>
            <input type="text" 
                   name="solo_ai_website_creator_alt_text_settings[prefix]" 
                   value="<?php echo esc_attr($prefix); ?>" 
                   class="regular-text" 
                   placeholder="<?php esc_attr_e('Prefix text', 'solo-ai-website-creator-alt-text-generator'); ?>">
            <span class="description"><?php esc_html_e('Text to add before the generated alt text', 'solo-ai-website-creator-alt-text-generator'); ?></span>
        </p>
        <p>
            <input type="text" 
                   name="solo_ai_website_creator_alt_text_settings[suffix]" 
                   value="<?php echo esc_attr($suffix); ?>" 
                   class="regular-text" 
                   placeholder="<?php esc_attr_e('Suffix text', 'solo-ai-website-creator-alt-text-generator'); ?>">
            <span class="description"><?php esc_html_e('Text to add after the generated alt text', 'solo-ai-website-creator-alt-text-generator'); ?></span>
        </p>
        <?php
    }

    /**
     * Render update fields
     */
    public function render_update_fields() {
        $settings = get_option('solo_ai_website_creator_alt_text_settings', []);
        $update_title = isset($settings['update_title']) ? $settings['update_title'] : false;
        $update_caption = isset($settings['update_caption']) ? $settings['update_caption'] : false;
        $update_description = isset($settings['update_description']) ? $settings['update_description'] : false;
        ?>
        <fieldset>
            <label>
                <input type="checkbox" 
                       name="solo_ai_website_creator_alt_text_settings[update_title]" 
                       value="1" 
                       <?php checked($update_title, true); ?>>
                <?php esc_html_e('Update image title', 'solo-ai-website-creator-alt-text-generator'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" 
                       name="solo_ai_website_creator_alt_text_settings[update_caption]" 
                       value="1" 
                       <?php checked($update_caption, true); ?>>
                <?php esc_html_e('Update image caption', 'solo-ai-website-creator-alt-text-generator'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" 
                       name="solo_ai_website_creator_alt_text_settings[update_description]" 
                       value="1" 
                       <?php checked($update_description, true); ?>>
                <?php esc_html_e('Update image description', 'solo-ai-website-creator-alt-text-generator'); ?>
            </label>
        </fieldset>
        <?php
    }

    /**
     * Encrypt API key before saving
     */
    public function encrypt_api_key($api_key) {
        try {
            Logger::log('Starting encryption process');
            Logger::log('Input key length: ' . strlen($api_key));

            if (empty($api_key)) {
                Logger::log('Empty API key');
                return '';
            }

            // Get the encryption key
            $encryption_key = $this->get_encryption_key();
            if (empty($encryption_key)) {
                Logger::log('Failed to get encryption key');
                return '';
            }
            Logger::log('Got encryption key');

            // Generate a random IV
            $iv = openssl_random_pseudo_bytes(16);
            if ($iv === false) {
                Logger::log('Failed to generate IV');
                return '';
            }
            Logger::log('IV generated');
            
            // Base64 encode the API key first to handle special characters
            $prepared_key = base64_encode($api_key);
            Logger::log('Key prepared for encryption');
            
            // Encrypt the prepared API key
            $encrypted = openssl_encrypt(
                $prepared_key,
                'AES-256-CBC',
                base64_decode($encryption_key),
                0,
                $iv
            );

            if ($encrypted === false) {
                Logger::log('Encryption failed');
                return '';
            }

            // Combine IV and encrypted data
            $combined = base64_encode($iv . $encrypted);
            Logger::log('Encryption successful. Result length: ' . strlen($combined));
            
            return $combined;

        } catch (\Exception $e) {
            Logger::log('Error: ' . $e->getMessage());
            Logger::log('Error Stack Trace: ' . $e->getTraceAsString());
            return '';
        }
    }

    /**
     * Get encryption key
     */
    private function get_encryption_key() {
        try {
            // Try to get existing key
            $key = get_option('solo_ai_website_creator_alt_text_encryption_key');
            
            // If no key exists, create one and store it
            if (empty($key)) {
                $key = base64_encode(openssl_random_pseudo_bytes(32));
                update_option('solo_ai_website_creator_alt_text_encryption_key', $key);
            }
            
            return $key;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get decrypted API key
     */
    public function get_api_key() {
        try {
            Logger::log('Starting decryption process');
            
            $settings = get_option('solo_ai_website_creator_alt_text_settings', []);
            $encrypted_key = isset($settings['api_key']) ? $settings['api_key'] : '';
            
            if (empty($encrypted_key)) {
                Logger::log('No encrypted key found');
                return '';
            }

            // Get the encryption key
            $encryption_key = $this->get_encryption_key();
            if (empty($encryption_key)) {
                Logger::log('Failed to get encryption key');
                return '';
            }

            // Decode the combined string
            $decoded = base64_decode($encrypted_key);
            if ($decoded === false) {
                Logger::log('Failed to decode combined string');
                return '';
            }

            // Extract IV and encrypted data
            $iv = substr($decoded, 0, 16);
            $encrypted_data = substr($decoded, 16);

            // Decrypt the API key
            $decrypted = openssl_decrypt(
                $encrypted_data,
                'AES-256-CBC',
                base64_decode($encryption_key),
                0,
                $iv
            );

            if ($decrypted === false) {
                Logger::log('Decryption failed');
                return '';
            }

            // Base64 decode to get original key
            $original_key = base64_decode($decrypted);
            if ($original_key === false) {
                Logger::log('Failed to decode decrypted key');
                return '';
            }

            Logger::log('Successfully decrypted key');
            return $original_key;

        } catch (\Exception $e) {
            Logger::log('Decryption Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_scripts($hook) {
        try {
            // Check if we're on our plugin pages or media pages
            $is_plugin_page = (
                strpos($hook, 'solo-ai') !== false ||
                strpos($hook, 'upload.php') !== false ||
                strpos($hook, 'post.php') !== false ||
                strpos($hook, 'post-new.php') !== false ||
                strpos($hook, 'media-new.php') !== false
            );

            // Always load on media pages
            if (!$is_plugin_page) {
                return;
            }

            // Get plugin directory URL
            $plugin_dir_url = plugin_dir_url(dirname(__FILE__));

            // Get settings and API key
            $settings = get_option('solo_ai_website_creator_alt_text_settings', []);
            $api_key = $this->get_api_key();

            // First enqueue CSS
            wp_enqueue_style(
                'solo-ai-alt-text-admin',
                $plugin_dir_url . 'assets/css/admin.css',
                [],
                SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_VERSION
            );

            // Then enqueue JavaScript with proper dependencies
            wp_enqueue_script(
                'solo-ai-alt-text-admin',
                $plugin_dir_url . 'assets/js/admin.js',
                ['jquery', 'wp-util', 'wp-i18n'],
                SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_VERSION . '.' . time(),
                true
            );

            // Finally localize the script
            wp_localize_script(
                'solo-ai-alt-text-admin',
                'solo_ai_alt_text_obj',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('solo_ai_alt_text_nonce'),
                    'prefix' => isset($settings['prefix']) ? $settings['prefix'] : '',
                    'suffix' => isset($settings['suffix']) ? $settings['suffix'] : '',
                    'has_api_key' => !empty($api_key),
                    'hook' => $hook,
                    'plugin_url' => $plugin_dir_url,
                    'i18n' => [
                        'saving' => __('Saving...', 'solo-ai-website-creator-alt-text-generator'),
                        'saved' => __('Saved!', 'solo-ai-website-creator-alt-text-generator'),
                        'analyzing' => __('Analyzing...', 'solo-ai-website-creator-alt-text-generator'),
                        'analyzed' => __('Done!', 'solo-ai-website-creator-alt-text-generator'),
                        'error' => __('Error', 'solo-ai-website-creator-alt-text-generator'),
                        'no_api_key' => __('Please configure your X.AI API key in the plugin settings', 'solo-ai-website-creator-alt-text-generator')
                    ]
                ]
            );

        } catch (\Exception $e) {
            // Log to our custom logger instead of error_log
            Logger::log('Script enqueue error: ' . $e->getMessage());
        }
    }

    /**
     * Handle AJAX request to save alt text
     */
    public function handle_save_alt_text() {
        try {
            // Verify nonce
            if (!check_ajax_referer('solo_ai_alt_text_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => 'Invalid security token']);
                return;
            }

            // Check permissions
            if (!current_user_can('upload_files')) {
                wp_send_json_error(['message' => 'You do not have permission to perform this action']);
                return;
            }

            // Get and validate parameters
            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            $alt_text = isset($_POST['alt_text']) ? sanitize_text_field(wp_unslash($_POST['alt_text'])) : '';

            if (!$image_id) {
                wp_send_json_error(['message' => 'Invalid image ID']);
                return;
            }

            // Update the alt text
            $result = update_post_meta($image_id, '_wp_attachment_image_alt', wp_slash($alt_text));

            // update_post_meta returns false if value hasn't changed, which is not an error
            if ($result === false && get_post_meta($image_id, '_wp_attachment_image_alt', true) !== $alt_text) {
                wp_send_json_error(['message' => 'Failed to save alt text']);
                return;
            }

            // Force refresh attachment metadata
            clean_post_cache($image_id);

            // Also update other fields if enabled
            $settings = get_option('solo_ai_website_creator_alt_text_settings', []);
            if (isset($settings['update_title']) && $settings['update_title']) {
                wp_update_post([
                    'ID' => $image_id,
                    'post_title' => $alt_text
                ]);
            }

            if (isset($settings['update_caption']) && $settings['update_caption']) {
                wp_update_post([
                    'ID' => $image_id,
                    'post_excerpt' => $alt_text
                ]);
            }

            if (isset($settings['update_description']) && $settings['update_description']) {
                wp_update_post([
                    'ID' => $image_id,
                    'post_content' => $alt_text
                ]);
            }

            wp_send_json_success([
                'message' => 'Alt text saved successfully',
                'alt_text' => $alt_text
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Error saving alt text: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle AJAX request to analyze image
     */
    public function handle_analyze_image() {
        try {
            // Initialize debug info array for logging
            $debug_info = ['steps' => []];
            $debug_info['steps'][] = '[1] Starting image analysis request';
            
            // Verify nonce
            if (!check_ajax_referer('solo_ai_alt_text_nonce', 'nonce', false)) {
                $debug_info['steps'][] = '[ERROR] Nonce verification failed';
                Logger::log('Nonce verification failed in image analysis');
                wp_send_json_error([
                    'message' => 'Invalid security token',
                    'debug_info' => $debug_info['steps']
                ]);
                return;
            }
            $debug_info['steps'][] = '[2] Nonce verified';

            // Check permissions
            if (!current_user_can('upload_files')) {
                $debug_info['steps'][] = '[ERROR] Permission check failed';
                wp_send_json_error([
                    'message' => 'You do not have permission to perform this action',
                    'debug_info' => $debug_info['steps']
                ]);
                return;
            }
            $debug_info['steps'][] = '[3] Permissions verified';

            // Get and validate parameters
            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            $image_url = isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '';
            
            $debug_info['steps'][] = '[4] Parameters received - ID: ' . $image_id . ', URL: ' . $image_url;

            if (!$image_id || !$image_url) {
                $debug_info['steps'][] = '[ERROR] Invalid image data';
                wp_send_json_error([
                    'message' => 'Invalid image data',
                    'debug_info' => $debug_info['steps']
                ]);
                return;
            }

            // Get API key
            $api_key = $this->get_api_key();
            $debug_info['steps'][] = '[5] API key retrieved - Length: ' . strlen($api_key);
            
            if (empty($api_key)) {
                $debug_info['steps'][] = '[ERROR] Empty API key';
                wp_send_json_error([
                    'message' => 'Please configure your X.AI API key in the plugin settings',
                    'debug_info' => $debug_info['steps']
                ]);
                return;
            }

            $debug_info['steps'][] = '[6] Initializing ImageAnalyzer';
            
            // Initialize the image analyzer
            $analyzer = new \SmartAltText\ImageAnalyzer($api_key);

            try {
                $debug_info['steps'][] = '[7] Starting image analysis';
                // Analyze the image
                $result = $analyzer->analyze_image($image_url);
                
                if (!$result['success']) {
                    $debug_info['steps'][] = '[ERROR] Analysis failed: ' . $result['error'];
                    wp_send_json_error([
                        'message' => $result['error'],
                        'debug_info' => array_merge($debug_info['steps'], $result['debug_info'])
                    ]);
                    return;
                }
                
                $alt_text = $result['alt_text'];
                $debug_info['steps'] = array_merge($debug_info['steps'], $result['debug_info']);
                
                $debug_info['steps'][] = '[8] Analysis completed - Result length: ' . strlen($alt_text);
                
                if (empty($alt_text)) {
                    $debug_info['steps'][] = '[ERROR] Empty response from API';
                    wp_send_json_error([
                        'message' => 'Failed to generate alt text. Please try again.',
                        'debug_info' => $debug_info['steps']
                    ]);
                    return;
                }

                // Apply prefix/suffix
                $settings = get_option('solo_ai_website_creator_alt_text_settings', []);
                $prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
                $suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
                
                if (!empty($prefix)) {
                    $alt_text = trim($prefix) . ' ' . $alt_text;
                }
                if (!empty($suffix)) {
                    $alt_text = $alt_text . ' ' . trim($suffix);
                }

                $debug_info['steps'][] = '[9] Updating post meta';
                // Update the alt text
                $update_result = update_post_meta($image_id, '_wp_attachment_image_alt', wp_slash($alt_text));
                
                if ($update_result === false) {
                    $debug_info['steps'][] = '[ERROR] Failed to update post meta';
                    wp_send_json_error([
                        'message' => 'Failed to save alt text',
                        'debug_info' => $debug_info['steps']
                    ]);
                    return;
                }
                
                $debug_info['steps'][] = '[10] Post meta updated successfully';
                
                wp_send_json_success([
                    'message' => 'Alt text generated and saved successfully',
                    'alt_text' => $alt_text,
                    'debug_info' => $debug_info['steps']
                ]);

            } catch (\Exception $e) {
                $debug_info['steps'][] = '[ERROR] Analysis error: ' . $e->getMessage();
                wp_send_json_error([
                    'message' => $e->getMessage(),
                    'debug_info' => $debug_info['steps']
                ]);
                return;
            }

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wp_send_json_error([
                    'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                    'debug_info' => [
                        'error_type' => get_class($e),
                        'error_message' => $e->getMessage(),
                        'error_trace' => $e->getTraceAsString()
                    ]
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'An unexpected error occurred. Please try again later.'
                ]);
            }
        }
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'solo-ai-website-creator-alt-text-generator'));
        }
        require_once SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Render the bulk processing page.
     */
    public function render_bulk_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'solo-ai-website-creator-alt-text-generator'));
        }
        require_once SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'templates/bulk-page.php';
    }

    /**
     * Render the stats page.
     */
    public function render_stats_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'solo-ai-website-creator-alt-text-generator'));
        }
        require_once SOLO_AI_WEBSITE_CREATOR_ALT_TEXT_PLUGIN_DIR . 'templates/stats-page.php';
    }

    /**
     * Render the main settings section description.
     */
    public function render_section_main() {
        echo '<p>' . esc_html__('Configure your Solo AI Alt Text Generator settings below.', 'solo-ai-website-creator-alt-text-generator') . '</p>';
    }

    /**
     * Handle new image upload for auto-generation
     */
    public function handle_new_image($attachment_id) {
        try {
            // Get settings
            $settings = get_option('solo_ai_website_creator_alt_text_settings', []);
            
            // Check if auto-generation is enabled
            if (!isset($settings['auto_generate']) || !$settings['auto_generate']) {
                return;
            }

            // Check if it's an image
            if (!wp_attachment_is_image($attachment_id)) {
                return;
            }

            // Get image URL
            $image_url = wp_get_attachment_url($attachment_id);
            if (!$image_url) {
                return;
            }

            // Check file type
            $file_info = wp_check_filetype($image_url);
            $allowed_types = ['jpg', 'jpeg', 'png'];
            
            if (!in_array(strtolower($file_info['ext']), $allowed_types)) {
                return;
            }

            // Get API key
            $api_key = $this->get_api_key();
            if (empty($api_key)) {
                return;
            }

            // Initialize the image analyzer
            $analyzer = new \SmartAltText\ImageAnalyzer($api_key);

            // Get the alt text
            $result = $analyzer->analyze_image($image_url);
            if (!$result['success'] || empty($result['alt_text'])) {
                Logger::log('Failed to generate alt text: ' . ($result['error'] ?? 'Unknown error'));
                return;
            }

            $alt_text = $result['alt_text'];

            // Apply prefix/suffix
            $prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
            $suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
            
            if (!empty($prefix)) {
                $alt_text = trim($prefix) . ' ' . $alt_text;
            }
            if (!empty($suffix)) {
                $alt_text = $alt_text . ' ' . trim($suffix);
            }

            // Update alt text
            update_post_meta($attachment_id, '_wp_attachment_image_alt', wp_slash($alt_text));

            // Update other fields if enabled
            if (isset($settings['update_title']) && $settings['update_title']) {
                wp_update_post([
                    'ID' => $attachment_id,
                    'post_title' => $alt_text
                ]);
            }

            if (isset($settings['update_caption']) && $settings['update_caption']) {
                wp_update_post([
                    'ID' => $attachment_id,
                    'post_excerpt' => $alt_text
                ]);
            }

            if (isset($settings['update_description']) && $settings['update_description']) {
                wp_update_post([
                    'ID' => $attachment_id,
                    'post_content' => $alt_text
                ]);
            }

        } catch (\Exception $e) {
            // Silently fail in production
        }
    }

    /**
     * Display admin notice for missing API key
     */
    public function display_api_key_notice() {
        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'solo-ai') === false) {
            return;
        }

        // Get API key
        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            $settings_url = admin_url('admin.php?page=solo-ai-website-creator-alt-text');
            $message = sprintf(
                /* translators: %s: settings page URL */
                __('Please <a href="%s">configure your API key</a> to start using Solo AI Alt Text Generator.', 'solo-ai-website-creator-alt-text-generator'),
                esc_url($settings_url)
            );
            
            printf(
                '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                wp_kses(
                    $message,
                    array(
                        'a' => array('href' => array())
                    )
                )
            );
        }
    }

    /**
     * Clean up hooks and options on deactivation
     */
    public static function deactivate() {
        // Remove any scheduled hooks
        wp_clear_scheduled_hook('solo_ai_alt_text_cleanup');
        
        // Remove the admin notice action
        remove_action('admin_notices', array('SmartAltText\\Admin', 'display_api_key_notice'));
        
        // Clear any transients
        delete_transient('solo_ai_alt_text_api_check');
    }

    /**
     * Render the dashboard page.
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'solo-ai-website-creator-alt-text-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Solo AI Website Creator', 'solo-ai-website-creator-alt-text-generator'); ?></h1>
            <div class="card">
                <h2><?php echo esc_html__('Welcome to Solo AI Website Creator', 'solo-ai-website-creator-alt-text-generator'); ?></h2>
                <p><?php echo esc_html__('Enhance your website with our AI-powered tools:', 'solo-ai-website-creator-alt-text-generator'); ?></p>
                <ul>
                    <li><?php echo esc_html__('Alt Text Generator - Automatically generate descriptive alt text for your images', 'solo-ai-website-creator-alt-text-generator'); ?></li>
                    <li><?php echo esc_html__('Bulk Processing - Update multiple images at once', 'solo-ai-website-creator-alt-text-generator'); ?></li>
                    <li><?php echo esc_html__('Usage Statistics - Track your AI usage', 'solo-ai-website-creator-alt-text-generator'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX request to get API key debug info
     */
    public function handle_get_api_key_debug_info() {
        check_ajax_referer('solo_ai_alt_text_nonce', 'nonce');
        
        $debug_info = get_option('solo_ai_alt_text_debug_info', array());
        wp_send_json_success($debug_info);
    }
} 