<?php
/**
 * The LocalMeta GraphQL object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject\Settings\Meta
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\Settings\Meta;

use WPGraphQL\AppContext;
use WPGraphQL\RankMath\Type\Enum\KnowledgeGraphTypeEnum;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - LocalMeta
 */
class LocalMeta extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'LocalMetaSettings';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The RankMath SEO Local settings.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'type' => [
				'type'        => KnowledgeGraphTypeEnum::get_type_name(),
				'description' => static fn () => __( 'Whether the site represents a person or an organization.', 'wp-graphql-rank-math' ),
			],
			'name' => [
				'type'        => 'String',
				'description' => static fn () => __( 'Your name or company name to be used in Google\'s Knowledge Graph', 'wp-graphql-rank-math' ),
			],
			'logo' => [
				'type'        => 'MediaItem',
				'description' => static fn () => __( 'The logo to be used in the Google\'s Knowledge Graph.', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source, array $args, AppContext $context ) {
					return ! empty( $source['logoId'] ) ? $context->get_loader( 'post' )->load_deferred( $source['logoId'] ) : null;
				},
			],
			'url'  => [
				'type'        => 'String',
				'description' => static fn () => __( 'URL of the item.', 'wp-graphql-rank-math' ),
			],
		];
	}
}
