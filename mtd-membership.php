<?php
/**
 * Plugin Name: Jet Membership
 * Description: A lightweight membership plugin fully compatible with JetFormBuilder.
 * Version: 1.0.0
 * Author: Malik Tanveer
 * Author URI: https://mtdtechnologies.com/
 * Text Domain: mtd-membership
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define constants
define( 'MTD_MEMBERSHIP_VERSION', '1.0.0' );
define( 'MTD_MEMBERSHIP_PATH', plugin_dir_path( __FILE__ ) );
define( 'MTD_MEMBERSHIP_URL', plugin_dir_url( __FILE__ ) );

// Autoload classes
spl_autoload_register( function ( $class ) {
	$prefix = 'MTD_Membership\\';
	$base_dir = MTD_MEMBERSHIP_PATH . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

// Initialize the plugin
function mtd_membership_init() {
	MTD_Membership\Plugin::instance();
}

add_action( 'plugins_loaded', 'mtd_membership_init' );

// Activation hook - schedule cron
register_activation_hook( __FILE__, function() {
	if ( ! wp_next_scheduled( 'mtd_daily_deactivation_check' ) ) {
		wp_schedule_event( time(), 'daily', 'mtd_daily_deactivation_check' );
	}
});

// Deactivation hook - clear cron
register_deactivation_hook( __FILE__, function() {
	wp_clear_scheduled_hook( 'mtd_daily_deactivation_check' );
});
