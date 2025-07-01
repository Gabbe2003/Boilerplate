<?php

add_action('rest_api_init', function () {
    register_rest_route('hpv/v1', '/log-view/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'hpv_rest_log_post_view',
        'permission_callback' => '__return_true',
    ]);
});

if (!function_exists('hpv_rest_log_post_view')) {
    function hpv_rest_log_post_view($request) {
        global $wpdb;

        $post_id = (int) $request['id'];

        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return new WP_Error('invalid_post', 'Post not found', ['status' => 404]);
        }

        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        $transient_key = 'hpv_view_' . md5($ip . '_' . $post_id);

        if (get_transient($transient_key)) {
            return new WP_REST_Response(['message' => 'Already logged recently'], 200);
        }

        // Prevents duplicate views from the same IP/post combo for 6 hours
        set_transient($transient_key, true, 3 * HOUR_IN_SECONDS);

        $views = (int) get_post_meta($post_id, 'post_views', true);
        update_post_meta($post_id, 'post_views', $views + 1);

        $table = $wpdb->prefix . 'post_view_logs';
        $wpdb->insert($table, [
            'post_id'   => $post_id,
            'view_date' => current_time('mysql'),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'post_id' => $post_id,
            'views'   => $views + 1,
        ], 200);
    }
}
