<?php
/**
 * The Bulk editing type enum.
 *
 * @package WPGraphQL\RankMath\Type\Enum
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\Enum;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\EnumType;

/**
 * Class - BulkEditingTypeEnum
 */
class BulkEditingTypeEnum extends EnumType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'BulkEditingTypeEnum';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The setting chosen for the RankMath Bulk Editing feature', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values(): array {
		return [
			'DISABLED'  => [
				'description' => static fn () => __( 'Disabled.', 'wp-graphql-rank-math' ),
				'value'       => '0',
			],
			'ENABLED'   => [
				'description' => static fn () => __( 'Enabled.', 'wp-graphql-rank-math' ),
				'value'       => 'editing',
			],
			'READ_ONLY' => [
				'description' => static fn () => __( 'Read only.', 'wp-graphql-rank-math' ),
				'value'       => 'readonly',
			],
		];
	}
}
