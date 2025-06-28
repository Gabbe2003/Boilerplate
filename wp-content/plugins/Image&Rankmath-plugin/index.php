<?php
/**
 * Plugin Name: Image Connector & REST-API Meta Exposer
 * Description : Exposes Rank Math SEO fields in the REST API, swaps yesterday’s featured images with Pixabay photos (plus credit) and logs everything.
 * Version     : 1.2.1
 * Author      : You
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------
 * 0. Config
 * ---------------------------------------------------------------- */
const YPSEO_PIXABAY_KEY        = '51035765-dc055a26a149f644ef6904d34';
define('YPSEO_FALLBACK_IMAGE', plugin_dir_url(__FILE__) . 'assets/fallback.jpg');
const YPSEO_TRANSIENT_PREFIX   = 'ypseo_pixabay_completed_';   // daily suffix is added at run-time

/** Return the transient key for ‘today’. */
function ypseo_daily_transient_key(): string {
	return YPSEO_TRANSIENT_PREFIX . gmdate( 'Ymd' );
}

/* ------------------------------------------------------------------
 * 1. Expose Rank Math meta via REST
 * ---------------------------------------------------------------- */
add_action( 'init', function () {
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
	foreach ( $meta as $m ) {
		register_post_meta( 'post', $m, [
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can( 'edit_posts' ),
		] );
	}
} );

/* ------------------------------------------------------------------
 * 2. Replace featured images — run once per day
 * ---------------------------------------------------------------- */
add_action( 'init', 'ypseo_replace_featured_images_daily' );

function ypseo_replace_featured_images_daily() {

	$key = ypseo_daily_transient_key();
	if ( get_transient( $key ) ) {
		return;          // today’s run already finished
	}

	$posts  = ypseo_get_yesterdays_posts();
	$cache  = [];       // keyword → Pixabay JSON

	foreach ( $posts as $post ) {

		$raw_kw = trim( get_post_meta( $post->ID, 'rank_math_focus_keyword', true ) );
		if ( $raw_kw === '' ) {
			continue;
		}

		$first_kw  = strtolower( preg_split( '/\s+/', $raw_kw )[0] );

		if ( ! isset( $cache[ $first_kw ] ) ) {
			$cache[ $first_kw ] = ypseo_pixabay_query( $first_kw );
		}
		$pixabay = $cache[ $first_kw ];

		// Choose Pixabay hit or fallback
		if ( ! empty( $pixabay['hits'][0]['largeImageURL'] ) ) {
			$img_url   = $pixabay['hits'][0]['largeImageURL'];
			$img_title = $pixabay['hits'][0]['tags'] ?: $first_kw;
		} else {
			$img_url   = YPSEO_FALLBACK_IMAGE;
			$img_title = $first_kw;
		}

		ypseo_set_post_thumbnail_from_url( $post->ID, $img_url, $img_title );
	}

	set_transient( $key, 1, DAY_IN_SECONDS );     // mark today done
}

/* ------------------------------------------------------------------
 * 3. Helpers
 * ---------------------------------------------------------------- */
function ypseo_get_yesterdays_posts(): array {

	$args = [
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'date_query'     => [[
			'after'     => gmdate( 'Y-m-d', strtotime( '-1 day' ) ) . ' 00:00:00',
			'before'    => gmdate( 'Y-m-d', strtotime( '-1 day' ) ) . ' 23:59:59',
			'inclusive' => true,
		]],
	];

	return get_posts( $args );
}

function ypseo_pixabay_query( string $kw ): array {

	$url = add_query_arg( [
		'key'        => YPSEO_PIXABAY_KEY,
		'q'          => rawurlencode( $kw ),
		'image_type' => 'photo',
		'per_page'   => 5,
		'safesearch' => 'true',
	], 'https://pixabay.com/api/' );

	$response = wp_remote_get( $url, [ 'timeout' => 15 ] );
	if ( is_wp_error( $response ) ) {
		return [ 'error' => $response->get_error_message(), 'request' => $url ];
	}
	return json_decode( wp_remote_retrieve_body( $response ), true );
}

function ypseo_set_post_thumbnail_from_url( int $post_id, string $url, string $title ): bool {

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url );
	if ( is_wp_error( $tmp ) ) {
		return false;
	}

	$file = [
		'name'     => wp_basename( parse_url( $url, PHP_URL_PATH ) ),
		'tmp_name' => $tmp,
	];

	$attach_id = media_handle_sideload( $file, $post_id, '', [
		'post_title' => sanitize_text_field( $title ) . ' photo:Pixabay',
	] );

	if ( is_wp_error( $attach_id ) ) {
		@unlink( $file['tmp_name'] );
		return false;
	}

	set_post_thumbnail( $post_id, $attach_id );
	return true;
}

/* ------------------------------------------------------------------
 * 4. Optional console log for admins (visual feedback)
 * ---------------------------------------------------------------- */
add_action( 'wp_footer', function () {

	if ( ! ( is_user_logged_in() && current_user_can( 'manage_options' ) ) ) {
		return;
	}

	$key   = ypseo_daily_transient_key();
	$ran   = get_transient( $key ) ? '✅' : '⏳';
	$posts = ypseo_get_yesterdays_posts();

	echo '<script>';
	echo "console.info('Pixmap routine today: $ran');";
	foreach ( $posts as $p ) {
		$title = esc_js( get_the_title( $p ) );
		$thumb = esc_js( get_the_post_thumbnail_url( $p->ID, 'full' ) );
		echo "console.log('📝 {$title} — thumbnail →', '{$thumb}');";
	}
	echo '</script>';
} );