<?php
/**
 * Interface for a Node with SEO data.
 *
 * @package WPGraphQL\RankMath\Type\WPInterface
 * @since 0.0.8
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPInterface;

use GraphQL\Error\UserError;
use WPGraphQL\Model\Model;
use WPGraphQL\RankMath\Model\ContentNodeSeo;
use WPGraphQL\RankMath\Model\ContentTypeSeo;
use WPGraphQL\RankMath\Model\TermNodeSeo;
use WPGraphQL\RankMath\Model\UserSeo;
use WPGraphQL\RankMath\Type\WPInterface\ContentNodeSeo as WPInterfaceContentNodeSeo;
use WPGraphQL\RankMath\Utils\Utils;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\InterfaceType;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Helper\Compat;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\TypeWithInterfaces;

/**
 * Class - NodeWithSeo
 */
class NodeWithSeo extends InterfaceType implements TypeWithInterfaces {
	/**
	 * {@inheritDoc}
	 *
	 * Overloaded so the type isn't prefixed.
	 */
	public static function register(): void {
		// @todo Remove when WPGraphQL < 2.3.0 is dropped.
		$config = Compat::resolve_graphql_config( static::get_type_config() );
		register_graphql_interface_type( static::type_name(), $config );

		/**
		 * Filters the GraphQL types that have SEO data.
		 * This is used to register the NodeWithSeo interface to the types.
		 *
		 * @since 0.0.8
		 *
		 * @param array $types_with_seo The types that have SEO data.
		 */
		$types_with_seo = apply_filters(
			'graphql_seo_types_with_seo',
			[
				'User',
				'TermNode',
				'ContentType',
				'ContentNode',
			]
		);

		// @todo only apply to ContentTypes that have SEO data.

		register_graphql_interfaces_to_types( self::type_name(), $types_with_seo );

		// Narrow down ContentNode types.
		Utils::overload_graphql_field_type( 'ContentNode', 'seo', WPInterfaceContentNodeSeo::get_type_name() );
		// This is necessary because the filter doesn't work for inheritance.
		Utils::overload_graphql_field_type( 'HierarchicalContentNode', 'seo', WPInterfaceContentNodeSeo::get_type_name() );
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'NodeWithRankMathSeo';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'A node with RankMath SEO data.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'seo' => [
				'type'        => Seo::get_type_name(),
				'description' => static fn () => __( 'The RankMath SEO data for the node.', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source ) {
					if ( ! $source instanceof Model ) {
						return null;
					}

					if ( empty( $source->uri ) ) {
						/**
						 * This can occur when querying the `Posts` page, since the Model "casts" it as a `ContentType` due to the lack of archive support.
						 *
						 * @see \WPGraphQL\Model\Post::$uri
						 */
						if ( $source instanceof \WPGraphQL\Model\Post && $source->isPostsPage ) {
							graphql_debug(
								sprintf(
									// translators: %d: The ID of the Post model being queried.
									esc_html__( 'Post %d is configured as the Posts archive, but is being queried as a `Page`. To get the SEO data, please query the object as a `ContentType` (e.g. via `nodeByUri`).', 'wp-graphql-rank-math' ),
									$source->databaseId,
								)
							);
						}
						return null;
					}

					$model = self::get_model_for_node( $source );

					if ( empty( $model ) ) {
						throw new UserError(
							sprintf(
								/* translators: %s: The name of the node type */
								esc_html__( 'The %s type does not have a corresponding SEO model class.', 'wp-graphql-rank-math' ),
								esc_html( get_class( $source ) )
							)
						);
					}

					return $model;
				},
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_interfaces(): array {
		return [ 'Node' ];
	}

	/**
	 * Gets the SEO model class for a given node model.
	 *
	 * @param \WPGraphQL\Model\Model $node_model The node model.
	 */
	private static function get_model_for_node( Model $node_model ): ?Model {
		// A map of the node models to their corresponding SEO model classes.
		switch ( true ) {
			case $node_model instanceof \WPGraphQL\Model\Post:
				$seo_model = isset( $node_model->databaseId ) ? new ContentNodeSeo( $node_model->databaseId ) : null;
				break;
			case $node_model instanceof \WPGraphQL\Model\PostType:
				$seo_model = isset( $node_model->name ) ? new ContentTypeSeo( $node_model->name ) : null;
				break;
			case $node_model instanceof \WPGraphQL\Model\Term:
				$seo_model = isset( $node_model->databaseId ) ? new TermNodeSeo( $node_model->databaseId ) : null;
				break;
			case $node_model instanceof \WPGraphQL\Model\User:
				$seo_model = isset( $node_model->databaseId ) ? new UserSeo( $node_model->databaseId ) : null;
				break;
			default:
				$seo_model = null;
		}

		/**
		 * Filter the SEO model class used for a given node model.
		 *
		 * @since 0.0.8
		 *
		 * @param \WPGraphQL\Model\Model|null $seo_model The SEO model class to use.
		 * @param \WPGraphQL\Model\Model $node_model The Model for the node.
		 */
		$seo_model = apply_filters( 'graphql_seo_resolved_model', $seo_model, $node_model );

		return $seo_model;
	}
}
