<?php
/**
 * The Rank Math Facebook OpenGraph meta tags GraphQL Object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\OpenGraph;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - Video
 */
class Video extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'OpenGraphVideo';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The OpenGraph Video meta.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'url'      => [
				'type'        => 'String',
				'description' => static fn () => __( 'The URL of the video.', 'wp-graphql-rank-math' ),
			],
			'duration' => [
				'type'        => 'String',
				'description' => static fn () => __( 'The duration of the video.', 'wp-graphql-rank-math' ),
			],
		];
	}
}
