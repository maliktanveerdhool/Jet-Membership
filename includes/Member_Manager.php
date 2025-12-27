<?php

namespace MTD_Membership;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Member_Manager {

	public function __construct() {
		add_action( 'mtd_daily_deactivation_check', array( $this, 'deactivate_expired_members' ) );
		add_filter( 'authenticate', array( $this, 'block_expired_login' ), 100, 2 );
	}

	/**
	 * Daily task to check for expired members and deactivate them
	 */
	public function deactivate_expired_members() {
		$expired_users = get_users( array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => '_mtd_expiry_date',
					'value'   => '0',
					'compare' => '!=',
				),
				array(
					'key'     => '_mtd_expiry_date',
					'value'   => time(),
					'compare' => '<',
					'type'    => 'NUMERIC',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_mtd_account_status',
						'value'   => 'deactivated',
						'compare' => '!=',
					),
					array(
						'key'     => '_mtd_account_status',
						'compare' => 'NOT EXISTS',
					),
				),
			),
		) );

		foreach ( $expired_users as $user ) {
			update_user_meta( $user->ID, '_mtd_account_status', 'deactivated' );
		}
	}

	/**
	 * Prevent login if account is deactivated or expired
	 */
	public function block_expired_login( $user, $username ) {
		if ( is_wp_error( $user ) || empty( $user ) || ! is_object( $user ) || empty( $user->ID ) ) {
			return $user;
		}

		$level_id = get_user_meta( $user->ID, '_mtd_level_id', true );
		
		// If user has no membership level, allow login
		if ( empty( $level_id ) ) {
			return $user;
		}

		// Check account status first
		$status = get_user_meta( $user->ID, '_mtd_account_status', true );
		if ( 'deactivated' === $status ) {
			return new \WP_Error( 
				'mtd_deactivated', 
				'<strong>Error:</strong> Your account is currently deactivated. Please contact the administrator.' 
			);
		}

		// Check if membership has expired
		if ( ! $this->is_member( $user->ID ) ) {
			// Deactivate the account
			update_user_meta( $user->ID, '_mtd_account_status', 'deactivated' );
			return new \WP_Error( 
				'mtd_expired', 
				'<strong>Error:</strong> Your membership has expired. Please renew your subscription to log in.' 
			);
		}

		return $user;
	}

	/**
	 * Assign a membership level to a user
	 */
	public function assign_membership( $user_id, $level_id ) {
		if ( empty( $user_id ) || empty( $level_id ) ) {
			return false;
		}

		$level_id = intval( $level_id );
		$user_id = intval( $user_id );

		// Verify the level exists
		$level = get_post( $level_id );
		if ( ! $level || $level->post_type !== 'mtd_level' ) {
			return false;
		}

		// Calculate expiry date
		$duration     = get_post_meta( $level_id, '_mtd_duration_days', true );
		$fixed_expiry = get_post_meta( $level_id, '_mtd_fixed_expiry', true );
		$expiry       = '0'; // Default: Lifetime

		if ( ! empty( $fixed_expiry ) ) {
			// Fixed expiry date takes priority
			$expiry = strtotime( $fixed_expiry . ' 23:59:59' );
		} elseif ( ! empty( $duration ) && intval( $duration ) > 0 ) {
			// Calculate from duration days
			$expiry = time() + ( intval( $duration ) * DAY_IN_SECONDS );
		}

		// Remove hooks to prevent infinite loop
		remove_action( 'updated_user_meta', array( $this, 'handle_meta_update' ), 10 );
		remove_action( 'added_user_meta', array( $this, 'handle_meta_update' ), 10 );

		// Update user meta
		update_user_meta( $user_id, '_mtd_level_id', $level_id );
		update_user_meta( $user_id, '_mtd_expiry_date', $expiry );
		update_user_meta( $user_id, '_mtd_account_status', 'active' );

		// Set start date if not already set
		$start_date = get_user_meta( $user_id, '_mtd_start_date', true );
		if ( empty( $start_date ) ) {
			update_user_meta( $user_id, '_mtd_start_date', time() );
		}

		// Re-add hooks
		add_action( 'updated_user_meta', array( $this, 'handle_meta_update' ), 10, 4 );
		add_action( 'added_user_meta', array( $this, 'handle_meta_update' ), 10, 4 );

		return true;
	}

	/**
	 * Automatically assign membership when _mtd_level_id meta is updated
	 */
	public function handle_meta_update( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( '_mtd_level_id' === $meta_key && ! empty( $_meta_value ) ) {
			$this->assign_membership( $object_id, $_meta_value );
		}
	}

	/**
	 * Check if a user is an active member
	 */
	public function is_member( $user_id, $level_id = null ) {
		if ( empty( $user_id ) ) {
			return false;
		}

		$current_level = get_user_meta( $user_id, '_mtd_level_id', true );
		$expiry        = get_user_meta( $user_id, '_mtd_expiry_date', true );
		$status        = get_user_meta( $user_id, '_mtd_account_status', true );

		// No level assigned
		if ( empty( $current_level ) ) {
			return false;
		}

		// Account manually deactivated
		if ( 'deactivated' === $status ) {
			return false;
		}

		// Check expiry (0 means lifetime)
		if ( ! empty( $expiry ) && $expiry !== '0' && time() > intval( $expiry ) ) {
			return false;
		}

		// Check specific level if requested
		if ( $level_id !== null && intval( $current_level ) !== intval( $level_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get user's membership level ID
	 */
	public function get_user_level( $user_id ) {
		return get_user_meta( $user_id, '_mtd_level_id', true );
	}

	/**
	 * Get user's membership expiry date
	 */
	public function get_user_expiry( $user_id ) {
		return get_user_meta( $user_id, '_mtd_expiry_date', true );
	}

	/**
	 * Revoke membership from a user
	 */
	public function revoke_membership( $user_id ) {
		delete_user_meta( $user_id, '_mtd_level_id' );
		delete_user_meta( $user_id, '_mtd_expiry_date' );
		delete_user_meta( $user_id, '_mtd_account_status' );
	}
}
