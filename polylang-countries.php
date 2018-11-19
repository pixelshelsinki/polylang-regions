<?php
/**
 * Plugin Name:     Polylang Countries
 * Description:     Adds support for countries in directory url patterns.
 * Author:          Pixels Helsinki Oy
 * Author URI:      https://pixels.fi
 * Text Domain:     polylang-countries
 * Domain Path:     /languages
 * Version:         1.0.0
 */

namespace PolylangCountries;

if ( ! defined( 'ABSPATH' ) ) {
 	exit; // Don't access directly
}

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function load_textdomain() {
  load_plugin_textdomain( 'polylang-countries', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_textdomain' );

/**
 * Load plugin files.
 */
$includes = [
  'include/actions.php',
  'include/api.php',
  'include/class-plc-links-directory-countries.php',
];

foreach ($includes as $file) {
  if (!$filepath = plugin_dir_path(__FILE__) . $file ) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'polylang-countries'), $file), E_USER_ERROR);
  }
  require_once $filepath;
}
unset($file, $filepath);
