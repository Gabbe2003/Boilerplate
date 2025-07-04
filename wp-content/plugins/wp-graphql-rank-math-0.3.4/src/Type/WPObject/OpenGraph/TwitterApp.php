<?php
/**
 * The Rank Math TwitterApp OpenGraph meta tags GraphQL Object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\OpenGraph;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - TwitterApp
 */
class TwitterApp extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'OpenGraphTwitterApp';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The OpenGraph Twitter App meta.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'name' => [
				'type'        => 'String',
				'description' => static fn () => __( 'The name of the Twitter app.', 'wp-graphql-rank-math' ),
			],
			'id'   => [
				'type'        => 'ID',
				'description' => static fn () => __( 'The App ID .', 'wp-graphql-rank-math' ),
			],
			'url'  => [
				'type'        => 'String',
				'description' => static fn () => __( 'Your app\’s custom URL scheme.', 'wp-graphql-rank-math' ),
			],
		];
	}
}
