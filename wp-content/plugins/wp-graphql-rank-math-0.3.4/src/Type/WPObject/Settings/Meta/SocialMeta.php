<?php
/**
 * The SocialMeta GraphQL object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject\Settings\Meta
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\Settings\Meta;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - SocialMeta
 */
class SocialMeta extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'SocialMetaSettings';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The RankMath SEO Social settings.', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'facebookPageUrl'    => [
				'type'        => 'String',
				'description' => static fn () => __( 'The complete Facebook page URL.', 'wp-graphql-rank-math' ),
			],
			'facebookAuthorUrl'  => [
				'type'        => 'String',
				'description' => static fn () => __( 'The personal Facebook profile URL used to show authorship when articles are shared on Facebook.', 'wp-graphql-rank-math' ),
			],
			'facebookAdminId'    => [
				'type'        => [ 'list_of' => 'Int' ],
				'description' => static fn () => __( 'A list of numeric Facebook admin User Ids.', 'wp-graphql-rank-math' ),
			],
			'facebookAppId'      => [
				'type'        => 'Int',
				'description' => static fn () => __( 'The facebook Facebook app ID.', 'wp-graphql-rank-math' ),
			],
			'twitterAuthorName'  => [
				'type'        => 'String',
				'description' => static fn () => __( 'Twitter Username of the auther used in the `twitter:creater` tag.', 'wp-graphql-rank-math' ),
			],
			'additionalProfiles' => [
				'type'        => [ 'list_of' => 'String' ],
				'description' => static fn () => __( 'Additional social profile URLs to add to the sameAs property for the Organization Schema.', 'wp-graphql-rank-math' ),
			],
		];
	}
}
