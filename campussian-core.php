<?php
/**
 * Plugin Name:       Campussian Core
 * Plugin URI:        https://salmanfarshe.me/campussian-core/
 * Description:       School role management and access control for the Campussian theme: custom roles, wp-admin gating, login routing and portal pages.
 * Version:           1.0.5
 * Author:            Salman Farshe
 * Author URI:        https://salmanfarshe.me
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       campussian-core
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CMPSIAN_CORE_VERSION', '1.0.5' );
define( 'CMPSIAN_CORE_FILE', __FILE__ );
define( 'CMPSIAN_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CMPSIAN_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once CMPSIAN_CORE_DIR . 'includes/class-campussian-core.php';
require_once CMPSIAN_CORE_DIR . 'includes/class-campussian-content.php';

/**
 * Singleton accessor for the core plugin.
 *
 * @since 1.0.0
 * @return Campussian_Core
 */
function cmpsian_core() {
	return Campussian_Core::instance();
}
cmpsian_core();

register_activation_hook( CMPSIAN_CORE_FILE, 'cmpsian_core_activate' );
register_deactivation_hook( CMPSIAN_CORE_FILE, 'cmpsian_core_deactivate' );

/**
 * Activation callback.
 *
 * @since 1.0.0
 * @return void
 */
function cmpsian_core_activate() {
	cmpsian_core()->activate();
}

/**
 * Deactivation callback.
 *
 * @since 1.0.0
 * @return void
 */
function cmpsian_core_deactivate() {
	cmpsian_core()->deactivate();
}
