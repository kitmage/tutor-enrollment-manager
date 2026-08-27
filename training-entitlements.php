<?php
/**
 * Plugin Name: WooCommerce Tutor LMS Training Entitlements
 * Description: Grants shareable Tutor LMS enrollment entitlements from paid WooCommerce order items.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Author: Training Entitlements
 * Text Domain: training-entitlements
 */

defined( 'ABSPATH' ) || exit;

define( 'WCTE_VERSION', '1.0.0' );
define( 'WCTE_FILE', __FILE__ );
define( 'WCTE_PATH', plugin_dir_path( __FILE__ ) );

require_once WCTE_PATH . 'includes/class-autoloader.php';
\WCTE\Autoloader::register();

register_activation_hook( __FILE__, array( '\\WCTE\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\WCTE\\Installer', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) || ! class_exists( '\\Tutor\\Models\\EnrollmentModel' ) ) {
			add_action( 'admin_notices', array( '\\WCTE\\Plugin', 'dependency_notice' ) );
			return;
		}
		\WCTE\Plugin::instance()->boot();
	}
);
