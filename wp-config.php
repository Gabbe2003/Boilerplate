<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'X{64tnn-:^+_OS/m:MVVMz wcU.OiTQa1bvTE;&;asE&%UmrFEUf)A8}]8Cs/=tF' );
define( 'SECURE_AUTH_KEY',   'WJkK8]4e^A&7%,$3F.!oV3HE&Ov-nL$<Xwxqwq4fjR=+,0:<+0;`<T`uuaC_HB0X' );
define( 'LOGGED_IN_KEY',     'JuYoHz)-=e5vxv[yPI5si}AKSj0YZ12uxw0UP|TM5@mlY+^p2tFZt;@ |f::n2&[' );
define( 'NONCE_KEY',         '_jWyOs*t1PFLL(_=A#_]KQhyI1oDw2=AGQN<kx9`NVuJ]7JYB?7Yn^K.Ly{hTme4' );
define( 'AUTH_SALT',         'U?9@D YJAgA!!#?M41M]mvjPnH$i>PmKApb2j(WpYy?Vz){g{6g ^~u .[)o7u~X' );
define( 'SECURE_AUTH_SALT',  '6}+(STp:&eyM(+2*YRq(fT:A@*ZSt/Lw5CVtBBiYN.k#VN hV;?n0G)EeOt* i%:' );
define( 'LOGGED_IN_SALT',    'nA(,fqp6jOL&0/3!oF^E]AkIjma6fTt1FQUnodcTF=7]@9G_{Q]|qK}.uj*I:|kA' );
define( 'NONCE_SALT',        '2=>fPV^u#QhbM!r=R#ydat$4B6=!9XDs?7[}J!c.=:MY*v+bHX3=0 HNF!YeL`%Z' );
define( 'WP_CACHE_KEY_SALT', 'aYuzx8wTSgx1^;!~sS/Ms~fFZ0QPVj`S-;f?cHp.|Ghp53We`P:cde}^/Z3tTKTF' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}


define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
