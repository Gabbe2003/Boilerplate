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
define( 'AUTH_KEY',          'd+Ni;%5i&c;y)6TUWau&qGOZ6+w4 TLuDgtFDb2?Kr9g2:ttURgO:V{%CsC,,O<5' );
define( 'SECURE_AUTH_KEY',   '.F?9>s3Xau8$Y>u@xdQdBkk|0XN@*zhthe$yfE%b+4Gd8$3ey2 ?ay|{;q6SA6SG' );
define( 'LOGGED_IN_KEY',     'TeBs..g29r[}Xa2ZLG]*pq17@hvES.eIfG{DMXdazrM%q/GRX>tP9Dt_kIkST_SE' );
define( 'NONCE_KEY',         'A%K_71hM[9iwz`BPtx)hC]mf=4+?);. Ov/C%:-NIhAJ#Li/R&x4xzEHZ0*irW#=' );
define( 'AUTH_SALT',         'kjh.~$?_:s!PD^=;_w<q[X~OTG2M%N$nnUyAtL@3%rmf{>FxiSlvf$tUCd~>fvx9' );
define( 'SECURE_AUTH_SALT',  'I^vhMmu6@{A$XAPgYgnKper6E) dyEjD_J~inDH_8o*:MQP-{O#baDhv+A,,m/Ev' );
define( 'LOGGED_IN_SALT',    'G=S$O[uX|FAfUKtpL%oVimAr:^:jO|t}d<*MYEpY-1q0](f,BM(Low#X?f_CS5e7' );
define( 'NONCE_SALT',        '+/ArDyOGd*BD(msbB_tkD6jTGy:$$N7_bh94PT5=g`wdjldtp4`aFw^,luASKC,l' );
define( 'WP_CACHE_KEY_SALT', 'nEKy+fPiN|_nY2dvE-0=zN&TZXz09xM|YEh459%{q9z7O? MAEDVm0nnwl(p4@iT' );


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
define('DISALLOW_FILE_EDIT', false);

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
