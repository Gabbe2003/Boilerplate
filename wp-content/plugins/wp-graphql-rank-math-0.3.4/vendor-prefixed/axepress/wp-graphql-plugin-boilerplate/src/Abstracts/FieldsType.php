<?php

/**
 * Abstract class to make it easy to register Fields to an existing type in WPGraphQL.
 *
 * @package \AxeWP\GraphQL\Abstracts
 */
declare (strict_types=1);
namespace WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts;

use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Helper\Compat;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\GraphQLType;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\Registrable;
use WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\TypeWithFields;
if (!class_exists('\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\FieldsType')) {
    /**
     * Class - FieldsType
     */
    abstract class FieldsType implements GraphQLType, Registrable, TypeWithFields
    {
        /**
         * {@inheritDoc}
         */
        public static function init(): void
        {
            add_action('graphql_register_types', [static::class, 'register']);
        }
        /**
         * Defines the GraphQL type name registered in WPGraphQL.
         */
        abstract protected static function type_name(): string;
        /**
         * Gets the GraphQL type name.
         */
        abstract public static function get_type_name(): string;
        /**
         * Register Fields to the GraphQL Schema.
         */
        public static function register(): void
        {
            /** @todo remove when WPGraphQL > 2.3.0 is required. */
            $config = ['fields' => static::get_fields()];
            $config = Compat::resolve_graphql_config($config);
            register_graphql_fields(static::get_type_name(), $config['fields'] ?? []);
        }
    }
}