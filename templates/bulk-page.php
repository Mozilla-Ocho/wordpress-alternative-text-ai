<?php
if (!defined('WPINC')) {
    die;
}

// Get current page and filter
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';

// Build query args
$args = array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'posts_per_page' => $per_page,
    'paged' => $current_page,
    'post_status' => 'inherit',
    'orderby' => 'date',
    'order' => 'DESC'
);

// Add meta query for filters
if ($filter === 'with-alt') {
    $args['meta_query'] = array(
        array(
            'key' => '_wp_attachment_image_alt',
            'compare' => '!=',
            'value' => ''
        )
    );
} elseif ($filter === 'no-alt') {
    $args['meta_query'] = array(
        array(
            'key' => '_wp_attachment_image_alt',
            'compare' => 'NOT EXISTS'
        )
    );
}

// Get images and total count
$images_query = new WP_Query($args);
$images = $images_query->posts;
$total_images = $images_query->found_posts;
$total_pages = ceil($total_images / $per_page);

// Get counts for filters
$total_with_alt = wp_count_posts('attachment')->inherit;
$with_alt_query = new WP_Query(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'posts_per_page' => -1,
    'post_status' => 'inherit',
    'meta_query' => array(
        array(
            'key' => '_wp_attachment_image_alt',
            'compare' => '!=',
            'value' => ''
        )
    )
));
$count_with_alt = $with_alt_query->found_posts;
$count_no_alt = $total_with_alt - $count_with_alt;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (empty($images)) : ?>
        <p><?php _e('No images found.', 'smart-alt-text'); ?></p>
    <?php else : ?>
        <div class="smart-alt-text-table-container">
            <ul class="subsubsub">
                <li>
                    <a href="<?php echo esc_url(add_query_arg('filter', 'all')); ?>" 
                       class="<?php echo $filter === 'all' ? 'current' : ''; ?>">
                        <?php _e('All', 'smart-alt-text'); ?>
                        <span class="count">(<?php echo number_format_i18n($total_with_alt); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('filter', 'with-alt')); ?>" 
                       class="<?php echo $filter === 'with-alt' ? 'current' : ''; ?>">
                        <?php _e('With Alt Text', 'smart-alt-text'); ?>
                        <span class="count">(<?php echo number_format_i18n($count_with_alt); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('filter', 'no-alt')); ?>" 
                       class="<?php echo $filter === 'no-alt' ? 'current' : ''; ?>">
                        <?php _e('No Alt Text', 'smart-alt-text'); ?>
                        <span class="count">(<?php echo number_format_i18n($count_no_alt); ?>)</span>
                    </a>
                </li>
            </ul>

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'smart-alt-text'); ?></label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php _e('Bulk Actions', 'smart-alt-text'); ?></option>
                        <option value="analyze"><?php _e('Analyze Selected', 'smart-alt-text'); ?></option>
                    </select>
                    <button type="button" id="doaction" class="button action"><?php _e('Apply', 'smart-alt-text'); ?></button>
                </div>

                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(_n('%s item', '%s items', $total_images, 'smart-alt-text'), number_format_i18n($total_images)); ?>
                    </span>
                    <?php if ($total_pages > 1) : ?>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $current_page
                            ));
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                <br class="clear">
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'smart-alt-text'); ?></label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="column-image"><?php _e('Image', 'smart-alt-text'); ?></th>
                        <th class="column-alt-text"><?php _e('Alt Text', 'smart-alt-text'); ?></th>
                        <th class="column-usage"><?php _e('Used In', 'smart-alt-text'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'smart-alt-text'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $image) : 
                        $image_url = wp_get_attachment_url($image->ID);
                        $thumb_url = wp_get_attachment_image_src($image->ID, 'thumbnail')[0];
                        $current_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);

                        // Get posts where this image is used
                        $usage_query = new WP_Query(array(
                            'post_type' => array('post', 'page'),
                            'posts_per_page' => -1,
                            'meta_query' => array(
                                'relation' => 'OR',
                                array(
                                    'key' => '_thumbnail_id',
                                    'value' => $image->ID,
                                    'compare' => '='
                                )
                            ),
                            'suppress_filters' => true
                        ));

                        $used_in_posts = array();

                        // Check featured images
                        while ($usage_query->have_posts()) {
                            $usage_query->the_post();
                            $used_in_posts[get_the_ID()] = array(
                                'title' => get_the_title(),
                                'edit_link' => get_edit_post_link(),
                                'post_type' => get_post_type()
                            );
                        }
                        wp_reset_postdata();

                        // Check content for image URL or attachment ID
                        global $wpdb;
                        $filename = basename($image_url);

                        // Search in post content for various forms of the image URL
                        $content_results = $wpdb->get_results($wpdb->prepare("
                            SELECT ID, post_title, post_type 
                            FROM {$wpdb->posts} 
                            WHERE (post_content LIKE %s 
                            OR post_content LIKE %s 
                            OR post_content LIKE %s)
                            AND post_type IN ('post', 'page')
                            AND post_status = 'publish'
                        ", 
                            '%' . $wpdb->esc_like($image_url) . '%',
                            '%' . $wpdb->esc_like($filename) . '%',
                            '%wp:image {"id":' . $image->ID . '%'
                        ));

                        // Add content results to used_in_posts
                        foreach ($content_results as $post) {
                            if (!isset($used_in_posts[$post->ID])) {
                                $used_in_posts[$post->ID] = array(
                                    'title' => $post->post_title,
                                    'edit_link' => get_edit_post_link($post->ID),
                                    'post_type' => $post->post_type
                                );
                            }
                        }
                    ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="cb-select-<?php echo esc_attr($image->ID); ?>">
                                    <?php printf(__('Select image %s', 'smart-alt-text'), esc_html($image->post_title)); ?>
                                </label>
                                <input id="cb-select-<?php echo esc_attr($image->ID); ?>" 
                                       type="checkbox" 
                                       name="images[]" 
                                       value="<?php echo esc_attr($image->ID); ?>"
                                       data-image-url="<?php echo esc_url($image_url); ?>">
                            </th>
                            <td class="column-image">
                                <img src="<?php echo esc_url($thumb_url); ?>" 
                                     alt="<?php echo esc_attr($current_alt); ?>"
                                     style="max-width: 150px; height: auto;">
                            </td>
                            <td class="column-alt-text">
                                <input type="text" 
                                       class="smart-alt-text-input regular-text" 
                                       data-image-id="<?php echo esc_attr($image->ID); ?>"
                                       value="<?php echo esc_attr($current_alt); ?>"
                                       placeholder="<?php esc_attr_e('Enter alt text...', 'smart-alt-text'); ?>">
                                <div class="save-status"></div>
                            </td>
                            <td class="column-usage">
                                <?php if (!empty($used_in_posts)) : ?>
                                    <ul class="usage-list">
                                        <?php foreach ($used_in_posts as $post_data) : ?>
                                            <li>
                                                <a href="<?php echo esc_url($post_data['edit_link']); ?>" target="_blank">
                                                    <?php echo esc_html($post_data['title']); ?>
                                                </a>
                                                <span class="post-type">(<?php echo esc_html(get_post_type_object($post_data['post_type'])->labels->singular_name); ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <em><?php _e('Not used', 'smart-alt-text'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button type="button" 
                                        class="button analyze-button" 
                                        data-image-id="<?php echo esc_attr($image->ID); ?>"
                                        data-image-url="<?php echo esc_url($image_url); ?>">
                                    <?php _e('Analyze', 'smart-alt-text'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select All', 'smart-alt-text'); ?></label>
                            <input id="cb-select-all-2" type="checkbox">
                        </td>
                        <th class="column-image"><?php _e('Image', 'smart-alt-text'); ?></th>
                        <th class="column-alt-text"><?php _e('Alt Text', 'smart-alt-text'); ?></th>
                        <th class="column-usage"><?php _e('Used In', 'smart-alt-text'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'smart-alt-text'); ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Select bulk action', 'smart-alt-text'); ?></label>
                    <select name="action2" id="bulk-action-selector-bottom">
                        <option value="-1"><?php _e('Bulk Actions', 'smart-alt-text'); ?></option>
                        <option value="analyze"><?php _e('Analyze Selected', 'smart-alt-text'); ?></option>
                    </select>
                    <button type="button" id="doaction2" class="button action"><?php _e('Apply', 'smart-alt-text'); ?></button>
                </div>

                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(_n('%s item', '%s items', $total_images, 'smart-alt-text'), number_format_i18n($total_images)); ?>
                    </span>
                    <?php if ($total_pages > 1) : ?>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $current_page
                            ));
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="smart-alt-text-debug">
            <h3>Debug Information</h3>
            <pre>
Hook: <?php echo esc_html($_GET['page'] ?? 'unknown'); ?>
API Key: <?php echo !empty(get_option('smart_alt_text_api_key')) ? 'Present' : 'Missing'; ?>
jQuery: <script>document.write(typeof jQuery !== 'undefined' ? 'Loaded' : 'Not loaded');</script>
smart_alt_text_obj: <script>document.write(typeof smart_alt_text_obj !== 'undefined' ? 'Loaded' : 'Not loaded');</script>
            </pre>
        </div>
    <?php endif; ?>
</div>

<style>
.smart-alt-text-table-container {
    margin-top: 20px;
}

.smart-alt-text-table-container table {
    width: 100%;
    border-spacing: 0;
}

.column-cb {
    width: 30px;
}

.column-image {
    width: 150px;
}

.column-alt-text {
    width: 30%;
}

.column-usage {
    width: auto;
}

.column-actions {
    width: 100px;
    text-align: center;
}

.smart-alt-text-input {
    width: 100%;
}

.analyze-button {
    min-width: 80px;
}

.save-status {
    height: 2px;
    margin-top: 5px;
    transition: all 0.3s ease;
}

#smart-alt-text-debug {
    margin-top: 30px;
    padding: 15px;
    background: #f5f5f5;
    border: 1px solid #ccc;
}

