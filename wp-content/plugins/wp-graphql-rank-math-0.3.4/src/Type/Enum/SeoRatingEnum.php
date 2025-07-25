<?php
/**
 * The SEO Rating enum.
 *
 * @package WPGraphQL\RankMath\Type\Enum
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\Enum;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\EnumType;

/**
 * Class - SeoRatingEnum
 */
class SeoRatingEnum extends EnumType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'SeoRatingEnum';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The SEO rating', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values(): array {
		return [
			'UNKNOWN' => [
				'description' => static fn () => __( 'Unknown score.', 'wp-graphql-rank-math' ),
				'value'       => 'unknown',
			],
			'BAD'     => [
				'description' => static fn () => __( 'Bad ( < 50 ) score', 'wp-graphql-rank-math' ),
				'value'       => 'bad',
			],
			'GOOD'    => [
				'description' => static fn () => __( 'Good (50-79) score', 'wp-graphql-rank-math' ),
				'value'       => 'good',
			],
			'GREAT'   => [
				'description' => static fn () => __( 'Great ( > 80 ) score', 'wp-graphql-rank-math' ),
				'value'       => 'great',
			],
		];
	}
}
