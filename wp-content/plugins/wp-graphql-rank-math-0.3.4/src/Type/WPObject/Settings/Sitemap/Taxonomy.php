<?php
/**
 * The Taxonomy sitemap GraphQL object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject\Settings\Sitemap
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\Settings\Sitemap;

use RankMath\Helper;
use WPGraphQL\Data\Connection\TermObjectConnectionResolver;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\TypeWithConnections;

/**
 * Class - Taxonomy
 */
class Taxonomy extends ObjectType implements TypeWithConnections {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'SitemapTaxonomySettings';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_connections(): array {
		return [
			'connectedTerms' => [
				'toType'      => 'TermNode',
				'description' => static fn () => __( 'The connected terms whose URLs are included in the sitemap', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source, $args, $context, $info ) {
					if ( empty( $source['isInSitemap'] ) ) {
						return null;
					}

					$resolver = new TermObjectConnectionResolver( $source, $args, $context, $info, $source['type'] );

					$excluded_term_ids = Helper::get_settings( 'sitemap.exclude_terms' );
					$excluded_term_ids = ! empty( $excluded_term_ids ) ? array_map( 'absint', explode( ',', $excluded_term_ids ) ) : null;

					if ( ! empty( $excluded_term_ids ) ) {
						$resolver->set_query_arg( 'exclude', $excluded_term_ids );
					}

					return $resolver->get_connection();
				},
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The RankMath SEO Sitemap general settings.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'hasEmptyTerms' => [
				'type'        => 'Boolean',
				'description' => static fn () => __( 'Whether to archive pages of terms that have no posts associated.', 'wp-graphql-rank-math' ),
			],
			'isInSitemap'   => [
				'type'        => 'Boolean',
				'description' => static fn () => __( 'Whether the content type is included in the sitemap.', 'wp-graphql-rank-math' ),
			],
			'sitemapUrl'    => [
				'type'        => 'String',
				'description' => static fn () => __( 'The sitemap URL.', 'wp-graphql-rank-math' ),
			],
			'type'          => [
				'type'        => 'TaxonomyEnum',
				'description' => static fn () => __( 'The taxonomy type.', 'wp-graphql-rank-math' ),
			],
		];
	}
}
