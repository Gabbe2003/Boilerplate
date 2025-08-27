<?php
/**
 * Plugin Name:     Pixabay Image Connector
 * Description:     Exposes Rank Math SEO fields via REST, swaps featured images via Pixabay or a fallback, and provides settings, manual sync, cron time, batch size, and daily logs under Tools → Pixabay Sync.
 * Version:         1.11.0
 * Author:          You
 */

defined('ABSPATH') || exit;

/* ------------------------------------------------------------------
 * 0. Initialize logs storage
 * ---------------------------------------------------------------- */
function ypseo_get_logs()
{
    return get_option('ypseo_logs', []);
}
function ypseo_add_log($date, $entries)
{
    $logs = ypseo_get_logs();
    $logs[$date] = $entries;
    update_option('ypseo_logs', $logs);
}

/**
 * Append helper (non-breaking; doesn't change existing behavior elsewhere)
 */
function ypseo_append_logs($date, $entries_to_add)
{
    $logs = ypseo_get_logs();
    $existing = isset($logs[$date]) && is_array($logs[$date]) ? $logs[$date] : [];
    $logs[$date] = array_merge($existing, $entries_to_add);
    update_option('ypseo_logs', $logs);
}

/* ------------------------------------------------------------------
 * 1. Expose Rank Math meta via REST
 * ---------------------------------------------------------------- */
add_action('init', function () {
    $meta = [
        'rank_math_title',
        'rank_math_description',
        'rank_math_focus_keyword',
        'rank_math_canonical_url',
        'rank_math_og_title',
        'rank_math_og_description',
        'rank_math_og_url',
        'rank_math_og_image',
    ];
    foreach ($meta as $m) {
        register_post_meta('post', $m, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }
});

/* ------------------------------------------------------------------
 * 2. Default settings
 * ---------------------------------------------------------------- */
function ypseo_defaults()
{
    return [
        'pixabay_key' => '51035765-dc055a26a149f644ef6904d34',
        'safesearch' => 'true',
        'cache_days' => 1,
        'fallback_url' => plugin_dir_url(__FILE__) . 'assets/fallback.jpg',
        'batch_size' => 10,
        'cron_recurrence' => 'daily',
        'cron_time' => '02:00',
    ];
}

/* ------------------------------------------------------------------
 * 3. Settings get/update
 * ---------------------------------------------------------------- */
function ypseo_get($key)
{
    $opt = get_option('ypseo_' . $key);
    return false === $opt ? ypseo_defaults()[$key] : $opt;
}
function ypseo_update($key, $val)
{
    update_option('ypseo_' . $key, $val);
}

/* ------------------------------------------------------------------
 * 4. Cron scheduling
 * ---------------------------------------------------------------- */
register_activation_hook(__FILE__, function () {
    $rec = ypseo_get('cron_recurrence');
    $time = ypseo_get('cron_time');
    list($h, $m) = explode(':', $time);
    $now = time();
    $dt = mktime($h, $m, 0, date('n', $now), date('j', $now), date('Y', $now));
    if ($dt < $now) {
        $dt += DAY_IN_SECONDS;
    }
    if (!wp_next_scheduled('ypseo_pixabay_cron')) {
        wp_schedule_event($dt, $rec, 'ypseo_pixabay_cron');
    }
});
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ypseo_pixabay_cron');
});
add_action('ypseo_pixabay_cron', 'ypseo_replace_featured_images_daily');

/* ------------------------------------------------------------------
 * 5. Helpers: date query & Pixabay API
 * ---------------------------------------------------------------- */
function ypseo_get_posts_between($from, $to)
{
    return get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'date_query' => [
            [
                'after' => "$from 00:00:00",
                'before' => "$to 23:59:59",
                'inclusive' => true,
            ]
        ],
    ]);
}
function ypseo_pixabay_query($kw)
{
    $url = add_query_arg([
        'key' => ypseo_get('pixabay_key'),
        'q' => rawurlencode($kw),
        'image_type' => 'photo',
        'per_page' => 5,
        'safesearch' => ypseo_get('safesearch'),
    ], 'https://pixabay.com/api/');
    $resp = wp_remote_get($url, ['timeout' => 15]);
    if (is_wp_error($resp)) {
        return ['error' => $resp->get_error_message()];
    }
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    return is_array($data) ? $data : ['error' => 'Invalid JSON from Pixabay.'];
}

