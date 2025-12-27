<?php
namespace MTD_Membership\Integrations;

use Jet_Form_Builder\Actions\Action_Handler;
use Jet_Form_Builder\Actions\Types\Base;
use MTD_Membership\Plugin;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class JFB_Action extends Base {

	public function get_id() {
		return 'mtd_membership_settings';
	}

	public function get_name() {
		return 'MTD: Membership Settings';
	}

	public function self_script_name() {
		return 'mtd_membership_jfb_editor';
	}

	public function action_attributes() {
		return array(
			'user_id_field'  => array( 'default' => '' ),
			'level_id_field' => array( 'default' => '' ),
			'fixed_level_id' => array( 'default' => '' ),
		);
	}

	public function do_action( array $request, Action_Handler $handler ) {
		$settings = $this->settings;

		$user_id_field  = ! empty( $settings['user_id_field'] ) ? $settings['user_id_field'] : '';
		$level_id_field = ! empty( $settings['level_id_field'] ) ? $settings['level_id_field'] : '';
		$fixed_level_id = ! empty( $settings['fixed_level_id'] ) ? $settings['fixed_level_id'] : '';

		// Determine user ID
		$user_id = 0;
		
		// 1. Try from form field
		if ( ! empty( $user_id_field ) && isset( $request[ $user_id_field ] ) ) {
			$user_id = intval( $request[ $user_id_field ] );
		}
		
		// 2. Try from Register User action response
		if ( ! $user_id && ! empty( $handler->response_data['user_id'] ) ) {
			$user_id = intval( $handler->response_data['user_id'] );
		}
		
		// 3. Try from Register User action (alternate key)
		if ( ! $user_id && ! empty( $handler->response_data['register_user'] ) ) {
			$user_id = intval( $handler->response_data['register_user'] );
		}
		
		// 4. Fallback to current user
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// Determine level ID
		$level_id = 0;
		
		// 1. Fixed level takes priority
		if ( ! empty( $fixed_level_id ) ) {
			$level_id = intval( $fixed_level_id );
		} 
		// 2. Try from form field
		elseif ( ! empty( $level_id_field ) && isset( $request[ $level_id_field ] ) ) {
			$level_id = intval( $request[ $level_id_field ] );
		}

		// Assign membership if we have both IDs
		if ( $user_id && $level_id ) {
			Plugin::instance()->member_manager->assign_membership( $user_id, $level_id );
		}
	}

	public function action_data() {
		$levels = get_posts( array(
			'post_type'   => 'mtd_level',
			'numberposts' => -1,
			'post_status' => 'publish',
		) );

		$options = array(
			array( 'value' => '', 'label' => 'Select level...' ),
		);

		foreach ( $levels as $level ) {
			$options[] = array(
				'value' => (string) $level->ID,
				'label' => $level->post_title,
			);
		}

		return array(
			'levels' => $options,
		);
	}

	public function editor_labels() {
		return array(
			'user_id_field'  => 'User ID Field',
			'level_id_field' => 'Level ID Field (from form)',
			'fixed_level_id' => 'Fixed Membership Level',
		);
	}

	public function editor_labels_help() {
		return array(
			'user_id_field'  => 'Optional. Select a form field containing the User ID. Leave empty to use the current user or newly registered user.',
			'level_id_field' => 'Optional. Select a form field containing the Membership Level ID.',
			'fixed_level_id' => 'Select a specific level to assign to all users who submit this form.',
		);
	}
}
