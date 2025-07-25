<?php
/**
 * The Rank Math OpenGraph meta tags GraphQL Object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject;

use WPGraphQL\RankMath\Type\Enum\OpenGraphLocaleEnum;
use WPGraphQL\RankMath\Type\WPObject\OpenGraph;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - OpenGraphMeta
 */
class OpenGraphMeta extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'OpenGraphMeta';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The OpenGraph meta.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'articleMeta'       => [
				'type'        => OpenGraph\Article::get_type_name(),
				'description' => static fn () => __( 'The OpenGraph Article meta.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?array => ! empty( $source['article'] ) ? $source['article'] : null,
			],
			'alternateLocales'  => [
				'type'        => [ 'list_of' => OpenGraphLocaleEnum::get_type_name() ],
				'description' => static fn () => __( 'A list of other locales this page is available in', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source ): ?array {
					$value = ! empty( $source['og']['locale:alternate'] ) ? $source['og']['locale:alternate'] : null;

					if ( is_string( $value ) ) {
						$value = [ $value ];
					}

					return $value;
				},
			],
			'description'       => [
				'type'        => 'String',
				'description' => static fn () => __( 'A brief description of the content, usually between 2 and 4 sentences. ', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['og']['description'] ) ? (string) $source['og']['description'] : null,
			],
			'image'             => [
				'type'        => OpenGraph\Image::get_type_name(),
				'description' => static fn () => __( 'The OpenGraph image meta', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source ): ?array {
					$values = ! empty( $source['og']['image'] ) ? $source['og']['image'] : [];

					if ( ! empty( $source['og']['image'][0] ) ) {
						$values['url'] = (string) $source['og']['image'][0];
					}

					return ! empty( $values ) ? $values : null;
				},
			],
			'facebookMeta'      => [
				'type'        => OpenGraph\Facebook::get_type_name(),
				'description' => static fn () => __( 'The Facebook OpenGraph meta values.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?array => ! empty( $source['fb'] ) ? $source['fb'] : null,

			],
			'locale'            => [
				'type'        => OpenGraphLocaleEnum::get_type_name(),
				'description' => static fn () => __( 'The locale of the resource.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['og']['locale'] ) ? $source['og']['locale'] : null,
			],
			'productMeta'       => [
				'type'        => OpenGraph\Product::get_type_name(),
				'description' => static fn () => __( 'The Facebook OpenGraph meta values.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ) => ! empty( $source['product'] ) ? $source['product'] : null,
			],
			'slackEnhancedData' => [
				'type'        => [ 'list_of' => OpenGraph\SlackEnhancedData::get_type_name() ],
				'description' => static fn () => __( 'The Slack Enhanced Data meta values.', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source ): ?array {
					$values  = [];
					$counter = 1;

					while ( isset( $source['twitter'][ 'label' . $counter ] ) ) {
						$values[] = [
							'label' => $source['twitter'][ 'label' . $counter ],
							'data'  => $source['twitter'][ 'data' . $counter ],
						];
						++$counter;
					}

					return $values ?: null;
				},
			],
			'siteName'          => [
				'type'        => 'String',
				'description' => static fn () => __( 'The name of the site this resource is associated with.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['og']['site_name'] ) ? (string) $source['og']['site_name'] : null,
			],
			'title'             => [
				'type'        => 'String',
				'description' => static fn () => __( 'The title of your object as it should appear within the graph.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['og']['title'] ) ? (string) $source['og']['title'] : null,
			],
			'twitterMeta'       => [
				'type'        => OpenGraph\Twitter::get_type_name(),
				'description' => static fn () => __( 'The Twitter OpenGraph meta values.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?array => ! empty( $source['twitter'] ) ? $source['twitter'] : null,
			],
			'type'              => [
				'type'        => 'String',
				'description' => static fn () => __( 'The OpenGraph object type.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['og']['type'] ) ? (string) $source['og']['type'] : null,
			],
			'updatedTime'       => [
				'type'        => 'String',
				'description' => static fn () => __( 'The updated time', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['og']['updated_time'] ) ? (string) $source['og']['updated_time'] : null,
			],
			'url'               => [
				'type'        => 'String',
				'description' => static fn () => __( 'The canonical URL of your object that will be used as its permanent ID in the graph.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['og']['url'] ) ? (string) $source['og']['url'] : null,
			],
			'videoMeta'         => [
				'type'        => OpenGraph\Video::get_type_name(),
				'description' => static fn () => __( 'The Twitter OpenGraph meta values.', 'wp-graphql-rank-math' ),
				'resolve'     => static function ( $source ): ?array {
					$values = ! empty( $source['video'] ) ? $source['video'] : [];

					if ( isset( $source['og']['video'] ) ) {
						$values['url'] = $source['og']['video'];
					}

					return $values ?: null;
				},
			],
		];
	}
}