/* ------------------------------------------------------------------
 * 6. Image processing (sideload & metadata)
 * ---------------------------------------------------------------- */
function ypseo_set_post_thumbnail_from_url($post, $url, $kw, $old_id = 0)
{
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $fallback = ypseo_get('fallback_url');
    $is_pix = ($url !== $fallback);
    $legacy = ['title' => '', 'caption' => '', 'description' => '', 'alt' => ''];

    if ($old_id) {
        $old = get_post($old_id);
        if ($old && 'attachment' === $old->post_type) {
            $legacy['title'] = $old->post_title;
            $legacy['caption'] = $old->post_excerpt;
            $legacy['description'] = $old->post_content;
            $legacy['alt'] = get_post_meta($old_id, '_wp_attachment_image_alt', true);
        }
    }

    if (!$is_pix) {
        foreach ($legacy as &$v) {
            if (!$v) {
                $v = $post->post_title;
            }
        }
        unset($v);
    }

    if ($is_pix) {
        $legacy['caption'] .= ($legacy['caption'] ? ' ' : '') . 'photo:Pixabay';
    }

    $tmp = download_url($url);
    if (is_wp_error($tmp)) {
        return false;
    }

    $file = [
        'name' => sanitize_file_name(wp_basename(parse_url($url, PHP_URL_PATH))),
        'tmp_name' => $tmp,
    ];

    $attach_id = media_handle_sideload($file, $post->ID, '', [
        'post_title' => sanitize_text_field($legacy['title']),
        'post_excerpt' => sanitize_text_field($legacy['caption']),
        'post_content' => sanitize_text_field($legacy['description']),
    ]);
    if (is_wp_error($attach_id)) {
        @unlink($tmp);
        return false;
    }

    update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($legacy['alt'] ?: $kw));

    if ($old_id) {
        wp_delete_attachment($old_id, true);
    }

    set_post_thumbnail($post->ID, $attach_id);
    return true;
}

/**
 * NOTE: Added optional $hard_limit to respect 100/hr ceiling when batching.
 */
function ypseo_process_posts($posts, &$log, $include_existing = false, $hard_limit = null)
{
    $ttl = DAY_IN_SECONDS * ypseo_get('cache_days');
    $batch = ypseo_get('batch_size');
    $limit = $hard_limit ? intval($hard_limit) : intval($batch);
    $limit = $limit > 0 ? $limit : 1;
    $posts = array_slice($posts, 0, $limit);

    foreach ($posts as $post) {
        $e = ['post_id' => $post->ID, 'title' => $post->post_title, 'status' => '', 'url' => '', 'error' => ''];

        if (!$include_existing && has_post_thumbnail($post->ID)) {
            $e['status'] = 'Skipped';
            $log[] = $e;
            continue;
        }

        $raw = trim(get_post_meta($post->ID, 'rank_math_focus_keyword', true));
        $kw = $raw ? strtolower(preg_split('/\s+/', $raw)[0]) : '';

        $img = '';
        $is_pix = false;

        if ($kw) {
            $ck = 'ypseo_pixabay_' . md5($kw);
            $pd = get_transient($ck);
            if (!$pd) {
                $pd = ypseo_pixabay_query($kw);
                set_transient($ck, $pd, $ttl);
            }
            if (!empty($pd['hits'][0]['largeImageURL'])) {
                $img = $pd['hits'][0]['largeImageURL'];
                $is_pix = true;
            }
        }

        if (!$img) {
            $img = ypseo_get('fallback_url');
        }

        $ok = ypseo_set_post_thumbnail_from_url($post, $img, $kw ?: $post->post_title, get_post_thumbnail_id($post->ID));

        $e['status'] = $ok ? ($is_pix ? 'Pixabay' : 'Fallback') : 'Error';
        $e['url'] = $ok ? $img : '';
        $e['error'] = $ok ? '' : 'Failed';
        $log[] = $e;
    }
}

/* ------------------------------------------------------------------
 * 6.1 Batched scheduling (NEW): split >100 into hourly chunks
 * ---------------------------------------------------------------- */
add_action('ypseo_pixabay_batch', 'ypseo_run_scheduled_batch', 10, 2);

