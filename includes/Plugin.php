<?php

namespace MTD_Membership;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Plugin {

	private static $instance;

	public $cpt;
	public $member_manager;
	public $shortcodes;
	public $admin;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->member_manager = new Member_Manager();
		$this->cpt            = new CPT();
		$this->shortcodes     = new Shortcodes();

		if ( is_admin() ) {
			$this->admin = new Admin();
		}

		$this->init_hooks();
	}

	private function init_hooks() {
		// Reactive membership assignment when user meta is updated
		add_action( 'updated_user_meta', array( $this->member_manager, 'handle_meta_update' ), 10, 4 );
		add_action( 'added_user_meta', array( $this->member_manager, 'handle_meta_update' ), 10, 4 );

		// JFB Integration - Use admin_enqueue_scripts which ALWAYS fires
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_editor_assets' ) );

		// Process membership when user is registered via JFB
		add_action( 'jet-form-builder/actions/after-do-action/register_user', array( $this, 'process_register_user_membership' ), 10, 2 );
	}

	/**
	 * Check if we're on a JetFormBuilder edit page and enqueue our assets
	 */
	public function maybe_enqueue_editor_assets( $hook ) {
		// Only on post edit screens
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		// Check if JetFormBuilder is active
		if ( ! class_exists( 'Jet_Form_Builder\\Plugin' ) ) {
			return;
		}

		// Check if we're editing a jet-form-builder post type
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== 'jet-form-builder' ) {
			return;
		}

		$this->enqueue_editor_assets();
	}

	/**
	 * Process membership assignment after Register User action completes
	 */
	public function process_register_user_membership( $action, $handler ) {
		$settings = $action->settings ?? array();
		
		// Check for our custom setting added via the UI
		$level_id = ! empty( $settings['mtd_membership_level'] ) ? $settings['mtd_membership_level'] : '';

		if ( empty( $level_id ) ) {
			return;
		}

		// Get user ID from the response data
		$user_id = 0;
		if ( ! empty( $handler->response_data['user_id'] ) ) {
			$user_id = intval( $handler->response_data['user_id'] );
		} elseif ( ! empty( $handler->response_data['register_user'] ) ) {
			$user_id = intval( $handler->response_data['register_user'] );
		}

		if ( $user_id && $level_id ) {
			$this->member_manager->assign_membership( $user_id, $level_id );
		}
	}

	/**
	 * Enqueue editor assets for JetFormBuilder integration
	 */
	public function enqueue_editor_assets() {
		$levels = get_posts( array(
			'post_type'   => 'mtd_level',
			'numberposts' => -1,
			'post_status' => 'publish',
		) );

		$options = array(
			array( 'value' => '', 'label' => '— Select Membership Level —' ),
		);

		foreach ( $levels as $level ) {
			$options[] = array(
				'value' => (string) $level->ID,
				'label' => $level->post_title,
			);
		}

		// Enqueue our script
		wp_enqueue_script(
			'mtd-membership-jfb-editor',
			MTD_MEMBERSHIP_URL . 'assets/js/editor.js',
			array( 'jquery' ), // Use jQuery as dependency - it's always loaded in admin
			MTD_MEMBERSHIP_VERSION,
			true // Load in footer
		);

		// Pass levels data to JavaScript
		wp_localize_script( 'mtd-membership-jfb-editor', 'JetMembershipData', array(
			'levels' => $options,
		) );
	}
}
