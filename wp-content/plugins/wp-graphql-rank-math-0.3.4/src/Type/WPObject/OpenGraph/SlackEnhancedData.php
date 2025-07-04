<?php
/**
 * The Rank Math SlackEnhancedData OpenGraph meta tags GraphQL Object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\OpenGraph;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - SlackEnhancedData
 */
class SlackEnhancedData extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'OpenGraphSlackEnhancedData';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The Enhanced Data Tags for Slack Sharing.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'label' => [
				'type'        => 'String',
				'description' => static fn () => __( 'The Enhanced Data label', 'wp-graphql-rank-math' ),
			],
			'data'  => [
				'type'        => 'String',
				'description' => static fn () => __( 'The Enhanced Data', 'wp-graphql-rank-math' ),
			],
		];
	}
}
