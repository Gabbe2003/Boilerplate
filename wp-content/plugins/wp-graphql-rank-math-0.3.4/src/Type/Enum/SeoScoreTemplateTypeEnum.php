<?php
/**
 * The SEO Score Template Type enum.
 *
 * @package WPGraphQL\RankMath\Type\Enum
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\Enum;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\EnumType;

/**
 * Class - SeoScoreTemplateTypeEnum
 */
class SeoScoreTemplateTypeEnum extends EnumType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'SeoScoreTemplateTypeEnum';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The frontend SEO Score template type', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values(): array {
		return [
			'CIRCLE' => [
				'description' => static fn () => __( 'Circle template', 'wp-graphql-rank-math' ),
				'value'       => 'circle',
			],
			'SQUARE' => [
				'description' => static fn () => __( 'Square template', 'wp-graphql-rank-math' ),
				'value'       => 'square',
			],
		];
	}
}
