<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.kiliframework.org/
 * @since             1.0.0
 * @package           Kili_Core
 *
 * @wordpress-plugin
 * Plugin Name:       Kili Core
 * Plugin URI:        https://github.com/kiliframework/kili-core/
 * Description:       Framework code used to power Kili based WordPress themes.
 * Version:           1.0.0
 * Author:            Kili Team
 * Author URI:        https://www.kiliframework.org/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       kili-core
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'KILI_CORE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-kili-core-activator.php
 */
function activate_kili_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kili-core-activator.php';
	Kili_Core_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-kili-core-deactivator.php
 */
function deactivate_kili_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kili-core-deactivator.php';
	Kili_Core_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_kili_core' );
register_deactivation_hook( __FILE__, 'deactivate_kili_core' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-kili-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_kili_core() {

	$plugin = new Kili_Core();
	$plugin->run();

}
run_kili_core();
