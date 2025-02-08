<?php
if (!defined('WPINC')) {
    die;
}

// Security checks
if (!current_user_can('upload_files')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'solo-ai-website-creator-alt-text-generator'));
}

// Verify nonce for GET requests
if (!empty($_GET)) {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'solo_ai_alt_text_bulk_action')) {
        // Don't die, just reset to defaults
        $current_page = 1;
        $filter = 'all';
    } else {
        // Get and validate parameters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $filter = isset($_GET['filter']) ? sanitize_text_field(wp_unslash($_GET['filter'])) : 'all';
    }
} else {
    $current_page = 1;
    $filter = 'all';
}

$per_page = 20;

// Cache key for query results
$cache_key = 'solo_ai_alt_text_images_' . $current_page;
$images = wp_cache_get($cache_key);

if (false === $images) {
    // Build optimized query args
    $query_args = array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'post_status' => 'inherit',
        'posts_per_page' => $per_page,
        'paged' => $current_page,
        'orderby' => 'ID',
        'order' => 'DESC',
        'no_found_rows' => true,
        'fields' => 'ids',
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
        'cache_results' => true
    );

    // Add meta query with proper indexing
    add_filter('posts_clauses', function($clauses) {
        global $wpdb;
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS mt_alt ON ({$wpdb->posts}.ID = mt_alt.post_id AND mt_alt.meta_key = '_wp_attachment_image_alt')";
        $clauses['where'] .= " AND (mt_alt.meta_value IS NULL OR mt_alt.meta_value = '')";
        return $clauses;
    });

    // Get image IDs first
    $image_ids = get_posts($query_args);
    
    // Then get full post objects for these IDs
    $images = array_map('get_post', $image_ids);
    
    // Cache the results
    wp_cache_set($cache_key, $images, '', HOUR_IN_SECONDS);
}

// Get total count (cached separately)
$total_count_key = 'solo_ai_alt_text_total_images';
$total_images = wp_cache_get($total_count_key);

if (false === $total_images) {
    // Use optimized query with JOIN instead of meta_query
    add_filter('posts_clauses', function($clauses) {
        global $wpdb;
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS mt_alt ON ({$wpdb->posts}.ID = mt_alt.post_id AND mt_alt.meta_key = '_wp_attachment_image_alt')";
        $clauses['where'] .= " AND (mt_alt.meta_value IS NULL OR mt_alt.meta_value = '')";
        return $clauses;
    });

    $count_query = new WP_Query([
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false
    ]);
    
    remove_all_filters('posts_clauses');
    
    $total_images = $count_query->found_posts;
    wp_cache_set($total_count_key, $total_images, '', HOUR_IN_SECONDS);
}

$total_pages = ceil($total_images / $per_page);

// Get and validate parameters
$filter = isset($_GET['filter']) ? sanitize_text_field(wp_unslash($_GET['filter'])) : 'all';

