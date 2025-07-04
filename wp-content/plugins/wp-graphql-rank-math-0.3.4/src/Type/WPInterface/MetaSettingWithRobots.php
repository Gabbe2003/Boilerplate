<?php
/**
 * Interface for meta settings with robots fields.
 *
 * @package WPGraphQL\RankMath\Type\WPInterface
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPInterface;

use WPGraphQL\RankMath\Type\Enum\RobotsMetaValueEnum;
use WPGraphQL\RankMath\Type\WPObject\AdvancedRobotsMeta;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\InterfaceType;

/**
 * Class - Settings
 */
class MetaSettingWithRobots extends InterfaceType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'MetaSettingWithRobots';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'Meta settings with robots fields.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'advancedRobotsMeta' => [
				'type'        => AdvancedRobotsMeta::get_type_name(),
				'description' => static fn () => __( 'Advanced robots meta tag settings.', 'wp-graphql-rank-math' ),
			],
			'robotsMeta'         => [
				'type'        => [ 'list_of' => RobotsMetaValueEnum::get_type_name() ],
				'description' => static fn () => __( 'Custom values for robots meta tag.', 'wp-graphql-rank-math' ),
			],
		];
	}
}
