<?php
/**
 * The KnowledgeGraph type enum.
 *
 * @package WPGraphQL\RankMath\Type\Enum
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Type\Enum;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\EnumType;

/**
 * Class - KnowledgeGraphTypeEnum
 */
class KnowledgeGraphTypeEnum extends EnumType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'KnowledgeGraphTypeEnum';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The knowledge graph type', 'wp-graphql-rank-math' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values(): array {
		return [
			'PERSON'  => [
				'description' => static fn () => __( 'Person.', 'wp-graphql-rank-math' ),
				'value'       => 'person',
			],
			'COMPANY' => [
				'description' => static fn () => __( 'Company.', 'wp-graphql-rank-math' ),
				'value'       => 'company',
			],
		];
	}
}
