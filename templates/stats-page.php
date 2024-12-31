<?php
if (!defined('WPINC')) {
    die;
}

// Get statistics
$args = array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'posts_per_page' => -1,
    'post_status' => 'inherit'
);

$images = get_posts($args);
$total_images = count($images);
$images_with_alt = 0;

foreach ($images as $image) {
    if (!empty(get_post_meta($image->ID, '_wp_attachment_image_alt', true))) {
        $images_with_alt++;
    }
}

$coverage_percentage = $total_images > 0 ? round(($images_with_alt / $total_images) * 100) : 0;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="smart-alt-text-stats">
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-circle-container">
                    <div class="stats-circle" data-percentage="<?php echo esc_attr($coverage_percentage); ?>">
                        <div class="stats-circle-inner">
                            <div class="stats-percentage"><?php echo esc_html($coverage_percentage); ?>%</div>
                            <div class="stats-label"><?php _e('Coverage', 'smart-alt-text'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-number"><?php echo esc_html($total_images); ?></div>
                <div class="stats-label"><?php _e('Total Images', 'smart-alt-text'); ?></div>
            </div>

            <div class="stats-card">
                <div class="stats-number"><?php echo esc_html($images_with_alt); ?></div>
                <div class="stats-label"><?php _e('Images with Alt Text', 'smart-alt-text'); ?></div>
            </div>

            <div class="stats-card">
                <div class="stats-number"><?php echo esc_html($total_images - $images_with_alt); ?></div>
                <div class="stats-label"><?php _e('Images without Alt Text', 'smart-alt-text'); ?></div>
            </div>
        </div>
    </div>
</div>

<style>
.smart-alt-text-stats {
    margin-top: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stats-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.stats-number {
    font-size: 36px;
    font-weight: bold;
    color: #2271b1;
    margin-bottom: 10px;
}

.stats-label {
    color: #50575e;
    font-size: 14px;
}

.stats-circle-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.stats-circle {
    position: relative;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: conic-gradient(
        #2271b1 <?php echo esc_attr($coverage_percentage); ?>%,
        #e5e5e5 <?php echo esc_attr($coverage_percentage); ?>% 100%
    );
}

.stats-circle-inner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 120px;
    height: 120px;
    background: white;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.stats-percentage {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Animate the progress circle on load
    $('.stats-circle').each(function() {
        const percentage = $(this).data('percentage');
        $(this).css('background', `conic-gradient(
            #2271b1 ${percentage}%,
            #e5e5e5 ${percentage}% 100%
        )`);
    });
});</script> 