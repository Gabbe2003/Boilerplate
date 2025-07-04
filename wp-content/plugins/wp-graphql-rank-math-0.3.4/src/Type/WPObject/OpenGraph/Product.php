<?php
/**
 * The Rank Math Facebook OpenGraph meta tags GraphQL Object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\OpenGraph;

use WPGraphQL\RankMath\Type\Enum\OpenGraphProductAvailabilityEnum;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - Product
 */
class Product extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'Product';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The OpenGraph Product meta.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'brand'        => [
				'type'        => 'String',
				'description' => static fn () => __( 'The brand of the product.', 'wp-graphql-rank-math' ),
			],
			'price'        => [
				'type'        => 'Float',
				'description' => static fn () => __( 'The price of the object', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source ): ?float {
					return ! empty( $source['price']['amount'] ) ? (float) $source['price']['amount'] : null;
				},
			],
			'currency'     => [
				'type'        => 'String',
				'description' => static fn () => __( 'The currency of the object price.', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source ): ?string {
					return ! empty( $source['price']['currency'] ) ? (string) $source['price']['currency'] : null;
				},
			],
			'availability' => [
				'type'        => OpenGraphProductAvailabilityEnum::get_type_name(),
				'description' => static fn () => __( 'The currency of the object price.', 'wp-graphql-rank-math' ),
			],

		];
	}
}
