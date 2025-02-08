<?php
if (!defined('WPINC')) {
    die;
}

// Get current settings
$settings = get_option('solo_ai_website_creator_alt_text_settings', []);
$api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
$prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
$suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
$auto_generate = isset($settings['auto_generate']) ? $settings['auto_generate'] : false;
$update_title = isset($settings['update_title']) ? $settings['update_title'] : false;
$update_caption = isset($settings['update_caption']) ? $settings['update_caption'] : false;
$update_description = isset($settings['update_description']) ? $settings['update_description'] : false;
?>

<div class="wrap">
    <h1><?php echo esc_html__('Solo AI Alt Text Settings', 'solo-ai-website-creator-alt-text-generator'); ?></h1>
    <p><?php echo esc_html__('Configure your settings for the Solo AI Alt Text plugin below.', 'solo-ai-website-creator-alt-text-generator'); ?></p>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php
            settings_fields('solo_ai_website_creator_alt_text_options');
            do_settings_sections('solo_ai_website_creator_alt_text_options');
            submit_button();
        ?>
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