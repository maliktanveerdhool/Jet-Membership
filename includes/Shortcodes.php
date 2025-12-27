<?php

namespace MTD_Membership;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Shortcodes {

	public function __construct() {
		// Content restriction shortcodes
		add_shortcode( 'mtd_is_member', array( $this, 'restrict_content_shortcode' ) );
		add_shortcode( 'mtd_not_member', array( $this, 'not_member_shortcode' ) );
		
		// Information shortcodes
		add_shortcode( 'mtd_member_info', array( $this, 'member_info_shortcode' ) );
		add_shortcode( 'mtd_countdown', array( $this, 'countdown_shortcode' ) );
		
		// Display shortcodes (disabled - using JetFormBuilder for display)
		// add_shortcode( 'mtd_pricing_table', array( $this, 'pricing_table_shortcode' ) );
		// add_shortcode( 'mtd_levels_list', array( $this, 'levels_list_shortcode' ) );
		
		// User action shortcodes
		add_shortcode( 'mtd_login_link', array( $this, 'login_link_shortcode' ) );
		add_shortcode( 'mtd_logout_link', array( $this, 'logout_link_shortcode' ) );
		add_shortcode( 'mtd_account_link', array( $this, 'account_link_shortcode' ) );
	}

	/**
	 * Shortcode: [mtd_is_member level="123"]Content for members[/mtd_is_member]
	 * Shows content only if user is a member (optionally of a specific level)
	 */
	public function restrict_content_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'level'   => null,
			'message' => '',
		), $atts );

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			if ( ! empty( $atts['message'] ) ) {
				return '<div class="mtd-message mtd-message-info">' . esc_html( $atts['message'] ) . '</div>';
			}
			return '';
		}

		$level_id = ! empty( $atts['level'] ) ? intval( $atts['level'] ) : null;

		if ( Plugin::instance()->member_manager->is_member( $user_id, $level_id ) ) {
			return do_shortcode( $content );
		}

		if ( ! empty( $atts['message'] ) ) {
			return '<div class="mtd-message mtd-message-warning">' . esc_html( $atts['message'] ) . '</div>';
		}
		
		return '';
	}

	/**
	 * Shortcode: [mtd_not_member]Content for non-members[/mtd_not_member]
	 * Shows content only if user is NOT a member
	 */
	public function not_member_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'level' => null,
		), $atts );
		
		$user_id = get_current_user_id();

		// Not logged in = not a member
		if ( ! $user_id ) {
			return do_shortcode( $content );
		}

		$level_id = ! empty( $atts['level'] ) ? intval( $atts['level'] ) : null;

		// If logged in but not a member (of specific level if provided)
		if ( ! Plugin::instance()->member_manager->is_member( $user_id, $level_id ) ) {
			return do_shortcode( $content );
		}

		return '';
	}

	/**
	 * Shortcode: [mtd_member_info field="level_name"]
	 * Displays membership information for the current user
	 * Fields: level_name, level_id, expiry_date, status, days_remaining, start_date, 
	 *         price, description, features, badge, icon
	 */
	public function member_info_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'field'   => 'level_name',
			'format'  => '', // For dates
			'default' => '', // Default if no value
		), $atts );

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return esc_html( $atts['default'] );
		}

		$level_id = get_user_meta( $user_id, '_mtd_level_id', true );
		
		if ( empty( $level_id ) ) {
			return esc_html( $atts['default'] );
		}

		$date_format = ! empty( $atts['format'] ) ? $atts['format'] : get_option( 'date_format' );

		switch ( $atts['field'] ) {
			case 'level_name':
				$level = get_post( $level_id );
				return $level ? esc_html( $level->post_title ) : esc_html( $atts['default'] );
			
			case 'level_id':
				return esc_html( $level_id );
			
			case 'expiry_date':
				$expiry = get_user_meta( $user_id, '_mtd_expiry_date', true );
				if ( empty( $expiry ) || $expiry === '0' ) {
					return 'Lifetime';
				}
				return esc_html( date_i18n( $date_format, intval( $expiry ) ) );
			
			case 'status':
				$account_status = get_user_meta( $user_id, '_mtd_account_status', true ) ?: 'active';
				if ( 'deactivated' === $account_status ) {
					return 'Deactivated';
				}
				return Plugin::instance()->member_manager->is_member( $user_id ) ? 'Active' : 'Expired';
			
			case 'status_badge':
				$account_status = get_user_meta( $user_id, '_mtd_account_status', true ) ?: 'active';
				$is_active = Plugin::instance()->member_manager->is_member( $user_id );
				
				if ( 'deactivated' === $account_status ) {
					return '<span class="mtd-status-badge mtd-status-deactivated">Deactivated</span>';
				}
				if ( $is_active ) {
					return '<span class="mtd-status-badge mtd-status-active">Active</span>';
				}
				return '<span class="mtd-status-badge mtd-status-expired">Expired</span>';
			
			case 'days_remaining':
				$expiry = get_user_meta( $user_id, '_mtd_expiry_date', true );
				if ( empty( $expiry ) || $expiry === '0' ) {
					return 'Unlimited';
				}
				$days = ceil( ( intval( $expiry ) - time() ) / DAY_IN_SECONDS );
				return max( 0, $days );
			
			case 'start_date':
				$start = get_user_meta( $user_id, '_mtd_start_date', true );
				if ( empty( $start ) ) {
					return esc_html( $atts['default'] );
				}
				return esc_html( date_i18n( $date_format, intval( $start ) ) );
			
			case 'price':
				$price = get_post_meta( $level_id, '_mtd_price', true );
				$currency = get_post_meta( $level_id, '_mtd_currency', true ) ?: '$';
				if ( empty( $price ) || floatval( $price ) <= 0 ) {
					return 'Free';
				}
				return esc_html( $currency . number_format( floatval( $price ), 2 ) );
			
			case 'description':
				return esc_html( get_post_meta( $level_id, '_mtd_description', true ) );
			
			case 'features':
				$features = get_post_meta( $level_id, '_mtd_features', true );
				if ( empty( $features ) ) {
					return esc_html( $atts['default'] );
				}
				$features_array = array_filter( explode( "\n", $features ) );
				$output = '<ul class="mtd-features-list">';
				foreach ( $features_array as $feature ) {
					$output .= '<li>' . esc_html( trim( $feature ) ) . '</li>';
				}
				$output .= '</ul>';
				return $output;
			
			case 'badge':
				$color = get_post_meta( $level_id, '_mtd_badge_color', true ) ?: '#2271b1';
				$icon = get_post_meta( $level_id, '_mtd_icon', true ) ?: 'star-filled';
				$level = get_post( $level_id );
				$name = $level ? $level->post_title : 'Member';
				return '<span class="mtd-member-badge" style="background: ' . esc_attr( $color ) . ';">
					<span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span> ' . esc_html( $name ) . '
				</span>';
				
			default:
				return esc_html( $atts['default'] );
		}
	}

	/**
	 * Shortcode: [mtd_countdown]
	 * Shows countdown timer to membership expiry
	 */
	public function countdown_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'expired_text' => 'Membership Expired',
			'lifetime_text' => 'Lifetime Access',
			'format' => 'full', // full, compact, days_only
		), $atts );

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return '';
		}

		$expiry = get_user_meta( $user_id, '_mtd_expiry_date', true );
		
		if ( empty( $expiry ) || $expiry === '0' ) {
			return '<span class="mtd-countdown mtd-countdown-lifetime">' . esc_html( $atts['lifetime_text'] ) . '</span>';
		}

		$expiry_time = intval( $expiry );
		$now = time();
		
		if ( $now >= $expiry_time ) {
			return '<span class="mtd-countdown mtd-countdown-expired">' . esc_html( $atts['expired_text'] ) . '</span>';
		}

		$diff = $expiry_time - $now;
		$days = floor( $diff / DAY_IN_SECONDS );
		$hours = floor( ( $diff % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
		$minutes = floor( ( $diff % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );

		$id = 'mtd-countdown-' . wp_rand();
		
		if ( $atts['format'] === 'days_only' ) {
			return '<span class="mtd-countdown">' . $days . ' day' . ( $days !== 1 ? 's' : '' ) . ' remaining</span>';
		}
		
		if ( $atts['format'] === 'compact' ) {
			return '<span class="mtd-countdown">' . $days . 'd ' . $hours . 'h ' . $minutes . 'm</span>';
		}

		// Full format with live countdown
		return '<div class="mtd-countdown-timer" id="' . esc_attr( $id ) . '" data-expiry="' . esc_attr( $expiry_time ) . '">
			<div class="mtd-countdown-item"><span class="mtd-countdown-value" data-days>' . $days . '</span><span class="mtd-countdown-label">Days</span></div>
			<div class="mtd-countdown-item"><span class="mtd-countdown-value" data-hours>' . $hours . '</span><span class="mtd-countdown-label">Hours</span></div>
			<div class="mtd-countdown-item"><span class="mtd-countdown-value" data-minutes>' . $minutes . '</span><span class="mtd-countdown-label">Minutes</span></div>
		</div>
		<style>
			.mtd-countdown-timer { display: flex; gap: 15px; justify-content: center; }
			.mtd-countdown-item { text-align: center; background: #f6f7f7; padding: 15px 20px; border-radius: 8px; min-width: 70px; }
			.mtd-countdown-value { display: block; font-size: 28px; font-weight: 700; color: #2271b1; }
			.mtd-countdown-label { display: block; font-size: 12px; color: #646970; text-transform: uppercase; margin-top: 5px; }
		</style>
		<script>
			(function() {
				var el = document.getElementById("' . esc_js( $id ) . '");
				if (!el) return;
				var expiry = parseInt(el.dataset.expiry) * 1000;
				setInterval(function() {
					var now = Date.now();
					var diff = Math.max(0, expiry - now);
					var d = Math.floor(diff / 86400000);
					var h = Math.floor((diff % 86400000) / 3600000);
					var m = Math.floor((diff % 3600000) / 60000);
					el.querySelector("[data-days]").textContent = d;
					el.querySelector("[data-hours]").textContent = h;
					el.querySelector("[data-minutes]").textContent = m;
				}, 60000);
			})();
		</script>';
	}

	/**
	 * Shortcode: [mtd_pricing_table levels="1,2,3" columns="3"]
	 * Displays a pricing table for membership levels
	 */
	public function pricing_table_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'levels'     => '', // Comma-separated level IDs, empty for all
			'columns'    => 3,
			'highlight'  => '', // Level ID to highlight
			'show_features' => 'yes',
		), $atts );

		$query_args = array(
			'post_type'   => 'mtd_level',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'date',
			'order'       => 'ASC',
		);

		if ( ! empty( $atts['levels'] ) ) {
			$query_args['post__in'] = array_map( 'intval', explode( ',', $atts['levels'] ) );
		}

		$levels = get_posts( $query_args );

		if ( empty( $levels ) ) {
			return '<p class="mtd-no-levels">No membership levels available.</p>';
		}

		$columns = max( 1, min( 4, intval( $atts['columns'] ) ) );
		$highlight_id = intval( $atts['highlight'] );

		ob_start();
		?>
		<div class="mtd-pricing-table mtd-pricing-cols-<?php echo esc_attr( $columns ); ?>">
			<?php foreach ( $levels as $level ) : 
				$price = get_post_meta( $level->ID, '_mtd_price', true );
				$currency = get_post_meta( $level->ID, '_mtd_currency', true ) ?: '$';
				$duration = get_post_meta( $level->ID, '_mtd_duration_days', true );
				$description = get_post_meta( $level->ID, '_mtd_description', true );
				$features = get_post_meta( $level->ID, '_mtd_features', true );
				$color = get_post_meta( $level->ID, '_mtd_badge_color', true ) ?: '#2271b1';
				$icon = get_post_meta( $level->ID, '_mtd_icon', true ) ?: 'star-filled';
				$is_highlighted = ( $level->ID === $highlight_id );
				
				// Format duration
				$duration_text = 'Lifetime';
				if ( ! empty( $duration ) && intval( $duration ) > 0 ) {
					$days = intval( $duration );
					if ( $days >= 365 ) {
						$years = floor( $days / 365 );
						$duration_text = $years . ' Year' . ( $years > 1 ? 's' : '' );
					} elseif ( $days >= 30 ) {
						$months = floor( $days / 30 );
						$duration_text = $months . ' Month' . ( $months > 1 ? 's' : '' );
					} else {
						$duration_text = $days . ' Day' . ( $days > 1 ? 's' : '' );
					}
				}
				
				// Format price
				$is_free = empty( $price ) || floatval( $price ) <= 0;
				$price_display = $is_free ? 'Free' : $currency . number_format( floatval( $price ), 2 );
			?>
				<div class="mtd-pricing-card <?php echo $is_highlighted ? 'mtd-pricing-highlighted' : ''; ?>" style="--mtd-card-color: <?php echo esc_attr( $color ); ?>;">
					<?php if ( $is_highlighted ) : ?>
						<div class="mtd-pricing-badge">Most Popular</div>
					<?php endif; ?>
					
					<div class="mtd-pricing-header">
						<div class="mtd-pricing-icon" style="background: <?php echo esc_attr( $color ); ?>;">
							<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
						</div>
						<h3 class="mtd-pricing-title"><?php echo esc_html( $level->post_title ); ?></h3>
						<?php if ( $description ) : ?>
							<p class="mtd-pricing-description"><?php echo esc_html( $description ); ?></p>
						<?php endif; ?>
					</div>
					
					<div class="mtd-pricing-price">
						<span class="mtd-pricing-amount"><?php echo esc_html( $price_display ); ?></span>
						<?php if ( ! $is_free ) : ?>
							<span class="mtd-pricing-period">/ <?php echo esc_html( $duration_text ); ?></span>
						<?php endif; ?>
					</div>
					
					<?php if ( $atts['show_features'] === 'yes' && ! empty( $features ) ) : ?>
						<ul class="mtd-pricing-features">
							<?php foreach ( array_filter( explode( "\n", $features ) ) as $feature ) : ?>
								<li><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html( trim( $feature ) ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					
					<div class="mtd-pricing-footer">
						<a href="#" class="mtd-pricing-button" style="background: <?php echo esc_attr( $color ); ?>;">
							<?php echo $is_free ? 'Get Started Free' : 'Subscribe Now'; ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		
		<style>
			.mtd-pricing-table {
				display: grid;
				gap: 25px;
				max-width: 1200px;
				margin: 30px auto;
			}
			.mtd-pricing-cols-1 { grid-template-columns: 1fr; max-width: 400px; }
			.mtd-pricing-cols-2 { grid-template-columns: repeat(2, 1fr); }
			.mtd-pricing-cols-3 { grid-template-columns: repeat(3, 1fr); }
			.mtd-pricing-cols-4 { grid-template-columns: repeat(4, 1fr); }
			@media (max-width: 900px) {
				.mtd-pricing-cols-3, .mtd-pricing-cols-4 { grid-template-columns: repeat(2, 1fr); }
			}
			@media (max-width: 600px) {
				.mtd-pricing-table { grid-template-columns: 1fr !important; }
			}
			.mtd-pricing-card {
				background: #fff;
				border: 2px solid #e5e5e5;
				border-radius: 16px;
				padding: 30px;
				text-align: center;
				position: relative;
				transition: all 0.3s ease;
			}
			.mtd-pricing-card:hover {
				transform: translateY(-5px);
				box-shadow: 0 15px 40px rgba(0,0,0,0.1);
			}
			.mtd-pricing-highlighted {
				border-color: var(--mtd-card-color);
				transform: scale(1.02);
			}
			.mtd-pricing-badge {
				position: absolute;
				top: -12px;
				left: 50%;
				transform: translateX(-50%);
				background: var(--mtd-card-color);
				color: #fff;
				padding: 5px 20px;
				border-radius: 20px;
				font-size: 12px;
				font-weight: 600;
				text-transform: uppercase;
			}
			.mtd-pricing-icon {
				width: 60px;
				height: 60px;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				margin: 0 auto 20px;
			}
			.mtd-pricing-icon .dashicons {
				font-size: 28px;
				width: 28px;
				height: 28px;
				color: #fff;
			}
			.mtd-pricing-title {
				margin: 0 0 10px;
				font-size: 1.4em;
				color: #1d2327;
			}
			.mtd-pricing-description {
				color: #646970;
				font-size: 14px;
				margin: 0;
			}
			.mtd-pricing-price {
				margin: 25px 0;
				padding: 20px 0;
				border-top: 1px solid #e5e5e5;
				border-bottom: 1px solid #e5e5e5;
			}
			.mtd-pricing-amount {
				font-size: 2.2em;
				font-weight: 700;
				color: #1d2327;
			}
			.mtd-pricing-period {
				color: #646970;
				font-size: 14px;
			}
			.mtd-pricing-features {
				list-style: none;
				padding: 0;
				margin: 0 0 25px;
				text-align: left;
			}
			.mtd-pricing-features li {
				padding: 8px 0;
				color: #1d2327;
				display: flex;
				align-items: center;
				gap: 10px;
			}
			.mtd-pricing-features .dashicons {
				color: #00a32a;
				font-size: 18px;
			}
			.mtd-pricing-button {
				display: block;
				padding: 14px 30px;
				border-radius: 8px;
				color: #fff;
				text-decoration: none;
				font-weight: 600;
				transition: all 0.2s ease;
			}
			.mtd-pricing-button:hover {
				opacity: 0.9;
				transform: translateY(-2px);
				color: #fff;
			}
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [mtd_levels_list style="simple|cards"]
	 * Simple list of membership levels
	 */
	public function levels_list_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'style' => 'simple',
		), $atts );

		$levels = get_posts( array(
			'post_type'   => 'mtd_level',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'date',
			'order'       => 'ASC',
		) );

		if ( empty( $levels ) ) {
			return '';
		}

		$output = '<ul class="mtd-levels-list mtd-levels-' . esc_attr( $atts['style'] ) . '">';
		
		foreach ( $levels as $level ) {
			$price = get_post_meta( $level->ID, '_mtd_price', true );
			$currency = get_post_meta( $level->ID, '_mtd_currency', true ) ?: '$';
			$color = get_post_meta( $level->ID, '_mtd_badge_color', true ) ?: '#2271b1';
			$icon = get_post_meta( $level->ID, '_mtd_icon', true ) ?: 'star-filled';
			
			$price_display = ( empty( $price ) || floatval( $price ) <= 0 ) 
				? 'Free' 
				: $currency . number_format( floatval( $price ), 2 );
			
			$output .= '<li>';
			$output .= '<span class="mtd-level-icon" style="background: ' . esc_attr( $color ) . ';">';
			$output .= '<span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>';
			$output .= '</span>';
			$output .= '<span class="mtd-level-name">' . esc_html( $level->post_title ) . '</span>';
			$output .= '<span class="mtd-level-price">' . esc_html( $price_display ) . '</span>';
			$output .= '</li>';
		}
		
		$output .= '</ul>';
		$output .= '<style>
			.mtd-levels-list { list-style: none; padding: 0; margin: 0; }
			.mtd-levels-simple li { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #e5e5e5; }
			.mtd-levels-simple li:last-child { border-bottom: none; }
			.mtd-level-icon { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
			.mtd-level-icon .dashicons { color: #fff; font-size: 16px; width: 16px; height: 16px; }
			.mtd-level-name { flex: 1; font-weight: 500; }
			.mtd-level-price { font-weight: 600; color: #2271b1; }
		</style>';
		
		return $output;
	}

	/**
	 * Shortcode: [mtd_login_link text="Log In"]
	 */
	public function login_link_shortcode( $atts ) {
		if ( is_user_logged_in() ) {
			return '';
		}
		
		$atts = shortcode_atts( array(
			'text'     => 'Log In',
			'redirect' => '',
			'class'    => '',
		), $atts );
		
		$redirect = ! empty( $atts['redirect'] ) ? $atts['redirect'] : get_permalink();
		$class = 'mtd-login-link' . ( ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '' );
		
		return '<a href="' . esc_url( wp_login_url( $redirect ) ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $atts['text'] ) . '</a>';
	}

	/**
	 * Shortcode: [mtd_logout_link text="Log Out"]
	 */
	public function logout_link_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		
		$atts = shortcode_atts( array(
			'text'     => 'Log Out',
			'redirect' => '',
			'class'    => '',
		), $atts );
		
		$redirect = ! empty( $atts['redirect'] ) ? $atts['redirect'] : home_url();
		$class = 'mtd-logout-link' . ( ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '' );
		
		return '<a href="' . esc_url( wp_logout_url( $redirect ) ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $atts['text'] ) . '</a>';
	}

	/**
	 * Shortcode: [mtd_account_link]
	 * Displays appropriate login or account link
	 */
	public function account_link_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'login_text'   => 'Log In',
			'account_text' => 'My Account',
			'account_url'  => '',
			'class'        => '',
		), $atts );
		
		$class = 'mtd-account-link' . ( ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '' );
		
		if ( is_user_logged_in() ) {
			$url = ! empty( $atts['account_url'] ) ? $atts['account_url'] : admin_url( 'profile.php' );
			return '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $atts['account_text'] ) . '</a>';
		}
		
		return '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $atts['login_text'] ) . '</a>';
	}
}
