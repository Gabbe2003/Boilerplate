<?php
/**
 * DataLoader - Redirections
 *
 * @package WPGraphQL\RankMath\Modules\Redirection\Data\Loader
 */

declare( strict_types = 1 );

namespace WPGraphQL\RankMath\Modules\Redirection\Data\Loader;

use GraphQL\Error\UserError;
use WPGraphQL\Data\Loader\AbstractDataLoader;
use WPGraphQL\RankMath\Modules\Redirection\Model\Redirection;
use WPGraphQL\RankMath\Utils\RMUtils;

/**
 * Class - RedirectionsLoader
 */
class RedirectionsLoader extends AbstractDataLoader {
	/**
	 * Loader name.
	 *
	 * @var string
	 */
	public static string $name = 'redirections';

	/**
	 * {@inheritDoc}
	 */
	protected function get_model( $entry, $key ): Redirection {
		return new Redirection( $entry );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws \GraphQL\Error\UserError If a redirection does not exist.
	 */
	protected function loadKeys( array $keys ) {
		if ( empty( $keys ) ) {
			return $keys;
		}

		$table = RMUtils::get_redirections_table();

		$redirections = $table->where( 'id', 'IN', $keys )->get( ARRAY_A );

		$loaded = [];

		foreach ( $keys as $key ) {
			$index = array_search( $key, array_column( $redirections, 'id' ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- @todo remove this once we can investigate the strict type.
			if ( ! isset( $redirections[ $index ] ) ) {
				throw new UserError(
					sprintf(
						// translators: %s is the redirection ID.
						esc_html__( 'Redirection with ID "%s" does not exist.', 'wp-graphql-rank-math' ),
						esc_html( (string) $key )
					),
				);
			}

			$loaded[ $key ] = $redirections[ $index ];
		}

		return $loaded;
	}
}
