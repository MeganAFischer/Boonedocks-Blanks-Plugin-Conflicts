<?php
/**
* The base configuration for WordPress
*
* The wp-config.php creation script uses this file during the
* installation. You don't have to use the web site, you can
* copy this file to "wp-config.php" and fill in the values.
*
* This file contains the following configurations:
*
* * MySQL settings
* * Secret keys
* * Database table prefix
* * ABSPATH
*
* @link https://wordpress.org/support/article/editing-wp-config-php/
*
* @package WordPress
*/

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'boon2881_wp_2o0qb' );

/** MySQL database username */
define( 'DB_USER', 'boon2881_wp_bxu7u' );

/** MySQL database password */
define( 'DB_PASSWORD', 't2BQLo$86dkw9H~e' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost:3306' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
* Authentication Unique Keys and Salts.
*
* Change these to different unique phrases!
* You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
* You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
*
* @since 2.6.0
*/
define('AUTH_KEY',         '/`<pjt+0^Kz*3P29 |-%tpSDR%AqrQswm4?.^I$JI#iDs:4vFT=PCUTv(ir1UoF1');
define('SECURE_AUTH_KEY',  'fbt,:a9$]+W3R)v`|Li-fKr[2]J9ITzn[_cA;YM{2f$aSmO~.YxOZ@@)HYWtU^u8');
define('LOGGED_IN_KEY',    'OpY;YO2{y+[B@%<rN8#=x+:.YA[Z5u0pUw`=Jc[s(lZ+> (1t}]g)mji&.|-*3rF');
define('NONCE_KEY',        'AOr!e3mP?1uJbC.Ndw.vida< GaD3~Ditvs|om+w&)K9hp7c1 bA0rL|+v+>%!Q+');
define('AUTH_SALT',        '$iZJL+P3|}.|S9*F5CfBfP4?G%a:gq1|,NaOc+N;3sK=]HRy=[]|5w8^?AM~i7Sr');
define('SECURE_AUTH_SALT', 'o--^kfu~*8l |!W4P]=af=p=7%7F#:~*1Xxx&.O@b),?3t{uUEV0iI)h3ev(SBdw');
define('LOGGED_IN_SALT',   '6f&L$0UE#LOKB+c}D%%8cr|++|zLxx|X<t:7<|-sj4Ke[C9y@{u-1T`F(+rE_V#d');
define('NONCE_SALT',       '[ZqLb]urATa~qk+DOYaTiZ@+{j0=[y+jrdeEu6^RUA>GIa^&3+7O>r*~`r`f|Y[2');

/**
* WordPress Database Table prefix.
*
* You can have multiple installations in one database if you give each
* a unique prefix. Only numbers, letters, and underscores please!
*/
$table_prefix = 'K9XdF5_';


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

* @link https://wordpress.org/documentation/article/debugging-in-wordpress/

*/

// Added by Defender

// Enable WP_DEBUG mode
define( 'WP_DEBUG', true );

// Enable Debug logging to the /wp-content/debug.log file
define( 'WP_DEBUG_LOG', true );
@ini_set( 'log_errors', 1 );

// Disable display of errors and warnings
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

/* Add any custom values between this line and the "stop editing" line. */




define( 'WP_POST_REVISIONS', 5 );
define( 'DISALLOW_FILE_EDIT', true );

ini_set("memory_limit","2048M");
define('WP_MEMORY_LIMIT', '2048M');
define( 'WP_MAX_MEMORY_LIMIT', '2048M');
ini_set( 'max_input_vars' , 10000 );

define( 'WP_CACHE', true ); // Added by Hummingbird
/* That's all, stop editing! Happy publishing. */


/** Absolute path to the WordPress directory. */

if ( ! defined( 'ABSPATH' ) ) {

define( 'ABSPATH', __DIR__ . '/' );

}


/** Sets up WordPress vars and included files. */

require_once ABSPATH . 'wp-settings.php';