<?php
if (!defined('WPINC')) {
    die;
}

// Get current settings
$settings = get_option('smart_alt_text_settings', []);
$api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
$prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
$suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
$auto_generate = isset($settings['auto_generate']) ? $settings['auto_generate'] : false;
$update_title = isset($settings['update_title']) ? $settings['update_title'] : false;
$update_caption = isset($settings['update_caption']) ? $settings['update_caption'] : false;
$update_description = isset($settings['update_description']) ? $settings['update_description'] : false;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('smart_alt_text_settings');
        ?>

        <div class="card">
            <h2><?php _e('API Settings', 'smart-alt-text'); ?></h2>
            <p><?php _e('Configure your X.AI API key to enable image analysis.', 'smart-alt-text'); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('X.AI API Key', 'smart-alt-text'); ?></th>
                    <td>
                        <input type="text" 
                               name="smart_alt_text_settings[api_key]" 
                               value="<?php echo esc_attr($api_key); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php _e('Enter your X.AI API key. Get one from', 'smart-alt-text'); ?>
                            <a href="https://x.ai/api" target="_blank">X.AI Dashboard</a>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2><?php _e('Generation Settings', 'smart-alt-text'); ?></h2>
            <p><?php _e('Configure how alt text is generated and applied.', 'smart-alt-text'); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Auto Generation', 'smart-alt-text'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="smart_alt_text_settings[auto_generate]" 
                                   value="1" 
                                   <?php checked($auto_generate, true); ?>>
                            <?php _e('Automatically generate alt text when images are uploaded', 'smart-alt-text'); ?>
                        </label>
                        <p class="description">
                            <?php _e('You can always generate alt text manually using the Bulk Generate page or Analyze button.', 'smart-alt-text'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Update Fields', 'smart-alt-text'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" 
                                       name="smart_alt_text_settings[update_title]" 
                                       value="1" 
                                       <?php checked($update_title, true); ?>>
                                <?php _e('Also set the image title', 'smart-alt-text'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" 
                                       name="smart_alt_text_settings[update_caption]" 
                                       value="1" 
                                       <?php checked($update_caption, true); ?>>
                                <?php _e('Also set the image caption', 'smart-alt-text'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" 
                                       name="smart_alt_text_settings[update_description]" 
                                       value="1" 
                                       <?php checked($update_description, true); ?>>
                                <?php _e('Also set the image description', 'smart-alt-text'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Text Modifications', 'smart-alt-text'); ?></th>
                    <td>
                        <input type="text" 
                               name="smart_alt_text_settings[prefix]" 
                               value="<?php echo esc_attr($prefix); ?>" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e('Prefix text', 'smart-alt-text'); ?>">
                        <p class="description">
                            <?php _e('Add this text to the beginning of generated alt text', 'smart-alt-text'); ?>
                        </p>
                        <br>
                        <input type="text" 
                               name="smart_alt_text_settings[suffix]" 
                               value="<?php echo esc_attr($suffix); ?>" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e('Suffix text', 'smart-alt-text'); ?>">
                        <p class="description">
                            <?php _e('Add this text to the end of generated alt text', 'smart-alt-text'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-table th {
    width: 200px;
}

.form-table td fieldset label {
    margin: 0.25em 0 0.5em !important;
    display: block;
}

.form-table input[type="text"] {
    width: 100%;
    max-width: 400px;
}

.description {
    margin-top: 5px;
    color: #666;
}
</style> 