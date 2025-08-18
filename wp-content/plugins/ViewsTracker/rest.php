<?php
/**
 * Plugin Name: HPV Post Views API
 * Description: REST endpoints for logging post views, fetching top posts, customizing /wp/v2/posts, and fetching today's posts.
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================
 * === Log View Endpoint ======================================
 * ============================================================
 */
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

        // Save view (debounced for 3 hours per IP/post)
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

/**
 * ============================================================
 * === Top Posts Endpoint =====================================
 * ============================================================
 */
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
        'day' => 10,
        'week' => 12,
        'month' => 24,
    ];

    if (!isset($limits[$period])) {
        return new WP_Error('invalid_period', 'Invalid period parameter', ['status' => 400]);
    }

    // Expecting your existing helper:
    // hpv_get_top_viewed_posts($period, $limit) -> returns array of WP_Post objects
    $posts = hpv_get_top_viewed_posts($period, $limits[$period]);

    // --- Structure output for frontend Post interface ---
    return rest_ensure_response(array_map(function ($post) {
        // Featured Image
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $featured_image_url = get_the_post_thumbnail_url($post->ID, 'medium');
        $featured_image = $thumbnail_id ? [
            'node' => [
                'id' => $thumbnail_id,
                'sourceUrl' => $featured_image_url,
                'altText' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
            ]
        ] : null;

        // Author
        $author_id = $post->post_author;
        $author = $author_id ? [
            'node' => [
                'id' => $author_id,
                'name' => get_the_author_meta('display_name', $author_id),
                'slug' => get_the_author_meta('user_nicename', $author_id),
            ]
        ] : null;

        // Categories
        $category_terms = wp_get_post_terms($post->ID, 'category', ['fields' => 'all']);
        $categories = [
            'nodes' => array_map(function ($term) {
                return [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }, $category_terms)
        ];

        // Tags
        $tag_terms = wp_get_post_terms($post->ID, 'post_tag', ['fields' => 'all']);
        $tags = [
            'nodes' => array_map(function ($term) {
                return [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }, $tag_terms)
        ];

        return [
            'id' => (string) $post->ID,
            'databaseId' => (int) $post->ID,
            'slug' => $post->post_name,
            'uri' => get_permalink($post),
            'status' => $post->post_status,
            'author_name' => get_the_author_meta('display_name', $author_id),
            'category' => isset($category_terms[0]) ? $category_terms[0]->name : null,
            'title' => get_the_title($post),
            'excerpt' => get_the_excerpt($post),
            'date' => get_the_date('c', $post),
            'featuredImage' => $featured_image,
            'author' => $author,
            'categories' => $categories,
            'tags' => $tags,
            // 'comments' => [...], // Optional
            // 'seo'      => [...], // Optional
        ];
    }, $posts));
}

/**
 * ============================================================
 * === Customize Default /wp/v2/posts Output ==================
 * ============================================================
 */
add_filter('rest_prepare_post', 'hpv_customize_rest_post_response', 10, 3);

function hpv_customize_rest_post_response($response, $post, $request)
{
    if ($request->get_route() !== '/wp/v2/posts' && strpos($request->get_route(), '/wp/v2/posts?') !== 0) {
        return $response;
    }

    $thumbnail_id = get_post_thumbnail_id($post->ID);
    $featured_image_url = get_the_post_thumbnail_url($post->ID, 'medium');
    $featured_image = $thumbnail_id ? [
        'node' => [
            'id' => $thumbnail_id,
            'sourceUrl' => $featured_image_url,
            'altText' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
        ]
    ] : null;

    $author_id = $post->post_author;
    $author = $author_id ? [
        'node' => [
            'id' => $author_id,
            'name' => get_the_author_meta('display_name', $author_id),
            'slug' => get_the_author_meta('user_nicename', $author_id),
        ]
    ] : null;

    $category_terms = wp_get_post_terms($post->ID, 'category', ['fields' => 'all']);
    $categories = [
        'nodes' => array_map(function ($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
        }, $category_terms)
    ];

    $tag_terms = wp_get_post_terms($post->ID, 'post_tag', ['fields' => 'all']);
    $tags = [
        'nodes' => array_map(function ($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
        }, $tag_terms)
    ];

    $custom_data = [
        'id' => (string) $post->ID,
        'databaseId' => (int) $post->ID,
        'slug' => $post->post_name,
        'uri' => get_permalink($post),
        'status' => $post->post_status,
        'author_name' => get_the_author_meta('display_name', $author_id),
        'category' => isset($category_terms[0]) ? $category_terms[0]->name : null,
        'title' => get_the_title($post),
        'excerpt' => get_the_excerpt($post),
        'date' => get_the_date('c', $post),
        'featuredImage' => $featured_image,
        'author' => $author,
        'categories' => $categories,
        'tags' => $tags,
    ];

    return rest_ensure_response($custom_data);
}

/**
 * ============================================================
 * === NEW: Today’s Posts Endpoint ============================
 * ============================================================
 * Keeps all existing code and adds /hpv/v1/today-posts which
 * returns only posts published today, using the WP timezone.
 */
add_action('rest_api_init', function () {
    register_rest_route('hpv/v1', '/today-posts', [
        'methods' => 'GET',
        'callback' => 'hpv_rest_get_today_posts',
        'permission_callback' => '__return_true',
    ]);
});

if (!function_exists('hpv_rest_get_today_posts')) {
    function hpv_rest_get_today_posts($request)
    {
        // Get today's date (site timezone)
        $today = current_time('Y-m-d');

        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $today . ' 00:00:00',
                    'before' => $today . ' 23:59:59',
                    'inclusive' => true,
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
            'posts_per_page' => -1,
        ];

        $posts = get_posts($args);

        // Reuse same response structure as top-posts for consistency
        $data = array_map(function ($post) {
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $featured_image_url = get_the_post_thumbnail_url($post->ID, 'medium');
            $featured_image = $thumbnail_id ? [
                'node' => [
                    'id' => $thumbnail_id,
                    'sourceUrl' => $featured_image_url,
                    'altText' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
                ]
            ] : null;

            $author_id = $post->post_author;
            $author = $author_id ? [
                'node' => [
                    'id' => $author_id,
                    'name' => get_the_author_meta('display_name', $author_id),
                    'slug' => get_the_author_meta('user_nicename', $author_id),
                ]
            ] : null;

            $category_terms = wp_get_post_terms($post->ID, 'category', ['fields' => 'all']);
            $categories = [
                'nodes' => array_map(function ($term) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ];
                }, $category_terms)
            ];



            return [
                'id' => (string) $post->ID,
                'databaseId' => (int) $post->ID,
                'slug' => $post->post_name,
                'uri' => get_permalink($post),
                'status' => $post->post_status,
                'author_name' => get_the_author_meta('display_name', $author_id),
                'category' => isset($category_terms[0]) ? $category_terms[0]->name : null,
                'title' => get_the_title($post),
                'excerpt' => get_the_excerpt($post),
                'date' => get_the_date('c', $post),
                'featuredImage' => $featured_image,
                'author' => $author,
                'categories' => $categories,
            ];
        }, $posts);

        return rest_ensure_response($data);
    }
}