/**
 * Schedule hourly batches of up to 100 posts per hour until done.
 * First batch runs in +1h, second in +2h, etc.
 *
 * @param WP_Post[] $posts
 * @param bool      $include_existing
 */
function ypseo_schedule_batched_processing($posts, $include_existing = false)
{
    if (empty($posts)) {
        return;
    }

    // Turn into IDs to keep args small
    $ids = array_map(fn($p) => is_object($p) ? intval($p->ID) : intval($p), $posts);

    // Chunk to 100 IDs per hour (API ceiling)
    $chunks = array_chunk($ids, 100);
    $i = 0;
    foreach ($chunks as $chunk) {
        $offset_seconds = ($i + 1) * HOUR_IN_SECONDS; // 1h for first, 2h for second, etc.
        wp_schedule_single_event(
            time() + $offset_seconds,
            'ypseo_pixabay_batch',
            [$chunk, (bool) $include_existing]
        );
        $i++;
    }
}

/**
 * Handler for a single scheduled batch.
 *
 * @param int[] $ids
 * @param bool  $include_existing
 */
function ypseo_run_scheduled_batch($ids, $include_existing = false)
{
    if (empty($ids) || !is_array($ids)) {
        return;
    }
    $posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'post__in' => array_map('intval', $ids),
        'orderby' => 'post__in',
    ]);

    $logs = [];
    // Hard limit to 100 for scheduled runs to respect the hourly cap.
    ypseo_process_posts($posts, $logs, $include_existing, 100);

    // Append logs to today's date so multiple batches don't overwrite each other.
    $date = gmdate('Y-m-d');
    ypseo_append_logs($date, $logs);
}

/* ------------------------------------------------------------------
 * 7. Cron handler (and log)
 * --------------------------------------------------------------   */
function ypseo_replace_featured_images_daily()
{
    $date = gmdate('Y-m-d');
    $posts = ypseo_get_posts_between($date, $date);

    // If over 100, schedule batches hourly, else run immediately as before.
    if (count($posts) > 100) {
        ypseo_schedule_batched_processing($posts, false);
        // Mark the day as scheduled/completed to keep daily lock behavior consistent
        set_transient('ypseo_completed_' . $date, 1, DAY_IN_SECONDS);
        // Optional status entry
        ypseo_append_logs($date, [
            ['post_id' => 0, 'title' => 'Cron', 'status' => 'Scheduled batched processing', 'url' => '', 'error' => ''],
        ]);
        return;
    }

    $logs = [];
    ypseo_process_posts($posts, $logs);
    set_transient('ypseo_completed_' . $date, 1, DAY_IN_SECONDS);
    ypseo_add_log($date, $logs);
}

/* ------------------------------------------------------------------
 * 8. Admin page under Tools → Pixabay Sync
 * ---------------------------------------------------------------- */
add_action( 'admin_menu', function() {
    // Top-level admin menu item (left sidebar)
    add_menu_page(
        'Pixabay Sync',           // Page title
        'Pixabay Sync',           // Menu title
        'manage_options',         // Capability
        'ypseo-sync',             // Menu slug
        'ypseo_admin_page',       // Callback
        'dashicons-images-alt2',  // Icon (dashicon)
        65                        // Position (after "Tools", before "Settings" typically)
    );
} );


