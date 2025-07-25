<?php
/**
 * The Rank Math Facebook OpenGraph meta tags GraphQL Object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\OpenGraph;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - Article
 */
class Article extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'OpenGraphArticle';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The OpenGraph Article meta.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'modifiedTime'  => [
				'type'        => 'String',
				'description' => static fn () => __( 'The date modified.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['modified_time'] ) ? (string) $source['modified_time'] : null,
			],
			'publishedTime' => [
				'type'        => 'String',
				'description' => static fn () => __( 'The date published.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['published_time'] ) ? (string) $source['published_time'] : null,
			],
			'publisher'     => [
				'type'        => 'String',
				'description' => static fn () => __( 'The publisher', 'wp-graphql-rank-math' ),
			],
			'author'        => [
				'type'        => 'String',
				'description' => static fn () => __( 'The author.', 'wp-graphql-rank-math' ),
			],
			'tags'          => [
				'type'        => [ 'list_of' => 'String' ],
				'description' => static fn () => __( 'The article tags.', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source ): ?array {
					$value = ! empty( $source['tag'] ) ? $source['tag'] : null;

					if ( empty( $value ) ) {
						return null;
					}

					if ( ! is_array( $value ) ) {
						$value = [ (string) $value ];
					}

					// Ensure all tags are strings.
					$value = array_map( 'strval', $value );

					return $value;
				},
			],
			'section'       => [
				'type'        => 'String',
				'description' => static fn () => __( 'The article category.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['section'] ) ? (string) $source['section'] : null,
			],
		];
	}
}
