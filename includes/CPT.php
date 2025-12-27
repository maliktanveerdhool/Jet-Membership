<?php

namespace MTD_Membership;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class CPT {

	public function __construct() {
		add_action( 'init', array( $this, 'register_membership_level_cpt' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_membership_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_restriction_meta' ) );
		add_filter( 'the_content', array( $this, 'filter_restricted_content' ) );
		
		// Admin columns
		add_filter( 'manage_mtd_level_posts_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_mtd_level_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-mtd_level_sortable_columns', array( $this, 'sortable_columns' ) );
		
		// Admin styles
		add_action( 'admin_head', array( $this, 'admin_styles' ) );
	}

	public function register_membership_level_cpt() {
		$labels = array(
			'name'               => 'Membership Levels',
			'singular_name'      => 'Membership Level',
			'add_new'            => 'Add New Level',
			'add_new_item'       => 'Add New Membership Level',
			'edit_item'          => 'Edit Membership Level',
			'new_item'           => 'New Membership Level',
			'view_item'          => 'View Membership Level',
			'search_items'       => 'Search Levels',
			'not_found'          => 'No levels found',
			'not_found_in_trash' => 'No levels found in Trash',
			'menu_name'          => 'Jet Membership',
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'menu_icon'           => 'dashicons-groups',
			'show_in_rest'        => true,
			'menu_position'       => 25,
		);

		register_post_type( 'mtd_level', $args );
	}

	/**
	 * Add custom admin columns
	 */
	public function add_admin_columns( $columns ) {
		$new_columns = array();
		
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			
			if ( $key === 'title' ) {
				$new_columns['mtd_price']       = 'Price';
				$new_columns['mtd_duration']    = 'Duration';
				$new_columns['mtd_members']     = 'Members';
				$new_columns['mtd_status']      = 'Status';
				$new_columns['mtd_badge_color'] = 'Badge';
			}
		}
		
		return $new_columns;
	}

	/**
	 * Render custom admin columns
	 */
	public function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'mtd_price':
				$price = get_post_meta( $post_id, '_mtd_price', true );
				$currency = get_post_meta( $post_id, '_mtd_currency', true ) ?: '$';
				if ( ! empty( $price ) && floatval( $price ) > 0 ) {
					echo '<strong>' . esc_html( $currency . number_format( floatval( $price ), 2 ) ) . '</strong>';
				} else {
					echo '<span style="color: #00a32a; font-weight: bold;">Free</span>';
				}
				break;
				
			case 'mtd_duration':
				$duration = get_post_meta( $post_id, '_mtd_duration_days', true );
				$fixed_expiry = get_post_meta( $post_id, '_mtd_fixed_expiry', true );
				
				if ( ! empty( $fixed_expiry ) ) {
					echo '<span style="color: #d63638;">Expires: ' . esc_html( date( 'M j, Y', strtotime( $fixed_expiry ) ) ) . '</span>';
				} elseif ( empty( $duration ) || intval( $duration ) === 0 ) {
					echo '<span style="color: #00a32a;">♾️ Lifetime</span>';
				} else {
					$days = intval( $duration );
					if ( $days >= 365 ) {
						$years = floor( $days / 365 );
						echo $years . ' Year' . ( $years > 1 ? 's' : '' );
					} elseif ( $days >= 30 ) {
						$months = floor( $days / 30 );
						echo $months . ' Month' . ( $months > 1 ? 's' : '' );
					} else {
						echo $days . ' Day' . ( $days > 1 ? 's' : '' );
					}
				}
				break;
				
			case 'mtd_members':
				$count = $this->get_member_count( $post_id );
				$active = $this->get_active_member_count( $post_id );
				echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=mtd_level&page=mtd-members&level=' . $post_id ) ) . '">';
				echo '<strong>' . $active . '</strong> active';
				if ( $count > $active ) {
					echo ' <span style="color: #646970;">(' . $count . ' total)</span>';
				}
				echo '</a>';
				break;
				
			case 'mtd_status':
				$status = get_post_status( $post_id );
				if ( $status === 'publish' ) {
					echo '<span style="background: #e7f5e7; color: #00a32a; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">Active</span>';
				} else {
					echo '<span style="background: #f0f0f1; color: #646970; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">Draft</span>';
				}
				break;
				
			case 'mtd_badge_color':
				$color = get_post_meta( $post_id, '_mtd_badge_color', true ) ?: '#2271b1';
				$icon = get_post_meta( $post_id, '_mtd_icon', true ) ?: 'star-filled';
				echo '<div style="display: flex; align-items: center; gap: 8px;">';
				echo '<span style="width: 24px; height: 24px; background: ' . esc_attr( $color ) . '; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center;">';
				echo '<span class="dashicons dashicons-' . esc_attr( $icon ) . '" style="color: #fff; font-size: 14px; width: 14px; height: 14px;"></span>';
				echo '</span>';
				echo '</div>';
				break;
		}
	}

	/**
	 * Get member count for a level
	 */
	private function get_member_count( $level_id ) {
		$users = get_users( array(
			'meta_query' => array(
				array(
					'key'   => '_mtd_level_id',
					'value' => $level_id,
				),
			),
			'count_total' => true,
			'fields'      => 'ID',
		) );
		
		return count( $users );
	}

	/**
	 * Get active member count for a level
	 */
	private function get_active_member_count( $level_id ) {
		$users = get_users( array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'   => '_mtd_level_id',
					'value' => $level_id,
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_mtd_account_status',
						'value'   => 'active',
					),
					array(
						'key'     => '_mtd_account_status',
						'compare' => 'NOT EXISTS',
					),
				),
			),
			'count_total' => true,
			'fields'      => 'ID',
		) );

		// Filter by expiry
		$active_count = 0;
		foreach ( $users as $user_id ) {
			$expiry = get_user_meta( $user_id, '_mtd_expiry_date', true );
			if ( empty( $expiry ) || $expiry === '0' || intval( $expiry ) > time() ) {
				$active_count++;
			}
		}
		
		return $active_count;
	}

	/**
	 * Make columns sortable
	 */
	public function sortable_columns( $columns ) {
		$columns['mtd_price']    = 'mtd_price';
		$columns['mtd_duration'] = 'mtd_duration';
		return $columns;
	}

	/**
	 * Admin styles for meta boxes
	 */
	public function admin_styles() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== 'mtd_level' ) {
			return;
		}
		?>
		<style>
			/* Meta box styling */
			#mtd_level_settings .inside {
				padding: 0;
			}
			.mtd-meta-tabs {
				display: flex;
				border-bottom: 1px solid #c3c4c7;
				background: #f6f7f7;
				margin: 0;
				padding: 0;
			}
			.mtd-meta-tab {
				padding: 12px 20px;
				border: none;
				background: transparent;
				cursor: pointer;
				font-size: 13px;
				font-weight: 500;
				color: #646970;
				border-bottom: 3px solid transparent;
				margin-bottom: -1px;
				transition: all 0.2s ease;
			}
			.mtd-meta-tab:hover {
				color: #2271b1;
			}
			.mtd-meta-tab.active {
				background: #fff;
				color: #2271b1;
				border-bottom-color: #2271b1;
			}
			.mtd-meta-content {
				padding: 20px;
			}
			.mtd-meta-panel {
				display: none;
			}
			.mtd-meta-panel.active {
				display: block;
			}
			.mtd-field-group {
				margin-bottom: 20px;
			}
			.mtd-field-group:last-child {
				margin-bottom: 0;
			}
			.mtd-field-group label {
				display: flex;
				justify-content: space-between;
				align-items: center;
				font-weight: 600;
				margin-bottom: 8px;
				color: #1d2327;
			}
			.mtd-meta-key {
				font-family: 'Consolas', 'Monaco', monospace;
				font-size: 10px;
				background: #f0f0f1;
				color: #646970;
				padding: 2px 6px;
				border-radius: 3px;
				font-weight: normal;
				cursor: copy;
				transition: all 0.2s ease;
			}
			.mtd-meta-key:hover {
				background: #2271b1;
				color: #fff;
			}
			.mtd-meta-key::before {
				content: "Key: ";
				opacity: 0.7;
			}
			.mtd-field-group input,
			.mtd-field-group select,
			.mtd-field-group textarea {
				width: 100%;
				max-width: 400px;
			}
			.mtd-field-group textarea {
				min-height: 100px;
				max-width: 100%;
			}
			.mtd-field-help {
				margin-top: 6px;
				color: #646970;
				font-size: 12px;
			}
			.mtd-field-row {
				display: flex;
				gap: 20px;
				flex-wrap: wrap;
			}
			.mtd-field-row .mtd-field-group {
				flex: 1;
				min-width: 200px;
			}
			.mtd-color-picker-wrap {
				display: flex;
				gap: 10px;
				align-items: center;
			}
			.mtd-color-picker {
				width: 50px !important;
				height: 40px;
				padding: 0;
				border: 1px solid #c3c4c7;
				cursor: pointer;
			}
			.mtd-color-preview {
				padding: 8px 15px;
				border-radius: 4px;
				color: #fff;
				font-weight: 600;
				font-size: 12px;
			}
			.mtd-icon-grid {
				display: grid;
				grid-template-columns: repeat(8, 1fr);
				gap: 8px;
				margin-top: 10px;
			}
			.mtd-icon-option {
				width: 36px;
				height: 36px;
				border: 2px solid #dcdcde;
				border-radius: 4px;
				display: flex;
				align-items: center;
				justify-content: center;
				cursor: pointer;
				transition: all 0.2s ease;
				background: #fff;
			}
			.mtd-icon-option:hover {
				border-color: #2271b1;
				background: #f0f6fc;
			}
			.mtd-icon-option.selected {
				border-color: #2271b1;
				background: #2271b1;
				color: #fff;
			}
			.mtd-icon-option .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
			}
			
			/* Admin columns styling */
			.column-mtd_price { width: 100px; }
			.column-mtd_duration { width: 120px; }
			.column-mtd_members { width: 120px; }
			.column-mtd_status { width: 80px; }
			.column-mtd_badge_color { width: 60px; }
		</style>
		<?php
	}

	public function add_membership_meta_boxes() {
		add_meta_box(
			'mtd_level_settings',
			'Membership Level Settings',
			array( $this, 'render_meta_box' ),
			'mtd_level',
			'normal',
			'high'
		);

		add_meta_box(
			'mtd_level_id_info',
			'Level ID',
			array( $this, 'render_level_id_meta_box' ),
			'mtd_level',
			'side',
			'high'
		);

		add_meta_box(
			'mtd_content_restriction',
			'Jet Content Restriction',
			array( $this, 'render_restriction_meta_box' ),
			array( 'post', 'page' ),
			'side',
			'default'
		);
	}

	/**
	 * Render Level ID info box
	 */
	public function render_level_id_meta_box( $post ) {
		if ( $post->post_status === 'auto-draft' ) {
			echo '<p style="color: #646970;">Save the level to get an ID.</p>';
			return;
		}
		?>
		<div style="background: #f6f7f7; padding: 12px; border-radius: 4px; text-align: center;">
			<div style="font-size: 11px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">Level ID</div>
			<div style="font-size: 24px; font-weight: 700; color: #2271b1;"><?php echo esc_html( $post->ID ); ?></div>
		</div>
		<p style="margin-top: 12px; font-size: 12px; color: #646970;">
			Use this ID in shortcodes:<br>
			<code style="font-size: 11px;">[mtd_is_member level="<?php echo esc_html( $post->ID ); ?>"]</code>
		</p>
		<?php
	}

	public function render_meta_box( $post ) {
		// Get all meta values
		$price        = get_post_meta( $post->ID, '_mtd_price', true );
		$currency     = get_post_meta( $post->ID, '_mtd_currency', true ) ?: '$';
		$duration     = get_post_meta( $post->ID, '_mtd_duration_days', true );
		$fixed_expiry = get_post_meta( $post->ID, '_mtd_fixed_expiry', true );
		$description  = get_post_meta( $post->ID, '_mtd_description', true );
		$features     = get_post_meta( $post->ID, '_mtd_features', true );
		$badge_color  = get_post_meta( $post->ID, '_mtd_badge_color', true ) ?: '#2271b1';
		$icon         = get_post_meta( $post->ID, '_mtd_icon', true ) ?: 'star-filled';
		$trial_days   = get_post_meta( $post->ID, '_mtd_trial_days', true );
		$order        = get_post_meta( $post->ID, '_mtd_order', true ) ?: 0;
		$restrict_msg = get_post_meta( $post->ID, '_mtd_restrict_message', true );

		wp_nonce_field( 'mtd_level_meta_nonce', 'mtd_level_meta_nonce_field' );
		
		$icons = array(
			'star-filled', 'star-empty', 'awards', 'superhero', 'businessman', 
			'groups', 'admin-users', 'id', 'id-alt', 'tickets', 
			'heart', 'shield', 'lock', 'unlock', 'admin-network',
			'performance', 'chart-bar', 'money-alt', 'cart', 'products',
		);
		?>
		
		<div class="mtd-meta-tabs">
			<button type="button" class="mtd-meta-tab active" data-tab="general">General</button>
			<button type="button" class="mtd-meta-tab" data-tab="pricing">Pricing & Duration</button>
			<button type="button" class="mtd-meta-tab" data-tab="appearance">Appearance</button>
			<button type="button" class="mtd-meta-tab" data-tab="advanced">Advanced</button>
		</div>
		
		<div class="mtd-meta-content">
			<!-- General Tab -->
			<div class="mtd-meta-panel active" data-panel="general">
				<div class="mtd-field-group">
					<label for="mtd_description">
						Description
						<span class="mtd-meta-key">_mtd_description</span>
					</label>
					<textarea id="mtd_description" name="mtd_description" placeholder="Brief description of this membership level..."><?php echo esc_textarea( $description ); ?></textarea>
					<p class="mtd-field-help">A short description shown to potential members.</p>
				</div>
				
				<div class="mtd-field-group">
					<label for="mtd_features">
						Features
						<span class="mtd-meta-key">_mtd_features</span>
					</label>
					<textarea id="mtd_features" name="mtd_features" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"><?php echo esc_textarea( $features ); ?></textarea>
					<p class="mtd-field-help">List of features/benefits included in this level.</p>
				</div>
				
				<div class="mtd-field-group">
					<label for="mtd_order">
						Display Order
						<span class="mtd-meta-key">_mtd_order</span>
					</label>
					<input type="number" id="mtd_order" name="mtd_order" value="<?php echo esc_attr( $order ); ?>" min="0" style="max-width: 100px;">
					<p class="mtd-field-help">Lower numbers appear first when listing levels.</p>
				</div>
			</div>
			
			<!-- Pricing Tab -->
			<div class="mtd-meta-panel" data-panel="pricing">
				<div class="mtd-field-row">
					<div class="mtd-field-group">
						<label for="mtd_price">
							Price & Currency
							<span>
								<span class="mtd-meta-key" style="margin-right: 5px;">_mtd_price</span>
								<span class="mtd-meta-key">_mtd_currency</span>
							</span>
						</label>
						<div style="display: flex; gap: 10px; align-items: center;">
							<select id="mtd_currency" name="mtd_currency" style="width: auto; min-width: 70px;">
								<option value="$" <?php selected( $currency, '$' ); ?>>$ USD</option>
								<option value="€" <?php selected( $currency, '€' ); ?>>€ EUR</option>
								<option value="£" <?php selected( $currency, '£' ); ?>>£ GBP</option>
								<option value="¥" <?php selected( $currency, '¥' ); ?>>¥ JPY</option>
								<option value="₹" <?php selected( $currency, '₹' ); ?>>₹ INR</option>
								<option value="R" <?php selected( $currency, 'R' ); ?>>R SAR</option>
								<option value="د.إ" <?php selected( $currency, 'د.إ' ); ?>>د.إ AED</option>
							</select>
							<input type="number" step="0.01" id="mtd_price" name="mtd_price" value="<?php echo esc_attr( $price ); ?>" style="flex: 1; max-width: 150px;" placeholder="0.00">
						</div>
						<p class="mtd-field-help">Leave empty or 0 for a free tier.</p>
					</div>
					
					<div class="mtd-field-group">
						<label for="mtd_trial_days">
							Trial Period
							<span class="mtd-meta-key">_mtd_trial_days</span>
						</label>
						<input type="number" id="mtd_trial_days" name="mtd_trial_days" value="<?php echo esc_attr( $trial_days ); ?>" min="0" style="max-width: 100px;" placeholder="0">
						<p class="mtd-field-help">Optional trial before payment required.</p>
					</div>
				</div>
				
				<hr style="margin: 25px 0; border: none; border-top: 1px solid #dcdcde;">
				
				<div class="mtd-field-group">
					<label for="mtd_duration">
						Duration (Days)
						<span class="mtd-meta-key">_mtd_duration_days</span>
					</label>
					<input type="number" id="mtd_duration" name="mtd_duration" value="<?php echo esc_attr( $duration ); ?>" min="0" style="max-width: 150px;" placeholder="0 = Lifetime">
					<p class="mtd-field-help">How long the membership lasts. Enter 0 for lifetime access.</p>
				</div>
				
				<div class="mtd-field-group">
					<label for="mtd_fixed_expiry">
						Fixed Expiry Date
						<span class="mtd-meta-key">_mtd_fixed_expiry</span>
					</label>
					<input type="date" id="mtd_fixed_expiry" name="mtd_fixed_expiry" value="<?php echo esc_attr( $fixed_expiry ); ?>" style="max-width: 200px;">
					<p class="mtd-field-help">If set, ALL members of this level expire on this exact date (overrides duration).</p>
				</div>
			</div>
			
			<!-- Appearance Tab -->
			<div class="mtd-meta-panel" data-panel="appearance">
				<div class="mtd-field-group">
					<label>
						Badge Color
						<span class="mtd-meta-key">_mtd_badge_color</span>
					</label>
					<div class="mtd-color-picker-wrap">
						<input type="color" id="mtd_badge_color" name="mtd_badge_color" value="<?php echo esc_attr( $badge_color ); ?>" class="mtd-color-picker">
						<span class="mtd-color-preview" id="mtd_color_preview" style="background: <?php echo esc_attr( $badge_color ); ?>;">
							<?php echo esc_html( get_the_title( $post->ID ) ?: 'Level Name' ); ?>
						</span>
					</div>
					<p class="mtd-field-help">Choose a color for this membership badge.</p>
				</div>
				
				<div class="mtd-field-group">
					<label>
						Level Icon
						<span class="mtd-meta-key">_mtd_icon</span>
					</label>
					<input type="hidden" id="mtd_icon" name="mtd_icon" value="<?php echo esc_attr( $icon ); ?>">
					<div class="mtd-icon-grid">
						<?php foreach ( $icons as $icon_name ) : ?>
							<div class="mtd-icon-option <?php echo $icon === $icon_name ? 'selected' : ''; ?>" data-icon="<?php echo esc_attr( $icon_name ); ?>">
								<span class="dashicons dashicons-<?php echo esc_attr( $icon_name ); ?>"></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			
			<!-- Advanced Tab -->
			<div class="mtd-meta-panel" data-panel="advanced">
				<div class="mtd-field-group">
					<label for="mtd_restrict_message">
						Custom Restriction Message
						<span class="mtd-meta-key">_mtd_restrict_message</span>
					</label>
					<textarea id="mtd_restrict_message" name="mtd_restrict_message" placeholder="This content is exclusive to members. Please subscribe to access."><?php echo esc_textarea( $restrict_msg ); ?></textarea>
					<p class="mtd-field-help">Custom message shown when restricted content is accessed. Leave blank for default message.</p>
				</div>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			// Tab switching
			$('.mtd-meta-tab').on('click', function() {
				var tab = $(this).data('tab');
				$('.mtd-meta-tab').removeClass('active');
				$(this).addClass('active');
				$('.mtd-meta-panel').removeClass('active');
				$('.mtd-meta-panel[data-panel="' + tab + '"]').addClass('active');
			});
			
			// Color preview
			$('#mtd_badge_color').on('input', function() {
				$('#mtd_color_preview').css('background', $(this).val());
			});
			
			// Icon selection
			$('.mtd-icon-option').on('click', function() {
				$('.mtd-icon-option').removeClass('selected');
				$(this).addClass('selected');
				$('#mtd_icon').val($(this).data('icon'));
			});
		});
		</script>
		<?php
	}

	public function save_membership_meta( $post_id ) {
		if ( ! isset( $_POST['mtd_level_meta_nonce_field'] ) || ! wp_verify_nonce( $_POST['mtd_level_meta_nonce_field'], 'mtd_level_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Save all meta fields
		$fields = array(
			'mtd_price'           => '_mtd_price',
			'mtd_currency'        => '_mtd_currency',
			'mtd_duration'        => '_mtd_duration_days',
			'mtd_fixed_expiry'    => '_mtd_fixed_expiry',
			'mtd_description'     => '_mtd_description',
			'mtd_features'        => '_mtd_features',
			'mtd_badge_color'     => '_mtd_badge_color',
			'mtd_icon'            => '_mtd_icon',
			'mtd_trial_days'      => '_mtd_trial_days',
			'mtd_order'           => '_mtd_order',
			'mtd_restrict_message' => '_mtd_restrict_message',
		);

		foreach ( $fields as $post_key => $meta_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
			}
		}
	}

	public function render_restriction_meta_box( $post ) {
		$selected_level = get_post_meta( $post->ID, '_mtd_restrict_level', true );
		$custom_message = get_post_meta( $post->ID, '_mtd_custom_restrict_msg', true );
		$levels = get_posts( array( 
			'post_type'   => 'mtd_level', 
			'numberposts' => -1,
			'post_status' => 'publish',
			'orderby'     => 'date',
			'order'       => 'ASC',
		) );
		
		wp_nonce_field( 'mtd_restrict_nonce', 'mtd_restrict_nonce_field' );
		?>
		<style>
			.mtd-restrict-box { padding: 5px 0; }
			.mtd-restrict-box select { margin-bottom: 10px; }
			.mtd-restrict-box textarea { font-size: 12px; }
		</style>
		<div class="mtd-restrict-box">
			<p>
				<label for="mtd_restrict_level" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
					<strong>Restrict to:</strong>
					<span class="mtd-meta-key">_mtd_restrict_level</span>
				</label>
				<select name="mtd_restrict_level" id="mtd_restrict_level" class="widefat">
					<option value="">No Restriction (Public)</option>
					<option value="any" <?php selected( $selected_level, 'any' ); ?>>Any Membership</option>
					<?php foreach ( $levels as $level ) : ?>
						<option value="<?php echo $level->ID; ?>" <?php selected( $selected_level, $level->ID ); ?>>
							<?php echo esc_html( $level->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="mtd_custom_restrict_msg" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
					<strong>Custom Message:</strong>
					<span class="mtd-meta-key">_mtd_custom_restrict_msg</span>
				</label>
				<textarea name="mtd_custom_restrict_msg" id="mtd_custom_restrict_msg" class="widefat" rows="3" placeholder="Optional: Override default restriction message"><?php echo esc_textarea( $custom_message ); ?></textarea>
			</p>
		</div>
		<?php
	}

	public function save_restriction_meta( $post_id ) {
		// First handle mtd_level CPT
		if ( get_post_type( $post_id ) === 'mtd_level' ) {
			$this->save_membership_meta( $post_id );
			return;
		}

		if ( ! isset( $_POST['mtd_restrict_nonce_field'] ) || ! wp_verify_nonce( $_POST['mtd_restrict_nonce_field'], 'mtd_restrict_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['mtd_restrict_level'] ) ) {
			update_post_meta( $post_id, '_mtd_restrict_level', sanitize_text_field( $_POST['mtd_restrict_level'] ) );
		}
		
		if ( isset( $_POST['mtd_custom_restrict_msg'] ) ) {
			update_post_meta( $post_id, '_mtd_custom_restrict_msg', sanitize_textarea_field( $_POST['mtd_custom_restrict_msg'] ) );
		}
	}

	public function filter_restricted_content( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();
		$restrict_level = get_post_meta( $post_id, '_mtd_restrict_level', true );

		if ( empty( $restrict_level ) ) {
			return $content;
		}

		$user_id = get_current_user_id();
		$is_allowed = false;

		if ( $user_id ) {
			if ( $restrict_level === 'any' ) {
				$is_allowed = Plugin::instance()->member_manager->is_member( $user_id );
			} else {
				$is_allowed = Plugin::instance()->member_manager->is_member( $user_id, $restrict_level );
			}
		}

		if ( $is_allowed ) {
			return $content;
		}

		// Get custom message
		$custom_message = get_post_meta( $post_id, '_mtd_custom_restrict_msg', true );
		
		if ( empty( $custom_message ) && $restrict_level !== 'any' ) {
			$level_message = get_post_meta( $restrict_level, '_mtd_restrict_message', true );
			if ( ! empty( $level_message ) ) {
				$custom_message = $level_message;
			}
		}
		
		$message = ! empty( $custom_message ) 
			? $custom_message 
			: 'This content is reserved for members. Please subscribe or log in to access.';

		return '<div class="mtd-restriction-message">
					<div class="mtd-restriction-icon">
						<span class="dashicons dashicons-lock"></span>
					</div>
					<h3>Restricted Content</h3>
					<p>' . esc_html( $message ) . '</p>
				</div>
				<style>
					.mtd-restriction-message {
						padding: 40px 30px;
						border: 2px solid #e5e5e5;
						border-radius: 12px;
						background: linear-gradient(135deg, #f9f9f9 0%, #fff 100%);
						text-align: center;
						max-width: 500px;
						margin: 30px auto;
					}
					.mtd-restriction-icon {
						width: 60px;
						height: 60px;
						background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
						border-radius: 50%;
						display: flex;
						align-items: center;
						justify-content: center;
						margin: 0 auto 20px;
					}
					.mtd-restriction-icon .dashicons {
						font-size: 28px;
						width: 28px;
						height: 28px;
						color: #fff;
					}
					.mtd-restriction-message h3 {
						margin: 0 0 10px 0;
						font-size: 1.4em;
						color: #1d2327;
					}
					.mtd-restriction-message p {
						margin: 0;
						color: #646970;
						font-size: 1em;
						line-height: 1.6;
					}
				</style>';
	}
}
