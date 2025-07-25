<?php
/**
 * The SEO Twitter Card Type enum.
 *
 * @package WPGraphQL\RankMath\Type\Enum
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\Enum;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\EnumType;

/**
 * Class - TwitterCardTypeEnum
 */
class TwitterCardTypeEnum extends EnumType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'TwitterCardTypeEnum';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The Twitter Card Type Enum', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values(): array {
		return [
			'SUMMARY'             => [
				'description' => static fn () => __( 'Summary Card.', 'wp-graphql-rank-math' ),
				'value'       => 'summary_card',
			],
			'SUMMARY_LARGE_IMAGE' => [
				'description' => static fn () => __( 'Summary Card with Large Image.', 'wp-graphql-rank-math' ),
				'value'       => 'summary_large_image',
			],
			'APP'                 => [
				'description' => static fn () => __( 'The twitter App card', 'wp-graphql-rank-math' ),
				'value'       => 'app',
			],
			'PLAYER'              => [
				'description' => static fn () => __( 'The twitter Player card', 'wp-graphql-rank-math' ),
				'value'       => 'player',
			],
		];
	}
}