function ypseo_admin_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    // Handle saving settings
    $settings_saved = false;
    $actual_key = ypseo_get('pixabay_key');
    $masked_key = substr($actual_key, 0, 4) . str_repeat('*', max(0, strlen($actual_key) - 8)) . substr($actual_key, -4);

    if (isset($_POST['settings_submit']) && check_admin_referer('ypseo_settings')) {
        $input_key = sanitize_text_field($_POST['pixabay_key']);
        if ($input_key !== $masked_key) {
            ypseo_update('pixabay_key', $input_key);
        }
        ypseo_update('safesearch', sanitize_text_field($_POST['safesearch']));
        ypseo_update('cache_days', absint($_POST['cache_days']));
        ypseo_update('fallback_url', esc_url_raw($_POST['fallback_url']));
        ypseo_update('batch_size', absint($_POST['batch_size']));

        $recurrences = ['hourly', 'twicedaily', 'daily'];
        $rec_input = sanitize_text_field($_POST['cron_recurrence']);
        if (in_array($rec_input, $recurrences, true)) {
            ypseo_update('cron_recurrence', $rec_input);
            wp_clear_scheduled_hook('ypseo_pixabay_cron');
            $time = ypseo_get('cron_time');
            list($hh, $mm) = explode(':', $time);
            $ts = mktime($hh, $mm, 0, date('n'), date('j'), date('Y'));
            if ($ts < time())
                $ts += DAY_IN_SECONDS;
            wp_schedule_event($ts, $rec_input, 'ypseo_pixabay_cron');
        }

        if (isset($_POST['cron_time'])) {
            ypseo_update('cron_time', sanitize_text_field($_POST['cron_time']));
        }

        $settings_saved = true;
    }

    // Handle manual sync
    $sync_run = false;
    $log_entries = [];
    $scheduled_batches = 0;
    $scheduled_total = 0;

    if (isset($_POST['sync_submit']) && check_admin_referer('ypseo_sync')) {
        if (!empty($_POST['ignore_lock'])) {
            delete_transient('ypseo_completed_' . gmdate('Y-m-d'));
        }
        $include_existing = !empty($_POST['include_existing']);
        $from_date = sanitize_text_field($_POST['from']);
        $to_date = sanitize_text_field($_POST['to']);
        $posts = ypseo_get_posts_between($from_date, $to_date);

        if (count($posts) > 100) {
            $ids = array_map(fn($p) => $p->ID, $posts);
            $chunks = array_chunk($ids, 100);
            $scheduled_batches = count($chunks);
            $scheduled_total = count($ids);
            // Schedule batches starting +1h
            ypseo_schedule_batched_processing($posts, $include_existing);
            // Log an admin-run scheduling note (so you have a record immediately)
            ypseo_append_logs(gmdate('Y-m-d'), [
                ['post_id' => 0, 'title' => 'Manual', 'status' => "Scheduled {$scheduled_batches} batch(es), {$scheduled_total} post(s)", 'url' => '', 'error' => ''],
            ]);
            $sync_run = true;
        } else {
            ypseo_process_posts($posts, $log_entries, $include_existing);
            $sync_run = true;
        }
    }

    $daily_logs = ypseo_get_logs();

    ?>
    <div class="wrap">
        <h1>Pixabay Image Sync</h1>

        <h2>Settings</h2>
        <?php if ($settings_saved): ?>
            <div class="notice notice-success is-dismissible">
                <p>Settings saved.</p>
            </div>
        <?php endif; ?>

        <form method="post"><?php wp_nonce_field('ypseo_settings'); ?>
            <table class="form-table">
                <tr>
                    <th>Pixabay API Key</th>
                    <td><input type="text" name="pixabay_key" value="<?php echo esc_attr($masked_key); ?>"
                            class="regular-text" />
                        <p class="description">Showing first/last 4 characters</p>
                    </td>
                </tr>
                <tr>
                    <th>Safe Search</th>
                    <td><select name="safesearch">
                            <option value="true" <?php selected(ypseo_get('safesearch'), 'true'); ?>>Enabled</option>
                            <option value="false" <?php selected(ypseo_get('safesearch'), 'false'); ?>>Disabled</option>
                        </select></td>
                </tr>
                <tr>
                    <th>Cache Duration (days)</th>
                    <td><input type="number" name="cache_days" value="<?php echo esc_attr(ypseo_get('cache_days')); ?>"
                            min="0" class="small-text" /></td>
                </tr>
                <tr>
                    <th>Fallback Image URL</th>
                    <td><input type="url" name="fallback_url" value="<?php echo esc_url(ypseo_get('fallback_url')); ?>"
                            class="regular-text" /></td>
                </tr>
                <tr>
                    <th>Batch Size</th>
                    <td><input type="number" name="batch_size" value="<?php echo esc_attr(ypseo_get('batch_size')); ?>"
                            min="1" class="small-text" /></td>
                </tr>
                <tr>
                    <th>Cron Recurrence</th>
                    <td><select name="cron_recurrence">
                            <?php foreach (['hourly' => 'Hourly', 'twicedaily' => 'Twice Daily', 'daily' => 'Daily'] as $val => $label): ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected(ypseo_get('cron_recurrence'), $val); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select></td>
                </tr>
                <tr>
                    <th>Cron Time</th>
                    <td><input type="time" name="cron_time" value="<?php echo esc_attr(ypseo_get('cron_time')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'settings_submit'); ?>
        </form>

        <h2>Manual Sync</h2>
        <?php if ($sync_run && $scheduled_batches > 0): ?>
            <div class="notice notice-info is-dismissible">
                <p>Scheduled <?php echo esc_html($scheduled_batches); ?> hourly batch(es) for
                    <?php echo esc_html($scheduled_total); ?> post(s). First batch will run in ~1 hour.</p>
            </div>
        <?php elseif ($sync_run): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo count($log_entries); ?> post(s) processed.</p>
            </div>
        <?php endif; ?>

        <form method="post"><?php wp_nonce_field('ypseo_sync'); ?>
            <table class="form-table">
                <tr>
                    <th>From</th>
                    <td><input type="date" name="from"
                            value="<?php echo esc_attr($_POST['from'] ?? gmdate('Y-m-d', strtotime('-30 days'))); ?>" />
                    </td>
                </tr>
                <tr>
                    <th>To</th>
                    <td><input type="date" name="to"
                            value="<?php echo esc_attr($_POST['to'] ?? gmdate('Y-m-d', strtotime('-1 day'))); ?>" /></td>
                </tr>
                <tr>
                    <th>Ignore daily lock</th>
                    <td><label><input type="checkbox" name="ignore_lock" /> Yes</label></td>
                </tr>
                <tr>
                    <th>Include existing thumbnails</th>
                    <td><label><input type="checkbox" name="include_existing" /> Yes</label></td>
                </tr>
            </table>
            <?php submit_button('Run Sync', 'primary', 'sync_submit'); ?>
        </form>

        <?php if ($sync_run && empty($scheduled_batches)): ?>
            <h2>Run Log</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>URL/Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($log_entries as $entry): ?>
                        <tr>
                            <td><?php echo esc_html($entry['post_id']); ?></td>
                            <td><?php echo esc_html($entry['title']); ?></td>
                            <td><?php echo esc_html($entry['status']); ?></td>
                            <td><?php echo esc_html($entry['url'] ?: $entry['error']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Daily Logs</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Entries</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daily_logs as $date => $entries):
                    $view_url = add_query_arg(['view_date' => $date], menu_page_url('ypseo-sync', false));
                    $delete_url = wp_nonce_url(add_query_arg(['ypseo_action' => 'delete_logs', 'date' => $date]), 'ypseo_delete_logs');
                    ?>
                    <tr>
                        <td><?php echo esc_html($date); ?></td>
                        <td><?php echo esc_html(count($entries)); ?></td>
                        <td>
                            <a href="<?php echo esc_url($view_url); ?>">View</a> |
                            <a href="<?php echo esc_url($delete_url); ?>"
                                onclick="return confirm('Delete all logs for <?php echo esc_js($date); ?>?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (isset($_GET['view_date']) && isset($daily_logs[$_GET['view_date']])):
            $vd = sanitize_text_field($_GET['view_date']);
            echo '<h2>Log Details for ' . esc_html($vd) . '</h2>';
            echo '<a href="' . esc_url(menu_page_url('ypseo-sync', false)) . '">&larr; Back to logs</a>';
            echo '<table class="widefat striped"><thead><tr>' .
                '<th>Time</th><th>Post ID</th><th>Title</th><th>Keyword</th><th>Attachment ID</th><th>Status</th><th>URL/Error</th>' .
                '</tr></thead><tbody>';
            foreach ($daily_logs[$vd] as $entry) {
                echo '<tr>' .
                    '<td>' . esc_html($entry['timestamp'] ?? '') . '</td>' .
                    '<td>' . esc_html($entry['post_id'] ?? '') . '</td>' .
                    '<td>' . esc_html($entry['title'] ?? '') . '</td>' .
                    '<td>' . esc_html($entry['keyword'] ?? '') . '</td>' .
                    '<td>' . esc_html($entry['attachment_id'] ?? '') . '</td>' .
                    '<td>' . esc_html($entry['status'] ?? '') . '</td>' .
                    '<td>' . esc_html(($entry['url'] ?? '') ? $entry['url'] : ($entry['error'] ?? '')) . '</td>' .
                    '</tr>';
            }
            echo '</tbody></table>';
        endif;

        ?>
    </div>
    <?php
}
