<?php
namespace SmartAltText;

class Admin {
    /**
     * Initialize the admin functionality.
     */
    public function init() {
        try {
            // Add menu pages
            add_action('admin_menu', [$this, 'add_menu_page']);
            
            // Register settings
            add_action('admin_init', [$this, 'register_settings']);
            
            // Add settings link to plugins page
            add_filter('plugin_action_links_' . plugin_basename(SMART_ALT_TEXT_PLUGIN_DIR . 'smart-alt-text.php'), 
                      [$this, 'add_settings_link']);
            
            // Enqueue admin scripts and styles
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
            
            // Register AJAX handlers
            add_action('wp_ajax_save_alt_text', [$this, 'handle_save_alt_text']);
            add_action('wp_ajax_analyze_image', [$this, 'handle_analyze_image']);
            
        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: Exception in init: ' . $e->getMessage());
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
            admin_url('admin.php?page=smart-alt-text'),
            __('Settings', 'smart-alt-text')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Add menu pages to WordPress admin.
     */
    public function add_menu_page() {
        try {
            error_log('Smart Alt Text - Admin: [1] Adding menu pages');
            
            // Add main menu
            $parent_slug = 'smart-alt-text';
            
            add_menu_page(
                __('Smart Alt Text', 'smart-alt-text'),
                __('Smart Alt Text', 'smart-alt-text'),
                'manage_options',
                $parent_slug,
                [$this, 'render_settings_page'],
                'dashicons-format-image',
                30
            );

            error_log('Smart Alt Text - Admin: [2] Added main menu page');

            // Add submenu pages
            add_submenu_page(
                $parent_slug,
                __('Settings', 'smart-alt-text'),
                __('Settings', 'smart-alt-text'),
                'manage_options',
                $parent_slug,
                [$this, 'render_settings_page']
            );

            add_submenu_page(
                $parent_slug,
                __('Images', 'smart-alt-text'),
                __('Images', 'smart-alt-text'),
                'manage_options',
                $parent_slug . '-images',
                [$this, 'render_bulk_page']
            );

            add_submenu_page(
                $parent_slug,
                __('Usage Stats', 'smart-alt-text'),
                __('Usage Stats', 'smart-alt-text'),
                'manage_options',
                $parent_slug . '-stats',
                [$this, 'render_stats_page']
            );

            error_log('Smart Alt Text - Admin: [3] Added all submenu pages');

        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in add_menu_page: ' . $e->getMessage());
            error_log('Smart Alt Text - Admin: [ERROR] Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        try {
            error_log('Smart Alt Text - Admin: [1] Registering settings');

            // Register settings group
            register_setting(
                'smart_alt_text_settings',
                'smart_alt_text_settings',
                [
                    'type' => 'array',
                    'sanitize_callback' => [$this, 'sanitize_settings'],
                    'default' => [
                        'api_key' => '',
                        'auto_generate' => false,
                        'prefix' => '',
                        'suffix' => '',
                        'update_title' => false,
                        'update_caption' => false,
                        'update_description' => false
                    ]
                ]
            );

            // Register API settings section
            add_settings_section(
                'smart_alt_text_api_settings',
                __('API Settings', 'smart-alt-text'),
                [$this, 'render_api_settings_section'],
                'smart-alt-text'
            );

            // Register API Key field
            add_settings_field(
                'smart_alt_text_api_key',
                __('X.AI API Key', 'smart-alt-text'),
                [$this, 'render_api_key_field'],
                'smart-alt-text',
                'smart_alt_text_api_settings'
            );

            // Register generation settings section
            add_settings_section(
                'smart_alt_text_generation_settings',
                __('Generation Settings', 'smart-alt-text'),
                [$this, 'render_generation_settings_section'],
                'smart-alt-text'
            );

            // Register generation fields
            add_settings_field(
                'smart_alt_text_auto_generate',
                __('Auto Generate', 'smart-alt-text'),
                [$this, 'render_auto_generate_field'],
                'smart-alt-text',
                'smart_alt_text_generation_settings'
            );

            add_settings_field(
                'smart_alt_text_prefix_suffix',
                __('Prefix/Suffix', 'smart-alt-text'),
                [$this, 'render_prefix_suffix_fields'],
                'smart-alt-text',
                'smart_alt_text_generation_settings'
            );

            add_settings_field(
                'smart_alt_text_update_fields',
                __('Update Fields', 'smart-alt-text'),
                [$this, 'render_update_fields'],
                'smart-alt-text',
                'smart_alt_text_generation_settings'
            );

            error_log('Smart Alt Text - Admin: [2] Settings registered successfully');

        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in register_settings: ' . $e->getMessage());
        }
    }

    /**
     * Render API settings section
     */
    public function render_api_settings_section($args) {
        echo '<p>' . esc_html__('Configure your X.AI API key to enable image analysis.', 'smart-alt-text') . '</p>';
    }

    /**
     * Render generation settings section
     */
    public function render_generation_settings_section($args) {
        echo '<p>' . esc_html__('Configure how alt text is generated and applied.', 'smart-alt-text') . '</p>';
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        try {
            $settings = get_option('smart_alt_text_settings', []);
            $api_key = $this->get_api_key(); // Get decrypted key
            
            error_log('Smart Alt Text - Admin: [DEBUG] render_api_key_field - Raw settings: ' . print_r($settings, true));
            error_log('Smart Alt Text - Admin: [DEBUG] render_api_key_field - Decrypted API key exists: ' . (!empty($api_key) ? 'Yes' : 'No'));
            ?>
            <input type="text" 
                   id="smart_alt_text_api_key" 
                   name="smart_alt_text_settings[api_key]" 
                   value="<?php echo esc_attr($api_key); ?>" 
                   class="regular-text">
            <p class="description">
                <?php _e('Enter your X.AI API key. Get one from', 'smart-alt-text'); ?>
                <a href="https://x.ai/api" target="_blank">X.AI Dashboard</a>
            </p>
            <?php
        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in render_api_key_field: ' . $e->getMessage());
        }
    }

    /**
     * Render auto-generate field
     */
    public function render_auto_generate_field() {
        $settings = get_option('smart_alt_text_settings', []);
        $auto_generate = isset($settings['auto_generate']) ? $settings['auto_generate'] : false;
        ?>
        <label>
            <input type="checkbox" 
                   name="smart_alt_text_settings[auto_generate]" 
                   value="1" 
                   <?php checked($auto_generate, true); ?>>
            <?php _e('Automatically generate alt text when images are uploaded', 'smart-alt-text'); ?>
        </label>
        <?php
    }

    /**
     * Render prefix/suffix fields
     */
    public function render_prefix_suffix_fields() {
        $settings = get_option('smart_alt_text_settings', []);
        $prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
        $suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
        ?>
        <p>
            <input type="text" 
                   name="smart_alt_text_settings[prefix]" 
                   value="<?php echo esc_attr($prefix); ?>" 
                   class="regular-text" 
                   placeholder="<?php esc_attr_e('Prefix text', 'smart-alt-text'); ?>">
            <span class="description"><?php _e('Text to add before the generated alt text', 'smart-alt-text'); ?></span>
        </p>
        <p>
            <input type="text" 
                   name="smart_alt_text_settings[suffix]" 
                   value="<?php echo esc_attr($suffix); ?>" 
                   class="regular-text" 
                   placeholder="<?php esc_attr_e('Suffix text', 'smart-alt-text'); ?>">
            <span class="description"><?php _e('Text to add after the generated alt text', 'smart-alt-text'); ?></span>
        </p>
        <?php
    }

    /**
     * Render update fields
     */
    public function render_update_fields() {
        $settings = get_option('smart_alt_text_settings', []);
        $update_title = isset($settings['update_title']) ? $settings['update_title'] : false;
        $update_caption = isset($settings['update_caption']) ? $settings['update_caption'] : false;
        $update_description = isset($settings['update_description']) ? $settings['update_description'] : false;
        ?>
        <fieldset>
            <label>
                <input type="checkbox" 
                       name="smart_alt_text_settings[update_title]" 
                       value="1" 
                       <?php checked($update_title, true); ?>>
                <?php _e('Update image title', 'smart-alt-text'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" 
                       name="smart_alt_text_settings[update_caption]" 
                       value="1" 
                       <?php checked($update_caption, true); ?>>
                <?php _e('Update image caption', 'smart-alt-text'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" 
                       name="smart_alt_text_settings[update_description]" 
                       value="1" 
                       <?php checked($update_description, true); ?>>
                <?php _e('Update image description', 'smart-alt-text'); ?>
            </label>
        </fieldset>
        <?php
    }

    /**
     * Encrypt API key before saving
     */
    public function encrypt_api_key($api_key) {
        try {
            if (empty($api_key)) {
                error_log('Smart Alt Text - Admin: [DEBUG] encrypt_api_key - Empty API key provided');
                return '';
            }

            error_log('Smart Alt Text - Admin: [DEBUG] encrypt_api_key - Starting encryption');

            // Get the encryption key
            $encryption_key = $this->get_encryption_key();
            if (empty($encryption_key)) {
                error_log('Smart Alt Text - Admin: [DEBUG] encrypt_api_key - No encryption key available');
                return '';
            }

            // Generate a random IV
            $iv = openssl_random_pseudo_bytes(16);
            
            // Encrypt the API key
            $encrypted = openssl_encrypt(
                $api_key,
                'AES-256-CBC',
                base64_decode($encryption_key),
                0,
                $iv
            );

            if ($encrypted === false) {
                error_log('Smart Alt Text - Admin: [DEBUG] encrypt_api_key - Encryption failed');
                return '';
            }

            // Combine IV and encrypted data
            $combined = base64_encode($iv . $encrypted);
            
            error_log('Smart Alt Text - Admin: [DEBUG] encrypt_api_key - Encryption successful');
            return $combined;

        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in encrypt_api_key: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Get encryption key
     */
    private function get_encryption_key() {
        try {
            // Try to get existing key
            $key = get_option('smart_alt_text_encryption_key');
            
            // If no key exists, create one and store it
            if (empty($key)) {
                $key = base64_encode(openssl_random_pseudo_bytes(32));
                update_option('smart_alt_text_encryption_key', $key);
                error_log('Smart Alt Text - Admin: [DEBUG] get_encryption_key - Generated new key');
            }
            
            error_log('Smart Alt Text - Admin: [DEBUG] get_encryption_key - Key exists: ' . (!empty($key) ? 'Yes' : 'No'));
            return $key;
        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in get_encryption_key: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get decrypted API key
     */
    public function get_api_key() {
        try {
            $settings = get_option('smart_alt_text_settings', []);
            $encrypted_key = isset($settings['api_key']) ? $settings['api_key'] : '';
            
            error_log('Smart Alt Text - Admin: [DEBUG] get_api_key - Settings: ' . print_r($settings, true));
            error_log('Smart Alt Text - Admin: [DEBUG] get_api_key - Encrypted key exists: ' . (!empty($encrypted_key) ? 'Yes' : 'No'));
            
            if (empty($encrypted_key)) {
                error_log('Smart Alt Text - Admin: [DEBUG] get_api_key - No encrypted key found');
                return '';
            }

            // Get the encryption key
            $encryption_key = $this->get_encryption_key();
            if (empty($encryption_key)) {
                error_log('Smart Alt Text - Admin: [DEBUG] get_api_key - No encryption key found');
                return '';
            }

            // Decode the combined string
            $decoded = base64_decode($encrypted_key);
            if ($decoded === false) {
                error_log('Smart Alt Text - Admin: [DEBUG] get_api_key - Failed to decode encrypted key');
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

            error_log('Smart Alt Text - Admin: [DEBUG] get_api_key - Decryption result: ' . ($decrypted !== false ? 'Success' : 'Failed'));

            return $decrypted !== false ? $decrypted : '';

        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in get_api_key: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_scripts($hook) {
        try {
            error_log('Smart Alt Text - Admin: [1] Hook: ' . $hook);

            // Check if we're on our plugin pages or media pages
            $is_plugin_page = (
                strpos($hook, 'smart-alt-text') !== false ||
                strpos($hook, 'upload.php') !== false ||
                strpos($hook, 'post.php') !== false ||
                strpos($hook, 'post-new.php') !== false ||
                strpos($hook, 'media-new.php') !== false
            );

            error_log('Smart Alt Text - Admin: [2] Is plugin page: ' . ($is_plugin_page ? 'yes' : 'no'));

            // Always load on media pages
            if (!$is_plugin_page) {
                error_log('Smart Alt Text - Admin: [3] Not a plugin page, skipping');
                return;
            }

            // Get plugin directory URL
            $plugin_dir_url = plugin_dir_url(dirname(__FILE__));
            error_log('Smart Alt Text - Admin: [4] Plugin URL: ' . $plugin_dir_url);

            // Get settings and API key
            $settings = get_option('smart_alt_text_settings', []);
            $api_key = $this->get_api_key();

            error_log('Smart Alt Text - Admin: [DEBUG] enqueue_scripts - Raw settings: ' . print_r($settings, true));
            error_log('Smart Alt Text - Admin: [DEBUG] enqueue_scripts - API key present: ' . (!empty($api_key) ? 'Yes' : 'No'));

            // Localize script data
            $script_data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('smart_alt_text_nonce'),
                'prefix' => isset($settings['prefix']) ? $settings['prefix'] : '',
                'suffix' => isset($settings['suffix']) ? $settings['suffix'] : '',
                'has_api_key' => !empty($api_key) ? true : false,
                'debug' => true,
                'hook' => $hook,
                'plugin_url' => $plugin_dir_url,
                'i18n' => [
                    'saving' => __('Saving...', 'smart-alt-text'),
                    'saved' => __('Saved!', 'smart-alt-text'),
                    'analyzing' => __('Analyzing...', 'smart-alt-text'),
                    'analyzed' => __('Done!', 'smart-alt-text'),
                    'error' => __('Error', 'smart-alt-text'),
                    'no_api_key' => __('Please configure your X.AI API key in the plugin settings', 'smart-alt-text')
                ]
            ];

            error_log('Smart Alt Text - Admin: [DEBUG] Script data being localized: ' . print_r($script_data, true));

            // Enqueue CSS
            wp_enqueue_style(
                'smart-alt-text-admin',
                $plugin_dir_url . 'assets/css/admin.css',
                [],
                SMART_ALT_TEXT_VERSION . '.' . time()
            );

            // Enqueue JavaScript
            wp_enqueue_script(
                'smart-alt-text-admin',
                $plugin_dir_url . 'assets/js/admin.js',
                ['jquery'],
                SMART_ALT_TEXT_VERSION . '.' . time(),
                true
            );

            // Localize the script
            wp_localize_script(
                'smart-alt-text-admin',
                'smart_alt_text_obj',
                $script_data
            );

            error_log('Smart Alt Text - Admin: [5] Scripts loaded and localized successfully');

        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in enqueue_scripts: ' . $e->getMessage());
        }
    }

    /**
     * Handle AJAX request to save alt text
     */
    public function handle_save_alt_text() {
        try {
            error_log('Smart Alt Text - Admin: [1] Handling save alt text request');
            error_log('Smart Alt Text - Admin: POST data: ' . print_r($_POST, true));

            // Verify nonce
            if (!check_ajax_referer('smart_alt_text_nonce', 'nonce', false)) {
                error_log('Smart Alt Text - Admin: [ERROR] Invalid nonce');
                wp_send_json_error(['message' => 'Invalid security token']);
                return;
            }

            // Check permissions
            if (!current_user_can('upload_files')) {
                error_log('Smart Alt Text - Admin: [ERROR] Insufficient permissions');
                wp_send_json_error(['message' => 'You do not have permission to perform this action']);
                return;
            }

            // Get and validate parameters
            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            $alt_text = isset($_POST['alt_text']) ? sanitize_text_field($_POST['alt_text']) : '';

            error_log('Smart Alt Text - Admin: [2] Saving alt text for image ' . $image_id . ': ' . $alt_text);

            if (!$image_id) {
                error_log('Smart Alt Text - Admin: [ERROR] Invalid image ID');
                wp_send_json_error(['message' => 'Invalid image ID']);
                return;
            }

            // Update the alt text
            $result = update_post_meta($image_id, '_wp_attachment_image_alt', wp_slash($alt_text));

            // update_post_meta returns false if value hasn't changed, which is not an error
            if ($result === false && get_post_meta($image_id, '_wp_attachment_image_alt', true) !== $alt_text) {
                error_log('Smart Alt Text - Admin: [ERROR] Failed to update alt text');
                wp_send_json_error(['message' => 'Failed to save alt text']);
                return;
            }

            // Force refresh attachment metadata
            clean_post_cache($image_id);

            // Also update other fields if enabled
            if (get_option('smart_alt_text_update_title')) {
                wp_update_post([
                    'ID' => $image_id,
                    'post_title' => $alt_text
                ]);
            }

            if (get_option('smart_alt_text_update_caption')) {
                wp_update_post([
                    'ID' => $image_id,
                    'post_excerpt' => $alt_text
                ]);
            }

            if (get_option('smart_alt_text_update_description')) {
                wp_update_post([
                    'ID' => $image_id,
                    'post_content' => $alt_text
                ]);
            }

            error_log('Smart Alt Text - Admin: [3] Alt text saved successfully');
            wp_send_json_success([
                'message' => 'Alt text saved successfully',
                'alt_text' => $alt_text
            ]);

        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in handle_save_alt_text: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error saving alt text: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle AJAX request to analyze image
     */
    public function handle_analyze_image() {
        try {
            error_log('Smart Alt Text - Admin: [1] Handling analyze image request');
            error_log('Smart Alt Text - Admin: POST data: ' . print_r($_POST, true));

            // Verify nonce
            if (!check_ajax_referer('smart_alt_text_nonce', 'nonce', false)) {
                error_log('Smart Alt Text - Admin: [ERROR] Invalid nonce');
                wp_send_json_error(['message' => 'Invalid security token']);
                return;
            }

            // Check permissions
            if (!current_user_can('upload_files')) {
                error_log('Smart Alt Text - Admin: [ERROR] Insufficient permissions');
                wp_send_json_error(['message' => 'You do not have permission to perform this action']);
                return;
            }

            // Get and validate parameters
            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';

            error_log('Smart Alt Text - Admin: [2] Analyzing image ' . $image_id . ' at URL: ' . $image_url);

            if (!$image_id || !$image_url) {
                error_log('Smart Alt Text - Admin: [ERROR] Invalid image data');
                wp_send_json_error(['message' => 'Invalid image data']);
                return;
            }

            // Check file extension
            $file_info = wp_check_filetype($image_url);
            $allowed_types = ['jpg', 'jpeg', 'png'];
            
            if (!in_array(strtolower($file_info['ext']), $allowed_types)) {
                error_log('Smart Alt Text - Admin: [ERROR] Invalid file type: ' . $file_info['ext']);
                wp_send_json_error([
                    'message' => sprintf(
                        'This image type (%s) is not supported. Please use JPEG or PNG images only.',
                        strtoupper($file_info['ext'])
                    )
                ]);
                return;
            }

            // Get API key
            $api_key = $this->get_api_key();
            if (empty($api_key)) {
                error_log('Smart Alt Text - Admin: [ERROR] No API key configured');
                wp_send_json_error(['message' => 'Please configure your X.AI API key in the plugin settings']);
                return;
            }

            // Initialize the image analyzer
            $analyzer = new ImageAnalyzer($api_key);

            // Analyze the image
            try {
                error_log('Smart Alt Text - Admin: [3] Starting image analysis');
                $alt_text = $analyzer->analyze_image($image_url);
                
                if (empty($alt_text)) {
                    error_log('Smart Alt Text - Admin: [ERROR] Failed to generate alt text');
                    wp_send_json_error(['message' => 'Failed to generate alt text']);
                    return;
                }

                error_log('Smart Alt Text - Admin: [4] Generated alt text: ' . $alt_text);

                // Apply prefix/suffix
                $settings = get_option('smart_alt_text_settings', []);
                $prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
                $suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
                
                if (!empty($prefix)) {
                    $alt_text = trim($prefix) . ' ' . $alt_text;
                }
                if (!empty($suffix)) {
                    $alt_text = $alt_text . ' ' . trim($suffix);
                }

                error_log('Smart Alt Text - Admin: [5] Final alt text with prefix/suffix: ' . $alt_text);

                // Update the alt text
                update_post_meta($image_id, '_wp_attachment_image_alt', wp_slash($alt_text));

                // Force refresh attachment metadata
                clean_post_cache($image_id);
                
                // Also update other fields if enabled
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

                error_log('Smart Alt Text - Admin: [6] Alt text saved successfully');
                wp_send_json_success([
                    'message' => 'Alt text generated and saved successfully',
                    'alt_text' => $alt_text
                ]);

            } catch (\Exception $e) {
                error_log('Smart Alt Text - Admin: [ERROR] Analysis failed: ' . $e->getMessage());
                
                // Check if error is about file type
                if (strpos($e->getMessage(), 'Unsupported content-type') !== false) {
                    wp_send_json_error([
                        'message' => 'This image type is not supported. Please use JPEG or PNG images only.'
                    ]);
                } else {
                    wp_send_json_error([
                        'message' => 'Failed to analyze image: ' . $e->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in handle_analyze_image: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error analyzing image: ' . $e->getMessage()]);
        }
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        require_once SMART_ALT_TEXT_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Render the bulk processing page.
     */
    public function render_bulk_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        require_once SMART_ALT_TEXT_PLUGIN_DIR . 'templates/bulk-page.php';
    }

    /**
     * Render the stats page.
     */
    public function render_stats_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        require_once SMART_ALT_TEXT_PLUGIN_DIR . 'templates/stats-page.php';
    }

    /**
     * Render the main settings section description.
     */
    public function render_section_main() {
        echo '<p>' . esc_html__('Configure your Smart Alt Text settings below.', 'smart-alt-text') . '</p>';
    }

    /**
     * Handle new image upload for auto-generation
     */
    public function handle_new_image($attachment_id) {
        try {
            error_log('Smart Alt Text - Admin: [1] Handling new image upload: ' . $attachment_id);

            // Get settings
            $settings = get_option('smart_alt_text_settings', []);
            
            // Check if auto-generation is enabled
            if (!isset($settings['auto_generate']) || !$settings['auto_generate']) {
                error_log('Smart Alt Text - Admin: [2] Auto-generation is disabled');
                return;
            }

            // Check if it's an image
            if (!wp_attachment_is_image($attachment_id)) {
                error_log('Smart Alt Text - Admin: [3] Not an image attachment');
                return;
            }

            // Get image URL
            $image_url = wp_get_attachment_url($attachment_id);
            if (!$image_url) {
                error_log('Smart Alt Text - Admin: [ERROR] Could not get image URL');
                return;
            }

            // Check file type
            $file_info = wp_check_filetype($image_url);
            $allowed_types = ['jpg', 'jpeg', 'png'];
            
            if (!in_array(strtolower($file_info['ext']), $allowed_types)) {
                error_log('Smart Alt Text - Admin: [ERROR] Unsupported file type: ' . $file_info['ext']);
                return;
            }

            // Get API key
            $api_key = $this->get_api_key();
            if (empty($api_key)) {
                error_log('Smart Alt Text - Admin: [ERROR] No API key configured');
                return;
            }

            error_log('Smart Alt Text - Admin: [4] Starting image analysis');

            // Initialize the image analyzer
            $analyzer = new ImageAnalyzer($api_key);

            // Get the alt text
            $alt_text = $analyzer->analyze_image($image_url);
            if (empty($alt_text)) {
                error_log('Smart Alt Text - Admin: [ERROR] Failed to generate alt text');
                return;
            }

            error_log('Smart Alt Text - Admin: [5] Generated alt text: ' . $alt_text);

            // Apply prefix/suffix
            $prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
            $suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
            
            if (!empty($prefix)) {
                $alt_text = trim($prefix) . ' ' . $alt_text;
            }
            if (!empty($suffix)) {
                $alt_text = $alt_text . ' ' . trim($suffix);
            }

            error_log('Smart Alt Text - Admin: [6] Final alt text with prefix/suffix: ' . $alt_text);

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

            error_log('Smart Alt Text - Admin: [7] Alt text and fields updated successfully');

        } catch (\Exception $e) {
            error_log('Smart Alt Text - Admin: [ERROR] Exception in handle_new_image: ' . $e->getMessage());
        }
    }

    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings($settings) {
        error_log('Smart Alt Text - Admin: [DEBUG] sanitize_settings - Starting sanitization');
        error_log('Smart Alt Text - Admin: [DEBUG] sanitize_settings - Input settings: ' . print_r($settings, true));
        
        $sanitized = [];
        
        // Handle API key
        if (isset($settings['api_key'])) {
            $api_key = trim($settings['api_key']);
            if (!empty($api_key)) {
                error_log('Smart Alt Text - Admin: [DEBUG] sanitize_settings - New API key provided');
                $encrypted = $this->encrypt_api_key($api_key);
                if (!empty($encrypted)) {
                    $sanitized['api_key'] = $encrypted;
                    error_log('Smart Alt Text - Admin: [DEBUG] sanitize_settings - API key encrypted successfully');
                } else {
                    error_log('Smart Alt Text - Admin: [DEBUG] sanitize_settings - API key encryption failed');
                }
            } else {
                // If empty, keep the existing encrypted key
                $existing = get_option('smart_alt_text_settings', []);
                $sanitized['api_key'] = isset($existing['api_key']) ? $existing['api_key'] : '';
                error_log('Smart Alt Text - Admin: [DEBUG] sanitize_settings - Using existing API key');
            }
        }
        
        // Handle other settings
        $sanitized['auto_generate'] = isset($settings['auto_generate']) ? (bool)$settings['auto_generate'] : false;
        $sanitized['prefix'] = isset($settings['prefix']) ? sanitize_text_field($settings['prefix']) : '';
        $sanitized['suffix'] = isset($settings['suffix']) ? sanitize_text_field($settings['suffix']) : '';
        $sanitized['update_title'] = isset($settings['update_title']) ? (bool)$settings['update_title'] : false;
        $sanitized['update_caption'] = isset($settings['update_caption']) ? (bool)$settings['update_caption'] : false;
        $sanitized['update_description'] = isset($settings['update_description']) ? (bool)$settings['update_description'] : false;
        
        error_log('Smart Alt Text - Admin: [DEBUG] sanitize_settings - Final settings structure: ' . print_r(array_keys($sanitized), true));
        error_log('Smart Alt Text - Admin: [DEBUG] sanitize_settings - API key in final settings: ' . (!empty($sanitized['api_key']) ? 'Yes' : 'No'));
        
        return $sanitized;
    }
} 