<?php
/**
 * Registers fields to General
 *
 * @package WPGraphQL\Fields
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Modules\Redirection\Fields;

use WPGraphQL\RankMath\Modules\Redirection\Type\WPObject\RedirectionSettings;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\FieldsType;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Traits\TypeNameTrait;

/**
 * Class - GeneralSettings
 */
class GeneralSettings extends FieldsType {
	use TypeNameTrait;

	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'General';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'redirections' => [
				'type'        => RedirectionSettings::get_type_name(),
				'description' => static fn () => __( 'RankMath SEO redirection settings', 'wp-graphql-rank-math' ),
			],
		];
	}
}
