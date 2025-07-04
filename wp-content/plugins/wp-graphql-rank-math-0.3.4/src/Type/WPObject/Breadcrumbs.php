<?php
/**
 * The Rank Math Breadcrumbs GraphQL Object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - Breadcrumbs
 */
class Breadcrumbs extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'Breadcrumbs';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The Breadcrumb trail.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'text'     => [
				'type'        => 'String',
				'description' => static fn () => __( 'The text for the given breadcrumb', 'wp-graphql-rank-math' ),
			],
			'url'      => [
				'type'        => 'String',
				'description' => static fn () => __( 'The url for the given breadcrumb', 'wp-graphql-rank-math' ),
			],
			'isHidden' => [
				'type'        => 'Boolean',
				'description' => static fn () => __( 'Whether the given breadcrumb is hidden from the schema', 'wp-graphql-rank-math' ),
			],
		];
	}
}
