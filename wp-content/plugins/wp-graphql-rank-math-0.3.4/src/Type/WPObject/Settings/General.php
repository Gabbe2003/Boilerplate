<?php
/**
 * The Rank Math general settings GraphQL Object.
 *
 * @package WPGraphQL\RankMath\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\WPObject\Settings;

use WPGraphQL\RankMath\Type\WPObject\Settings\General\BreadcrumbsConfig;
use WPGraphQL\RankMath\Type\WPObject\Settings\General\FrontendSeoScore;
use WPGraphQL\RankMath\Type\WPObject\Settings\General\Links;
use WPGraphQL\RankMath\Type\WPObject\Settings\General\Webmaster;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - General
 */
class General extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'General';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The RankMath SEO general site settings', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'breadcrumbs'         => [
				'type'        => BreadcrumbsConfig::get_type_name(),
				'description' => static fn () => __( 'Breadcrumbs settings.', 'wp-graphql-rank-math' ),
			],
			'hasBreadcrumbs'      => [
				'type'        => 'Boolean',
				'description' => static fn () => __( 'Whether RankMath breadcrumbs are enabled.', 'wp-graphql-rank-math' ),
			],
			'links'               => [
				'type'        => Links::get_type_name(),
				'description' => static fn () => __( 'Link settings.', 'wp-graphql-rank-math' ),
			],
			'webmaster'           => [
				'type'        => Webmaster::get_type_name(),
				'description' => static fn () => __( 'Webmaster Tools settings.', 'wp-graphql-rank-math' ),
			],
			'hasFrontendSeoScore' => [
				'type'        => 'Boolean',
				'description' => static fn () => __( 'Whether to display the calculated SEO Score as a badge on the frontend. It can be disabled for specific posts in the post editor.', 'wp-graphql-rank-math' ),
			],
			'frontendSeoScore'    => [
				'type'        => FrontendSeoScore::get_type_name(),
				'description' => static fn () => __( 'Frontend SEO score settings.', 'wp-graphql-rank-math' ),
			],
			'rssBeforeContent'    => [
				'type'        => 'String',
				'description' => static fn () => __( 'The content to add before each post in your site feeds', 'wp-graphql-rank-math' ),
			],
			'rssAfterContent'     => [
				'type'        => 'String',
				'description' => static fn () => __( 'The content to add after each post in your site feeds', 'wp-graphql-rank-math' ),
			],
		];
	}
}
