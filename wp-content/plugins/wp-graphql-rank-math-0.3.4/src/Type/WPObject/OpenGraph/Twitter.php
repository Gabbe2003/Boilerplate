<?php
/**
 * The Rank Math Twitter OpenGraph meta tags GraphQL Object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\OpenGraph;

use WPGraphQL\RankMath\Type\Enum\TwitterCardTypeEnum;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - Twitter
 */
class Twitter extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'OpenGraphTwitter';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The OpenGraph Twitter meta.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'card'                    => [
				'type'        => TwitterCardTypeEnum::get_type_name(),
				'description' => static fn () => __( 'The Twitter card type', 'wp-graphql-rank-math' ),
			],
			'title'                   => [
				'type'        => 'String',
				'description' => static fn () => __( 'Title of content', 'wp-graphql-rank-math' ),
			],
			'description'             => [
				'type'        => 'String',
				'description' => static fn () => __( 'Description of content (maximum 200 characters)', 'wp-graphql-rank-math' ),
			],
			'appCountry'              => [
				'type'        => 'String',
				'description' => static fn () => __( 'The app country.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['app:country'] ) ? (string) $source['app:country'] : null,
			],
			'ipadApp'                 => [
				'type'        => TwitterApp::get_type_name(),
				'description' => static fn () => __( 'The Twitter iPad app meta', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?array => self::get_app_meta( $source, 'ipad' ),
			],
			'iphoneApp'               => [
				'type'        => TwitterApp::get_type_name(),
				'description' => static fn () => __( 'The Twitter iPhone app meta', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?array => self::get_app_meta( $source, 'iphone' ),
			],
			'googleplayApp'           => [
				'type'        => TwitterApp::get_type_name(),
				'description' => static fn () => __( 'The Twitter Google Play app meta', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?array => self::get_app_meta( $source, 'googleplay' ),
			],
			'playerUrl'               => [
				'type'        => 'Int',
				'description' => static fn () => __( 'URL of the twitter player.', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?int => ! empty( $source['player'] ) ? (int) $source['player'] : null,
			],
			'playerStream'            => [
				'type'        => 'String',
				'description' => static fn () => __( 'URL to raw video or audio stream', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['player:stream'] ) ? (string) $source['player:stream'] : null,
			],
			'site'                    => [
				'type'        => 'String',
				'description' => static fn () => __( '@username of website', 'wp-graphql-rank-math' ),
			],
			'playerStreamContentType' => [
				'type'        => 'String',
				'description' => static fn () => __( 'The content type of the stream', 'wp-graphql-rank-math' ),
				'resolve'     => static fn ( $source ): ?string => ! empty( $source['player:stream:content_type'] ) ? (string) $source['player:stream:content_type'] : null,
			],
			'image'                   => [
				'type'        => 'String',
				'description' => static fn () => __( 'URL of image to use in the card.', 'wp-graphql-rank-math' ),
			],
			'creator'                 => [
				'type'        => 'String',
				'description' => static fn () => __( '@username of content creator', 'wp-graphql-rank-math' ),
			],

		];
	}

	/**
	 * Get the app meta for the twitter app type.
	 *
	 * @param array<string, mixed> $source The values from the resolver.
	 * @param string               $type The app type.
	 *
	 * @return ?array<string, mixed>
	 */
	protected static function get_app_meta( array $source, string $type ): ?array {
		$values = [];

		if ( ! empty( $source[ 'app:name:' . $type ] ) ) {
			$values['name'] = $source[ 'app:name:' . $type ];
		}
		if ( ! empty( $source[ 'app:id:' . $type ] ) ) {
			$values['id'] = $source[ 'app:id:' . $type ];
		}
		if ( ! empty( $source[ 'app:url:' . $type ] ) ) {
			$values['url'] = $source[ 'app:url:' . $type ];
		}

		return $values ?: null;
	}
}
