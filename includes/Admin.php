<?php

namespace MTD_Membership;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_status_toggle' ) );
	}

	public function handle_status_toggle() {
		if ( ! isset( $_GET['page'] ) || 'mtd-members' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['action'], $_GET['user_id'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'mtd_toggle_status_' . $_GET['user_id'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$user_id = intval( $_GET['user_id'] );
		$action  = sanitize_text_field( $_GET['action'] );

		if ( 'deactivate' === $action ) {
			update_user_meta( $user_id, '_mtd_account_status', 'deactivated' );
		} elseif ( 'activate' === $action ) {
			update_user_meta( $user_id, '_mtd_account_status', 'active' );
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=mtd_level&page=mtd-members' ) );
		exit;
	}

	public function register_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=mtd_level',
			'Members',
			'Members',
			'manage_options',
			'mtd-members',
			array( $this, 'render_members_page' )
		);

		add_submenu_page(
			'edit.php?post_type=mtd_level',
			'Reports',
			'Reports',
			'manage_options',
			'mtd-reports',
			array( $this, 'render_reports_page' )
		);

		add_submenu_page(
			'edit.php?post_type=mtd_level',
			'Settings & Info',
			'Settings & Info',
			'manage_options',
			'mtd-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings and info page
	 */
	public function render_settings_page() {
		$plugin_data = array(
			'name'        => 'Jet Membership',
			'version'     => MTD_MEMBERSHIP_VERSION,
			'author'      => 'Malik Tanveer',
			'description' => 'A lightweight membership plugin fully compatible with JetFormBuilder.',
			'website'     => 'https://mtdtechnologies.com/',
		);
		?>
		<div class="wrap mtd-settings-wrap">
			<style>
				.mtd-settings-wrap {
					max-width: 1200px;
				}
				.mtd-settings-wrap h1 {
					font-size: 2em;
					margin-bottom: 20px;
					color: #1d2327;
				}
				.mtd-card {
					background: #fff;
					border: 1px solid #c3c4c7;
					border-radius: 8px;
					padding: 25px 30px;
					margin-bottom: 25px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.04);
				}
				.mtd-card h2 {
					margin: 0 0 20px 0;
					padding: 0 0 15px 0;
					border-bottom: 2px solid #2271b1;
					color: #1d2327;
					font-size: 1.4em;
					display: flex;
					align-items: center;
					gap: 10px;
				}
				.mtd-card h2 .dashicons {
					color: #2271b1;
					font-size: 1.2em;
				}
				.mtd-plugin-info {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
					gap: 20px;
				}
				.mtd-info-item {
					background: #f6f7f7;
					padding: 15px 20px;
					border-radius: 6px;
					border-left: 4px solid #2271b1;
				}
				.mtd-info-item label {
					display: block;
					font-size: 0.85em;
					color: #646970;
					text-transform: uppercase;
					letter-spacing: 0.5px;
					margin-bottom: 5px;
				}
				.mtd-info-item strong {
					font-size: 1.1em;
					color: #1d2327;
				}
				.mtd-shortcode-table {
					width: 100%;
					border-collapse: collapse;
					margin-top: 10px;
				}
				.mtd-shortcode-table th,
				.mtd-shortcode-table td {
					padding: 15px;
					text-align: left;
					border-bottom: 1px solid #e5e5e5;
				}
				.mtd-shortcode-table th {
					background: #f6f7f7;
					font-weight: 600;
					color: #1d2327;
				}
				.mtd-shortcode-table tr:hover {
					background: #fafafa;
				}
				.mtd-shortcode-code {
					background: #23282d;
					color: #50c878;
					padding: 8px 12px;
					border-radius: 4px;
					font-family: 'Consolas', 'Monaco', monospace;
					font-size: 0.9em;
					display: inline-block;
					white-space: nowrap;
				}
				.mtd-usage-list {
					list-style: none;
					padding: 0;
					margin: 0;
				}
				.mtd-usage-list li {
					padding: 12px 0;
					border-bottom: 1px solid #e5e5e5;
					display: flex;
					align-items: flex-start;
					gap: 15px;
				}
				.mtd-usage-list li:last-child {
					border-bottom: none;
				}
				.mtd-usage-list .step-num {
					background: #2271b1;
					color: #fff;
					width: 28px;
					height: 28px;
					border-radius: 50%;
					display: flex;
					align-items: center;
					justify-content: center;
					font-weight: bold;
					flex-shrink: 0;
				}
				.mtd-usage-list .step-content {
					flex: 1;
				}
				.mtd-usage-list .step-content strong {
					display: block;
					margin-bottom: 5px;
					color: #1d2327;
				}
				.mtd-options-list {
					margin: 10px 0 0 0;
					padding: 0;
					list-style: none;
				}
				.mtd-options-list li {
					padding: 5px 0;
					font-size: 0.9em;
					color: #646970;
				}
				.mtd-options-list code {
					background: #f0f0f1;
					padding: 2px 6px;
					border-radius: 3px;
					color: #1d2327;
				}
				.mtd-badge {
					display: inline-block;
					padding: 3px 8px;
					border-radius: 3px;
					font-size: 0.75em;
					font-weight: 600;
					text-transform: uppercase;
				}
				.mtd-badge-optional {
					background: #dcdcde;
					color: #646970;
				}
				.mtd-badge-required {
					background: #d63638;
					color: #fff;
				}
				.mtd-jfb-note {
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					color: #fff;
					padding: 20px;
					border-radius: 8px;
					margin-top: 15px;
				}
				.mtd-jfb-note h3 {
					margin: 0 0 10px 0;
					font-size: 1.1em;
				}
				.mtd-jfb-note p {
					margin: 0;
					opacity: 0.95;
				}
			</style>

			<h1><span class="dashicons dashicons-groups" style="font-size: 1.2em; margin-right: 10px;"></span>Jet Membership</h1>

			<!-- Plugin Information Card -->
			<div class="mtd-card">
				<h2><span class="dashicons dashicons-info-outline"></span>Plugin Information</h2>
				<div class="mtd-plugin-info">
					<div class="mtd-info-item">
						<label>Plugin Name</label>
						<strong><?php echo esc_html( $plugin_data['name'] ); ?></strong>
					</div>
					<div class="mtd-info-item">
						<label>Version</label>
						<strong><?php echo esc_html( $plugin_data['version'] ); ?></strong>
					</div>
					<div class="mtd-info-item">
						<label>Author</label>
						<strong><a href="<?php echo esc_url( $plugin_data['website'] ); ?>" target="_blank"><?php echo esc_html( $plugin_data['author'] ); ?></a></strong>
					</div>
					<div class="mtd-info-item">
						<label>Description</label>
						<strong><?php echo esc_html( $plugin_data['description'] ); ?></strong>
					</div>
				</div>
			</div>

			<!-- Usage Instructions Card -->
			<div class="mtd-card">
				<h2><span class="dashicons dashicons-book"></span>How to Use</h2>
				<ul class="mtd-usage-list">
					<li>
						<span class="step-num">1</span>
						<div class="step-content">
							<strong>Create Membership Levels</strong>
							Go to <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtd_level' ) ); ?>">Membership Levels</a> and create your membership tiers. Set the duration in days for each level (leave empty or 0 for lifetime access).
						</div>
					</li>
					<li>
						<span class="step-num">2</span>
						<div class="step-content">
							<strong>Manage Members</strong>
							View and manage all members from the <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtd_level&page=mtd-members' ) ); ?>">Members</a> page. You can activate or deactivate member accounts from here.
						</div>
					</li>
					<li>
						<span class="step-num">3</span>
						<div class="step-content">
							<strong>Use Shortcodes</strong>
							Restrict content on your pages using the shortcodes listed below. You can show or hide content based on membership status.
						</div>
					</li>
					<li>
						<span class="step-num">4</span>
						<div class="step-content">
							<strong>Display Member Info</strong>
							Show personalized information to logged-in members using the <code>[mtd_member_info]</code> shortcode.
						</div>
					</li>
				</ul>
			</div>

			<!-- JetFormBuilder Setup Guide Card -->
			<div class="mtd-card">
				<h2><span class="dashicons dashicons-forms"></span>JetFormBuilder Setup Guide</h2>
				
				<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 15px 20px; border-radius: 6px; margin-bottom: 25px;">
					<p style="margin: 0; display: flex; align-items: center; gap: 10px;">
						<span class="dashicons dashicons-info" style="font-size: 20px;"></span>
						<span>This plugin integrates seamlessly with JetFormBuilder to automatically assign membership when users register through your forms.</span>
					</p>
				</div>

				<h3 style="margin: 0 0 15px 0; color: #1d2327; font-size: 1.1em;">Prerequisites</h3>
				<div style="background: #f6f7f7; padding: 15px 20px; border-radius: 6px; margin-bottom: 25px; border-left: 4px solid #2271b1;">
					<ul style="margin: 0; padding-left: 20px;">
						<li>JetFormBuilder plugin must be installed and activated</li>
						<li>At least one Membership Level must be created in <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtd_level' ) ); ?>">Membership Levels</a></li>
					</ul>
				</div>

				<h3 style="margin: 0 0 15px 0; color: #1d2327; font-size: 1.1em;">Step-by-Step Setup</h3>
				<ul class="mtd-usage-list">
					<li>
						<span class="step-num">1</span>
						<div class="step-content">
							<strong>Create a New Form</strong>
							Navigate to <strong>JetFormBuilder → Add New</strong> in your WordPress admin. Give your form a descriptive name like "Membership Registration Form".
						</div>
					</li>
					<li>
						<span class="step-num">2</span>
						<div class="step-content">
							<strong>Add Required Form Fields</strong>
							Add the following essential fields to your form:
							<ul style="margin: 10px 0 0 0; padding-left: 20px; color: #646970;">
								<li><strong>Email Field</strong> - Required for user registration</li>
								<li><strong>Text Field</strong> - For username (set Field Name to <code>user_login</code>)</li>
								<li><strong>Text Field</strong> - For password (set Field Name to <code>user_pass</code>)</li>
								<li><strong>Text Field</strong> - For first name (optional, set Field Name to <code>first_name</code>)</li>
								<li><strong>Text Field</strong> - For last name (optional, set Field Name to <code>last_name</code>)</li>
								<li><strong>Submit Button</strong> - To submit the form</li>
							</ul>
						</div>
					</li>
					<li>
						<span class="step-num">3</span>
						<div class="step-content">
							<strong>Configure Post Submit Actions</strong>
							<ol style="margin: 10px 0 0 0; padding-left: 20px; color: #646970;">
								<li>In the form editor, look for the <strong>Post Submit Actions</strong> panel on the right sidebar</li>
								<li>Click <strong>+ New Action</strong></li>
								<li>Select <strong>Register User</strong> from the dropdown</li>
							</ol>
						</div>
					</li>
					<li>
						<span class="step-num">4</span>
						<div class="step-content">
							<strong>Map Form Fields to User Data</strong>
							<div style="background: #fff8e5; padding: 12px 15px; border-radius: 4px; margin-top: 10px; border-left: 4px solid #dba617;">
								<p style="margin: 0 0 8px 0; font-weight: 600; color: #1d2327;">In the Register User action settings:</p>
								<ul style="margin: 0; padding-left: 20px; color: #646970;">
									<li>Map <strong>User Email</strong> → Your email field</li>
									<li>Map <strong>User Login</strong> → Your username field</li>
									<li>Map <strong>User Password</strong> → Your password field</li>
									<li>Map <strong>First Name</strong> → Your first name field (if added)</li>
									<li>Map <strong>Last Name</strong> → Your last name field (if added)</li>
								</ul>
							</div>
							<div style="background: #f0f6fc; padding: 12px 15px; border-radius: 4px; margin-top: 10px; border-left: 4px solid #2271b1;">
								<p style="margin: 0 0 8px 0; font-weight: 600; color: #1d2327;">📌 User Meta for Membership (Add User Meta section):</p>
								<ul style="margin: 0; padding-left: 20px; color: #646970;">
									<li><code>_mtd_level_id</code> → The membership level ID (automatically set when you select a level in step 5, or can be mapped from a form field)</li>
								</ul>
							</div>
						</div>
					</li>
					<li>
						<span class="step-num">5</span>
						<div class="step-content">
							<strong>Select Membership Level</strong>
							<div style="background: #e7f5e7; padding: 12px 15px; border-radius: 4px; margin-top: 10px; border-left: 4px solid #00a32a;">
								<p style="margin: 0 0 8px 0; font-weight: 600; color: #1d2327;">🎯 This is the key integration step!</p>
								<p style="margin: 0; color: #646970;">
									In the Register User action settings, you'll find a <strong>"Membership Level"</strong> dropdown added by Jet Membership. 
									Select the membership level you want to automatically assign to users who register through this form.
								</p>
							</div>
						</div>
					</li>
					<li>
						<span class="step-num">6</span>
						<div class="step-content">
							<strong>Publish and Embed the Form</strong>
							<ol style="margin: 10px 0 0 0; padding-left: 20px; color: #646970;">
								<li>Click <strong>Publish</strong> to save your form</li>
								<li>Copy the form shortcode (e.g., <code>[jet_fb_form form_id="123"]</code>)</li>
								<li>Paste the shortcode on your registration page</li>
							</ol>
						</div>
					</li>
					<li>
						<span class="step-num">7</span>
						<div class="step-content">
							<strong>Test the Registration</strong>
							<ul style="margin: 10px 0 0 0; padding-left: 20px; color: #646970;">
								<li>Open the registration page in a private/incognito window</li>
								<li>Fill out and submit the form</li>
								<li>Go to <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtd_level&page=mtd-members' ) ); ?>">Members</a> page to verify the new user appears with the correct membership level</li>
							</ul>
						</div>
					</li>
				</ul>

				<!-- Pro Tips -->
				<div style="margin-top: 25px; padding: 20px; background: #f0f6fc; border-radius: 6px; border: 1px solid #c5d9ed;">
					<h4 style="margin: 0 0 15px 0; color: #1d2327; display: flex; align-items: center; gap: 8px;">
						<span class="dashicons dashicons-lightbulb" style="color: #2271b1;"></span>Pro Tips
					</h4>
					<ul style="margin: 0; padding-left: 20px; color: #646970;">
						<li><strong>Multiple Registration Forms:</strong> Create different forms for different membership levels (e.g., Free vs Premium registration)</li>
						<li><strong>Auto-login:</strong> Enable "Log In User" option in Register User action to automatically log users in after registration</li>
						<li><strong>Redirect After Registration:</strong> Add a "Redirect to Page" action to send users to a welcome or dashboard page</li>
						<li><strong>Payment Integration:</strong> Combine with payment gateways to create paid membership registration flows</li>
					</ul>
				</div>
			</div>

			<!-- Shortcodes Reference Card -->
			<div class="mtd-card">
				<h2><span class="dashicons dashicons-shortcode"></span>Available Shortcodes</h2>
				
				<!-- Content Restriction Shortcodes -->
				<h3 style="margin: 0 0 15px 0; color: #1d2327; font-size: 1.1em; border-bottom: 1px solid #dcdcde; padding-bottom: 10px;">
					<span class="dashicons dashicons-lock" style="color: #2271b1; margin-right: 5px;"></span>Content Restriction
				</h3>
				<table class="mtd-shortcode-table">
					<thead>
						<tr>
							<th style="width: 28%;">Shortcode</th>
							<th style="width: 47%;">Description</th>
							<th style="width: 25%;">Attributes</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<span class="mtd-shortcode-code">[mtd_is_member]...[/mtd_is_member]</span>
							</td>
							<td>
								<strong>Restrict Content to Members</strong><br>
								Content inside this shortcode will only be visible to active members.
							</td>
							<td>
								<ul class="mtd-options-list">
									<li><code>level</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br>Membership Level ID</li>
									<li><code>message</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br>Message to show non-members</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td>
								<span class="mtd-shortcode-code">[mtd_not_member]...[/mtd_not_member]</span>
							</td>
							<td>
								<strong>Show Content to Non-Members</strong><br>
								Content only visible to visitors who are NOT active members.
							</td>
							<td>
								<ul class="mtd-options-list">
									<li><code>level</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br>Check specific level</li>
								</ul>
							</td>
						</tr>
					</tbody>
				</table>

				<!-- Member Info Shortcodes -->
				<h3 style="margin: 25px 0 15px 0; color: #1d2327; font-size: 1.1em; border-bottom: 1px solid #dcdcde; padding-bottom: 10px;">
					<span class="dashicons dashicons-id" style="color: #2271b1; margin-right: 5px;"></span>Member Information
				</h3>
				<table class="mtd-shortcode-table">
					<tbody>
						<tr>
							<td style="width: 28%;">
								<span class="mtd-shortcode-code">[mtd_member_info field="..."]</span>
							</td>
							<td style="width: 47%;">
								<strong>Display Member Information</strong><br>
								Shows membership info for logged-in user.
							</td>
							<td style="width: 25%;">
								<ul class="mtd-options-list">
									<li><code>field</code> <span class="mtd-badge mtd-badge-required">Required</span><br>
										<code>level_name</code>, <code>level_id</code>, <code>expiry_date</code>, <code>status</code>, <code>status_badge</code>, <code>days_remaining</code>, <code>price</code>, <code>description</code>, <code>features</code>, <code>badge</code>
									</li>
									<li><code>format</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br>Date format</li>
									<li><code>default</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br>Default value if empty</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td>
								<span class="mtd-shortcode-code">[mtd_countdown]</span>
							</td>
							<td>
								<strong>Expiry Countdown Timer</strong><br>
								Displays a countdown timer to membership expiration.
							</td>
							<td>
								<ul class="mtd-options-list">
									<li><code>format</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br><code>full</code>, <code>compact</code>, <code>days_only</code></li>
									<li><code>expired_text</code> <span class="mtd-badge mtd-badge-optional">Optional</span></li>
									<li><code>lifetime_text</code> <span class="mtd-badge mtd-badge-optional">Optional</span></li>
								</ul>
							</td>
						</tr>
				</tbody>
				</table>

				<!-- User Action Shortcodes -->
				<h3 style="margin: 25px 0 15px 0; color: #1d2327; font-size: 1.1em; border-bottom: 1px solid #dcdcde; padding-bottom: 10px;">
					<span class="dashicons dashicons-admin-users" style="color: #2271b1; margin-right: 5px;"></span>User Actions
				</h3>
				<table class="mtd-shortcode-table">
					<tbody>
						<tr>
							<td style="width: 28%;">
								<span class="mtd-shortcode-code">[mtd_login_link]</span>
							</td>
							<td style="width: 47%;">
								<strong>Login Link</strong><br>
								Shows a login link for non-logged-in users.
							</td>
							<td style="width: 25%;">
								<ul class="mtd-options-list">
									<li><code>text</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br>Default: "Log In"</li>
									<li><code>redirect</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br>Redirect URL after login</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td>
								<span class="mtd-shortcode-code">[mtd_logout_link]</span>
							</td>
							<td>
								<strong>Logout Link</strong><br>
								Shows a logout link for logged-in users.
							</td>
							<td>
								<ul class="mtd-options-list">
									<li><code>text</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br>Default: "Log Out"</li>
									<li><code>redirect</code> <span class="mtd-badge mtd-badge-optional">Optional</span><br>Redirect URL after logout</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td>
								<span class="mtd-shortcode-code">[mtd_account_link]</span>
							</td>
							<td>
								<strong>Smart Account Link</strong><br>
								Shows login link or account link based on user state.
							</td>
							<td>
								<ul class="mtd-options-list">
									<li><code>login_text</code> <span class="mtd-badge mtd-badge-optional">Optional</span></li>
									<li><code>account_text</code> <span class="mtd-badge mtd-badge-optional">Optional</span></li>
									<li><code>account_url</code> <span class="mtd-badge mtd-badge-optional">Optional</span></li>
								</ul>
							</td>
						</tr>
					</tbody>
				</table>

				<!-- Shortcode Examples -->
				<div style="margin-top: 25px; padding: 20px; background: #f6f7f7; border-radius: 6px;">
					<h3 style="margin: 0 0 15px 0; color: #1d2327;">
						<span class="dashicons dashicons-editor-code" style="margin-right: 8px;"></span>Usage Examples
					</h3>
					
					<div style="margin-bottom: 20px;">
						<p style="margin: 0 0 8px 0; font-weight: 600;">Restrict content to members:</p>
						<pre style="background: #23282d; color: #e5e5e5; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 0;">[mtd_is_member level="123"]
    This content is only visible to Premium members.
[/mtd_is_member]</pre>
					</div>

					<div style="margin-bottom: 20px;">
						<p style="margin: 0 0 8px 0; font-weight: 600;">Show countdown timer to expiry:</p>
						<pre style="background: #23282d; color: #e5e5e5; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 0;">[mtd_countdown format="full"]</pre>
					</div>

					<div style="margin-bottom: 20px;">
						<p style="margin: 0 0 8px 0; font-weight: 600;">Display member badge with icon:</p>
						<pre style="background: #23282d; color: #e5e5e5; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 0;">[mtd_member_info field="badge"]</pre>
					</div>

					<div style="margin-bottom: 20px;">
						<p style="margin: 0 0 8px 0; font-weight: 600;">Member dashboard info:</p>
						<pre style="background: #23282d; color: #e5e5e5; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 0;">Welcome! Your membership: [mtd_member_info field="level_name"]
Status: [mtd_member_info field="status_badge"]
Expires: [mtd_member_info field="expiry_date"]
Days remaining: [mtd_member_info field="days_remaining"]</pre>
					</div>

					<div>
						<p style="margin: 0 0 8px 0; font-weight: 600;">Smart login/account link in header:</p>
						<pre style="background: #23282d; color: #e5e5e5; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 0;">[mtd_account_link login_text="Sign In" account_text="My Dashboard"]</pre>
					</div>
				</div>
			</div>

		</div>
		<?php
	}

	public function render_members_page() {
		$users = get_users( array(
			'meta_query' => array(
				array(
					'key'     => '_mtd_level_id',
					'compare' => 'EXISTS',
				),
			),
		) );

		?>
		<div class="wrap">
			<h1>Jet Membership: Members</h1>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Username</th>
						<th>Email</th>
						<th>Membership Level</th>
						<th>Expiry Date</th>
						<th>Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $users ) ) : ?>
						<tr><td colspan="6">No members found.</td></tr>
					<?php else : ?>
						<?php foreach ( $users as $user ) : 
							$level_id = get_user_meta( $user->ID, '_mtd_level_id', true );
							
							if ( empty( $level_id ) ) {
								continue;
							}

							$expiry = get_user_meta( $user->ID, '_mtd_expiry_date', true );
							$account_status = get_user_meta( $user->ID, '_mtd_account_status', true ) ?: 'active';
							$level_post = get_post( $level_id );
							$is_active = Plugin::instance()->member_manager->is_member( $user->ID );
							
							$status_label = $is_active ? 'Active' : 'Expired';
							$status_color = $is_active ? 'green' : 'red';
							
							if ( 'deactivated' === $account_status ) {
								$status_label = 'Deactivated';
								$status_color = 'orange';
							}
							?>
							<tr>
								<td><?php echo esc_html( $user->user_login ); ?></td>
								<td><?php echo esc_html( $user->user_email ); ?></td>
								<td><?php echo $level_post ? esc_html( $level_post->post_title ) : 'Unknown'; ?></td>
								<td>
									<?php 
									if ( empty( $expiry ) || $expiry === '0' ) {
										echo 'Lifetime';
									} else {
										echo esc_html( date( 'Y-m-d H:i', intval( $expiry ) ) );
									}
									?>
								</td>
								<td>
									<span style="color: <?php echo esc_attr( $status_color ); ?>; font-weight: bold;">
										<?php echo esc_html( $status_label ); ?>
									</span>
								</td>
								<td>
									<?php if ( 'deactivated' === $account_status ) : ?>
										<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'activate', 'user_id' => $user->ID ) ), 'mtd_toggle_status_' . $user->ID ) ); ?>" class="button button-small button-primary">Activate</a>
									<?php else : ?>
										<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'deactivate', 'user_id' => $user->ID ) ), 'mtd_toggle_status_' . $user->ID ) ); ?>" class="button button-small">Deactivate</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
	public function render_reports_page() {
		$member_manager = Plugin::instance()->member_manager;

		// Get all users who have a membership level
		$all_members = get_users( array(
			'meta_query' => array(
				array(
					'key'     => '_mtd_level_id',
					'compare' => 'EXISTS',
				),
			),
		) );

		$total_members   = count( $all_members );
		$active_count    = 0;
		$expired_count   = 0;
		$level_stats     = array();

		// Fetch all levels for reference
		$levels = get_posts( array(
			'post_type'   => 'mtd_level',
			'numberposts' => -1,
			'post_status' => 'publish',
		) );

		foreach ( $levels as $level ) {
			$level_stats[ $level->ID ] = array(
				'title' => $level->post_title,
				'count' => 0,
				'color' => get_post_meta( $level->ID, '_mtd_badge_color', true ) ?: '#2271b1',
			);
		}

		foreach ( $all_members as $user ) {
			$level_id = get_user_meta( $user->ID, '_mtd_level_id', true );
			
			if ( $member_manager->is_member( $user->ID ) ) {
				$active_count++;
			} else {
				$expired_count++;
			}

			if ( isset( $level_stats[ $level_id ] ) ) {
				$level_stats[ $level_id ]['count']++;
			}
		}

		// Sort level stats by count
		uasort( $level_stats, function($a, $b) {
			return $b['count'] - $a['count'];
		});

		// Get recent registrations (last 10)
		$recent_members = get_users( array(
			'meta_query' => array(
				array(
					'key'     => '_mtd_level_id',
					'compare' => 'EXISTS',
				),
			),
			'orderby'  => 'ID',
			'order'    => 'DESC',
			'number'   => 10,
		) );

		?>
		<div class="wrap mtd-reports-wrap">
			<style>
				.mtd-reports-wrap { max-width: 1200px; margin-top: 20px; }
				.mtd-reports-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; }
				.mtd-reports-header h1 { margin: 0; font-size: 2.2em; font-weight: 700; color: #1d2327; }
				
				.mtd-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
				.mtd-stat-card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid #2271b1; transition: transform 0.2s; }
				.mtd-stat-card:hover { transform: translateY(-5px); }
				.mtd-stat-label { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #646970; margin-bottom: 10px; display: block; }
				.mtd-stat-value { font-size: 36px; font-weight: 800; color: #1d2327; }
				.mtd-stat-card.active { border-left-color: #00a32a; }
				.mtd-stat-card.expired { border-left-color: #d63638; }
				.mtd-stat-card.levels { border-left-color: #72aee6; }

				.mtd-reports-row { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
				.mtd-reports-card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
				.mtd-reports-card h2 { margin-top: 0; font-size: 1.5em; border-bottom: 1px solid #f0f0f1; padding-bottom: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
				
				.mtd-level-distribution { list-style: none; padding: 0; margin: 0; }
				.mtd-level-row { margin-bottom: 15px; }
				.mtd-level-info { display: flex; justify-content: space-between; margin-bottom: 8px; font-weight: 600; }
				.mtd-level-bar-bg { background: #f0f0f1; height: 10px; border-radius: 5px; overflow: hidden; }
				.mtd-level-bar-fill { height: 100%; border-radius: 5px; transition: width 1s ease-out; }

				.mtd-recent-table { width: 100%; border-collapse: collapse; }
				.mtd-recent-table th { text-align: left; padding: 12px; border-bottom: 2px solid #f0f0f1; color: #646970; font-weight: 600; }
				.mtd-recent-table td { padding: 12px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
				.mtd-user-cell { display: flex; align-items: center; gap: 10px; }
				.mtd-avatar { width: 32px; height: 32px; border-radius: 50%; }
				
				@media (max-width: 900px) { .mtd-reports-row { grid-template-columns: 1fr; } }
			</style>

			<div class="mtd-reports-header">
				<h1><span class="dashicons dashicons-chart-area" style="font-size: 1em; width: auto; height: auto;"></span> Analytics Reports</h1>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtd_level&page=mtd-members' ) ); ?>" class="button button-primary button-large">Manage All Members</a>
			</div>

			<div class="mtd-stats-grid">
				<div class="mtd-stat-card">
					<span class="mtd-stat-label">Total Members</span>
					<div class="mtd-stat-value"><?php echo number_format( $total_members ); ?></div>
				</div>
				<div class="mtd-stat-card active">
					<span class="mtd-stat-label">Active Members</span>
					<div class="mtd-stat-value"><?php echo number_format( $active_count ); ?></div>
				</div>
				<div class="mtd-stat-card expired">
					<span class="mtd-stat-label">Inactive / Expired</span>
					<div class="mtd-stat-value"><?php echo number_format( $expired_count ); ?></div>
				</div>
				<div class="mtd-stat-card levels">
					<span class="mtd-stat-label">Membership Levels</span>
					<div class="mtd-stat-value"><?php echo number_format( count( $levels ) ); ?></div>
				</div>
			</div>

			<div class="mtd-reports-row">
				<div class="mtd-reports-card">
					<h2><span class="dashicons dashicons-calendar-alt"></span> Recent Registrations</h2>
					<table class="mtd-recent-table">
						<thead>
							<tr>
								<th>Member</th>
								<th>Level</th>
								<th>Joined Date</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $recent_members ) ) : ?>
								<tr><td colspan="4">No registrations found yet.</td></tr>
							<?php else : ?>
								<?php foreach ( $recent_members as $user ) : 
									$level_id = get_user_meta( $user->ID, '_mtd_level_id', true );
									$level_title = isset( $level_stats[ $level_id ] ) ? $level_stats[ $level_id ]['title'] : 'Unknown';
									$start_date = get_user_meta( $user->ID, '_mtd_start_date', true );
									$is_active = $member_manager->is_member( $user->ID );
									
									// Fallback date if meta is missing
									$display_date = ! empty( $start_date ) 
										? date_i18n( get_option( 'date_format' ), intval( $start_date ) ) 
										: date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) );
								?>
									<tr>
										<td>
											<div class="mtd-user-cell">
												<?php echo get_avatar( $user->ID, 32, '', '', array( 'class' => 'mtd-avatar' ) ); ?>
												<div>
													<strong><?php echo esc_html( $user->display_name ); ?></strong><br>
													<small style="color: #646970;"><?php echo esc_html( $user->user_email ); ?></small>
												</div>
											</div>
										</td>
										<td>
											<span class="mtd-badge" style="background: <?php echo esc_attr( isset( $level_stats[ $level_id ] ) ? $level_stats[ $level_id ]['color'] : '#2271b1' ); ?>; color: #fff;">
												<?php echo esc_html( $level_title ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $display_date ); ?></td>
										<td>
											<?php if ( $is_active ) : ?>
												<span style="color: #00a32a; font-weight: 600;">● Active</span>
											<?php else : ?>
												<span style="color: #d63638; font-weight: 600;">● Expired</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<div class="mtd-reports-card">
					<h2><span class="dashicons dashicons-chart-pie"></span> Level Distribution</h2>
					<div class="mtd-level-distribution">
						<?php foreach ( $level_stats as $id => $data ) : 
							$percentage = $total_members > 0 ? ( $data['count'] / $total_members ) * 100 : 0;
						?>
							<div class="mtd-level-row">
								<div class="mtd-level-info">
									<span><?php echo esc_html( $data['title'] ); ?></span>
									<span><?php echo $data['count']; ?> <small style="font-weight: normal; color: #646970;">(<?php echo round($percentage); ?>%)</small></span>
								</div>
								<div class="mtd-level-bar-bg">
									<div class="mtd-level-bar-fill" style="width: <?php echo esc_attr( $percentage ); ?>%; background: <?php echo esc_attr( $data['color'] ); ?>;"></div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
