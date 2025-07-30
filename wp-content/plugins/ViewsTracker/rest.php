<?php

// === Log View Endpoint ===
add_action('rest_api_init', function () {
    register_rest_route('hpv/v1', '/log-view/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'hpv_rest_log_post_view',
        'permission_callback' => '__return_true',
    ]);
});

if (!function_exists('hpv_rest_log_post_view')) {
    function hpv_rest_log_post_view($request)
    {
        global $wpdb;

        $post_id = (int) $request['id'];
        $post = get_post($post_id);

        if (!$post || $post->post_status !== 'publish') {
            return new WP_Error('invalid_post', 'Post not found', ['status' => 404]);
        }

        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        $transient_key = 'hpv_view_' . md5($ip . '_' . $post_id);

        if (get_transient($transient_key)) {
            return new WP_REST_Response(['message' => 'Already logged recently'], 400);
        }

        // Save view
        set_transient($transient_key, true, 3 * HOUR_IN_SECONDS);
        $views = (int) get_post_meta($post_id, 'post_views', true);
        update_post_meta($post_id, 'post_views', $views + 1);

        // Log view in custom table
        $table = $wpdb->prefix . 'post_view_logs';
        $wpdb->insert($table, [
            'post_id' => $post_id,
            'view_date' => current_time('mysql'),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'post_id' => $post_id,
        ], 200);
    }
}

// === Top Posts Endpoint ===
add_action('rest_api_init', function () {
    register_rest_route('hpv/v1', '/top-posts', [
        'methods' => 'GET',
        'callback' => 'hpv_rest_get_top_posts',
        'permission_callback' => '__return_true',
    ]);
});

function hpv_rest_get_top_posts($request)
{
    $period = $request->get_param('period') ?: 'day';

    $limits = [
        'day' => 6,
        'week' => 12,
        'month' => 24,
    ];

    if (!isset($limits[$period])) {
        return new WP_Error('invalid_period', 'Invalid period parameter', ['status' => 400]);
    }

    $posts = hpv_get_top_viewed_posts($period, $limits[$period]);

    return rest_ensure_response(array_map(function ($post) {
        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'slug' => $post->post_name,
            'link' => get_permalink($post),
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium'),
            'date' => get_the_date('c', $post),
            'author_name' => get_the_author_meta('display_name', $post->post_author),
            'categories' => wp_get_post_terms($post->ID, 'category', ['fields' => 'names']),
            'excerpt' => get_the_excerpt($post),

        ];
    }, $posts));
}

// === Customize Default /wp/v2/posts Output ===
add_filter('rest_prepare_post', 'hpv_customize_rest_post_response', 10, 3);

function hpv_customize_rest_post_response($response, $post, $request)
{
    if ($request->get_route() !== '/wp/v2/posts' && strpos($request->get_route(), '/wp/v2/posts?') !== 0) {
        return $response;
    }

    $custom_data = [
        'title' => get_the_title($post),
        'slug' => $post->post_name,
        'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium'),
        'date' => get_the_date('c', $post),
        'author_name' => get_the_author_meta('display_name', $post->post_author),
        'categories' => wp_get_post_terms($post->ID, 'category', ['fields' => 'names']),
        'excerpt' => get_the_excerpt($post), // <-- added!
    ];

    return rest_ensure_response($custom_data);
}