.updating-message {
    opacity: 0.6;
    cursor: wait !important;
}

.updated {
    border-color: #46b450 !important;
}

.error {
    border-color: #dc3232 !important;
}

.tablenav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.bulkactions {
    display: flex;
    gap: 4px;
}

.subsubsub {
    margin: 8px 0;
    list-style: none;
    padding: 0;
}

.subsubsub li {
    display: inline-block;
    margin: 0;
    padding: 0;
    white-space: nowrap;
}

.usage-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.usage-list li {
    margin-bottom: 4px;
}

.usage-list .post-type {
    color: #666;
    font-size: 12px;
    margin-left: 4px;
}

.tablenav-pages .pagination-links {
    margin-left: 8px;
}

.tablenav-pages .pagination-links a,
.tablenav-pages .pagination-links span {
    padding: 3px 6px;
    margin: 0 2px;
    border: 1px solid #ddd;
    background: #f7f7f7;
    text-decoration: none;
    border-radius: 2px;
}

.tablenav-pages .pagination-links .current {
    background: #0073aa;
    border-color: #0073aa;
    color: #fff;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle select all checkboxes
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('input[name="images[]"]').prop('checked', isChecked);
        $('#cb-select-all-1, #cb-select-all-2').prop('checked', isChecked);
    });

    // Handle bulk action
    $('#doaction, #doaction2').on('click', function() {
        const action = $(this).prev('select').val();
        if (action === 'analyze') {
            const selectedImages = $('input[name="images[]"]:checked');
            if (selectedImages.length === 0) {
                alert('Please select at least one image to analyze.');
                return;
            }

            // Process images sequentially
            let processed = 0;
            const total = selectedImages.length;

            function processNext() {
                if (processed >= total) {
                    return;
                }

                const $checkbox = $(selectedImages[processed]);
                const imageId = $checkbox.val();
                const imageUrl = $checkbox.data('image-url');
                const $button = $(`button[data-image-id="${imageId}"]`);

                // Trigger the analyze button click
                $button.trigger('click');

                // Wait for the analysis to complete before processing the next image
                const checkInterval = setInterval(function() {
                    if (!$button.prop('disabled')) {
                        clearInterval(checkInterval);
                        processed++;
                        processNext();
                    }
                }, 500);
            }

            processNext();
        }
    });
});</script> 