// Verify nonce if form is submitted
if (!empty($_POST)) {
    $nonce = isset($_POST['solo_ai_alt_text_nonce']) ? sanitize_text_field(wp_unslash($_POST['solo_ai_alt_text_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'solo_ai_alt_text_bulk_action')) {
        wp_die(esc_html__('Security check failed.', 'solo-ai-website-creator-alt-text-generator'));
    }
}

// Get counts for filters (with caching)
$counts_cache_key = 'solo_ai_alt_text_counts';
$counts = wp_cache_get($counts_cache_key);

if (false === $counts) {
    // Get total attachments
    $total_with_alt = wp_count_posts('attachment')->inherit;
    
    // Use optimized query with JOIN for counting images with alt text
    add_filter('posts_clauses', function($clauses) {
        global $wpdb;
        $clauses['join'] .= " INNER JOIN {$wpdb->postmeta} AS mt_alt ON ({$wpdb->posts}.ID = mt_alt.post_id AND mt_alt.meta_key = '_wp_attachment_image_alt')";
        $clauses['where'] .= " AND mt_alt.meta_value != ''";
        return $clauses;
    });

    $with_alt_query = new WP_Query([
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false
    ]);
    
    remove_all_filters('posts_clauses');
    
    $count_with_alt = $with_alt_query->found_posts;
    $count_no_alt = $total_with_alt - $count_with_alt;
    
    $counts = [
        'total' => $total_with_alt,
        'with_alt' => $count_with_alt,
        'no_alt' => $count_no_alt
    ];
    
    wp_cache_set($counts_cache_key, $counts, '', HOUR_IN_SECONDS);
} else {
    $total_with_alt = $counts['total'];
    $count_with_alt = $counts['with_alt'];
    $count_no_alt = $counts['no_alt'];
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (empty($images)) : ?>
        <p><?php esc_html_e('No images found.', 'solo-ai-website-creator-alt-text-generator'); ?></p>
    <?php else : ?>
        <div class="solo-ai-alt-text-table-container">
            <ul class="subsubsub">
                <li>
                    <a href="<?php echo esc_url(add_query_arg('filter', 'all')); ?>" 
                       class="<?php echo $filter === 'all' ? 'current' : ''; ?>">
                        <?php esc_html_e('All', 'solo-ai-website-creator-alt-text-generator'); ?>
                        <span class="count">(<?php echo esc_html(number_format_i18n($total_with_alt)); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('filter', 'with-alt')); ?>" 
                       class="<?php echo $filter === 'with-alt' ? 'current' : ''; ?>">
                        <?php esc_html_e('With Alt Text', 'solo-ai-website-creator-alt-text-generator'); ?>
                        <span class="count">(<?php echo esc_html(number_format_i18n($count_with_alt)); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('filter', 'no-alt')); ?>" 
                       class="<?php echo $filter === 'no-alt' ? 'current' : ''; ?>">
                        <?php esc_html_e('No Alt Text', 'solo-ai-website-creator-alt-text-generator'); ?>
                        <span class="count">(<?php echo esc_html(number_format_i18n($count_no_alt)); ?>)</span>
                    </a>
                </li>
            </ul>

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <?php wp_nonce_field('solo_ai_alt_text_bulk_action', 'solo_ai_alt_text_nonce'); ?>
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'solo-ai-website-creator-alt-text-generator'); ?></label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php esc_html_e('Bulk Actions', 'solo-ai-website-creator-alt-text-generator'); ?></option>
                        <option value="analyze"><?php esc_html_e('Analyze Selected', 'solo-ai-website-creator-alt-text-generator'); ?></option>
                    </select>
                    <button type="button" id="doaction" class="button action"><?php esc_html_e('Apply', 'solo-ai-website-creator-alt-text-generator'); ?></button>
                </div>

                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php 
                        /* translators: %s: number of items displayed in the table */
                        echo esc_html(sprintf(_n('%s item', '%s items', $total_images, 'solo-ai-website-creator-alt-text-generator'), 
                            number_format_i18n($total_images)
                        )); 
                        ?>
                    </span>
                    <?php if ($total_pages > 1) : ?>
                        <span class="pagination-links">
                            <?php
                            echo wp_kses(paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => esc_html__('&laquo;', 'solo-ai-website-creator-alt-text-generator'),
                                'next_text' => esc_html__('&raquo;', 'solo-ai-website-creator-alt-text-generator'),
                                'total' => $total_pages,
                                'current' => $current_page
                            )), array(
                                'a' => array('class' => array(), 'href' => array()),
                                'span' => array('class' => array(), 'aria-current' => array())
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
                            <label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e('Select All', 'solo-ai-website-creator-alt-text-generator'); ?></label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="column-image"><?php esc_html_e('Image', 'solo-ai-website-creator-alt-text-generator'); ?></th>
                        <th class="column-alt-text"><?php esc_html_e('Alt Text', 'solo-ai-website-creator-alt-text-generator'); ?></th>
                        <th class="column-usage"><?php esc_html_e('Used In', 'solo-ai-website-creator-alt-text-generator'); ?></th>
                        <th class="column-actions"><?php esc_html_e('Actions', 'solo-ai-website-creator-alt-text-generator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $image) : 
                        $image_url = wp_get_attachment_url($image->ID);
                        $thumb_url = wp_get_attachment_image_src($image->ID, 'thumbnail')[0];
                        $current_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);

                        // Check if image is used as featured image
                        $used_in_posts = array();
                        $cache_key = 'solo_ai_alt_text_usage_' . $image->ID;
                        $used_in_posts = wp_cache_get($cache_key);

                        if (false === $used_in_posts) {
                            // Get all post types that support featured images
                            $post_types = get_post_types(['public' => true]);
                            $used_in_posts = [];
                            
                            // Use optimized query with JOIN for featured images
                            add_filter('posts_clauses', function($clauses) use ($image) {
                                global $wpdb;
                                $clauses['join'] .= $wpdb->prepare(
                                    " INNER JOIN {$wpdb->postmeta} AS thumb_meta ON ({$wpdb->posts}.ID = thumb_meta.post_id AND thumb_meta.meta_key = '_thumbnail_id' AND thumb_meta.meta_value = %d)",
                                    $image->ID
                                );
                                return $clauses;
                            });

                            $featured_query = new WP_Query([
                                'post_type' => $post_types,
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'fields' => 'all',
                                'no_found_rows' => true,
                                'update_post_meta_cache' => false,
                                'update_post_term_cache' => false
                            ]);
                            
                            remove_all_filters('posts_clauses');
                            
                            if ($featured_query->have_posts()) {
                                while ($featured_query->have_posts()) {
                                    $featured_query->the_post();
                                    $post_id = get_the_ID();
                                    $used_in_posts[$post_id] = [
                                        'title' => get_the_title(),
                                        'edit_link' => get_edit_post_link($post_id),
                                        'post_type' => get_post_type()
                                    ];
                                }
                                wp_reset_postdata();
                            }
                            
                            wp_cache_set($cache_key, $used_in_posts, '', HOUR_IN_SECONDS);
                        }
                    ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="cb-select-<?php echo esc_attr($image->ID); ?>">
                                    <?php 
                                    /* translators: %s: image title */
                                    echo esc_html(sprintf(__('Select image %s', 'solo-ai-website-creator-alt-text-generator'), $image->post_title)); 
                                    ?>
                                </label>
                                <input id="cb-select-<?php echo esc_attr($image->ID); ?>" 
                                       type="checkbox" 
                                       name="images[]" 
                                       value="<?php echo esc_attr($image->ID); ?>"
                                       data-image-url="<?php echo esc_url($image_url); ?>">
                            </th>
                            <td class="column-image">
                                <?php 
                                echo wp_get_attachment_image(
                                    $image->ID,
                                    'thumbnail',
                                    false,
                                    array(
                                        'class' => 'attachment-thumbnail',
                                        'alt' => get_post_meta($image->ID, '_wp_attachment_image_alt', true)
                                    )
                                );
                                ?>
                            </td>
                            <td class="column-alt-text">
                                <input type="text" 
                                       class="solo-ai-alt-text-input" 
                                       data-image-id="<?php echo esc_attr($image->ID); ?>"
                                       value="<?php echo esc_attr($current_alt); ?>"
                                       placeholder="<?php esc_attr_e('Enter alt text...', 'solo-ai-website-creator-alt-text-generator'); ?>">
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
                                    <em><?php esc_html_e('Not used', 'solo-ai-website-creator-alt-text-generator'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button type="button" 
                                        class="button analyze-button" 
                                        data-image-id="<?php echo esc_attr($image->ID); ?>"
                                        data-image-url="<?php echo esc_url($image_url); ?>">
                                    <?php esc_html_e('Analyze', 'solo-ai-website-creator-alt-text-generator'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-2"><?php esc_html_e('Select All', 'solo-ai-website-creator-alt-text-generator'); ?></label>
                            <input id="cb-select-all-2" type="checkbox">
                        </td>
                        <th class="column-image"><?php esc_html_e('Image', 'solo-ai-website-creator-alt-text-generator'); ?></th>
                        <th class="column-alt-text"><?php esc_html_e('Alt Text', 'solo-ai-website-creator-alt-text-generator'); ?></th>
                        <th class="column-usage"><?php esc_html_e('Used In', 'solo-ai-website-creator-alt-text-generator'); ?></th>
                        <th class="column-actions"><?php esc_html_e('Actions', 'solo-ai-website-creator-alt-text-generator'); ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'solo-ai-website-creator-alt-text-generator'); ?></label>
                    <select name="action2" id="bulk-action-selector-bottom">
                        <option value="-1"><?php esc_html_e('Bulk Actions', 'solo-ai-website-creator-alt-text-generator'); ?></option>
                        <option value="analyze"><?php esc_html_e('Analyze Selected', 'solo-ai-website-creator-alt-text-generator'); ?></option>
                    </select>
                    <button type="button" id="doaction2" class="button action"><?php esc_html_e('Apply', 'solo-ai-website-creator-alt-text-generator'); ?></button>
                </div>

                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php 
                        /* translators: %s: number of items displayed in the table */
                        echo esc_html(sprintf(_n('%s item', '%s items', $total_images, 'solo-ai-website-creator-alt-text-generator'), 
                            number_format_i18n($total_images)
                        )); 
                        ?>
                    </span>
                    <?php if ($total_pages > 1) : ?>
                        <span class="pagination-links">
                            <?php
                            echo wp_kses(paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => esc_html__('&laquo;', 'solo-ai-website-creator-alt-text-generator'),
                                'next_text' => esc_html__('&raquo;', 'solo-ai-website-creator-alt-text-generator'),
                                'total' => $total_pages,
                                'current' => $current_page
                            )), array(
                                'a' => array('class' => array(), 'href' => array()),
                                'span' => array('class' => array(), 'aria-current' => array())
                            ));
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="solo-ai-alt-text-debug">
            <h3>Debug Information</h3>
            <pre>
Hook: <?php echo esc_html(sanitize_text_field(wp_unslash($_GET['page'] ?? 'unknown'))); ?>
API Key: <?php echo !empty(get_option('solo_ai_alt_text_settings')) ? 'Present' : 'Missing'; ?>
jQuery: <script>document.write(typeof jQuery !== 'undefined' ? 'Loaded' : 'Not loaded');</script>
solo_ai_alt_text_obj: <script>document.write(typeof solo_ai_alt_text_obj !== 'undefined' ? 'Loaded' : 'Not loaded');</script>
            </pre>
        </div>
    <?php endif; ?>
</div>

<style>
.solo-ai-alt-text-table-container {
    margin-top: 20px;
}

.solo-ai-alt-text-table-container table {
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

.solo-ai-alt-text-input {
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

#solo-ai-alt-text-debug {
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
        $('input[name="images[]"]:checked').prop('checked', isChecked);
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