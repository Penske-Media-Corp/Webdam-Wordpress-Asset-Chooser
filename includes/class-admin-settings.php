<?php

namespace Webdam;

/**
 * WebDAM Admin Settings Page (Settings > WebDAM)
 */
class Admin {

	/**
	 * @var Used to store an internal reference for the class
	 */
	private static $_instance;

	/**
	 * Fetch THE instance of the admin object
	 *
	 * @param null
	 *
	 * @return Admin object instance
	 */
	static function get_instance( ) {

		if ( empty( static::$_instance ) ){

			self::$_instance = new self();
		}

		// Return the single/cached instance of the class
		return self::$_instance;
	}

	/**
	 * Set WordPress hooks
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function __construct() {

		// Create the Settings > Webdam page
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'create_settings_page_elements' ) );

		// Enqueue styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

		// Display a notice when credentials are needed
		if ( ! \webdam_get_settings() ) {
			add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
		}
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function wp_enqueue_scripts() {

		// Only enqueue these items on our settings page
		if ( ! empty( $_GET['page'] ) ) {

			if ( 'webdam-settings' === $_GET['page'] ) {

				// Enqueue the webdam admin JS
				wp_enqueue_script(
					'webdam-admin-settings',
					WEBDAM_PLUGIN_URL . 'assets/admin-settings.js',
					array( 'jquery' ),
					false,
					false
				);

				// Enqueue the webdam admin CSS
				wp_enqueue_style(
					'webdam-admin-settings',
					WEBDAM_PLUGIN_URL . 'assets/admin-settings.css',
					array(),
					false,
					'screen'
				);
			}
		}
	}

	/**
	 * Show a notice to admin users to update plugin options
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function show_admin_notice() {
		/*
		 * We want to show notice only to those users who can update options,
		 * for everyone else the notice won't mean much if anything.
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		} ?>

		<div class="error">
			<p>
				<strong>
					Please update the <a href="<?php echo esc_url( admin_url( 'options-general.php?page=webdam-settings' ) ) ?>">WebDAM Settings</a> with your information.
				</strong>
			</p>
		</div><?php
	}

	/**
	 * Create the settings page
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function add_plugin_page() {

		add_options_page(
			'WebDAM Settings',
			'WebDAM',
			'manage_options',
			'webdam-settings',
			array( $this, 'create_settings_page' )
		);
	}

	/**
	 * Register our setting
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function create_settings_page_elements() {

		/**
		 * Register the webdam_settings setting
		 */
		register_setting(
			'webdam_settings',
			'webdam_settings',
			array( $this, 'webdam_settings_input_sanitization' )
		);
	}

	/**
	 * Create the settings page contents/form fields
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function create_settings_page() {

		// Set some default items
		$api_status_text = __( 'API NOT Authenticated', 'webdam' );
		$api_status_class = 'not-authenticated';

		// Fetch our existing settings
		$settings = get_option( 'webdam_settings' );

		// Determine if we're authenticated or not
		if ( \webdam_is_authenticated() ) {
			$api_status_text = __( 'API Authenticated', 'webdam' );
			$api_status_class = 'authenticated';
		} ?>
		
		<div class="webdam-settings wrap <?php echo esc_attr( $api_status_class ); ?>">
			<h2><?php echo esc_html_e( 'WebDAM Settings', 'webdam' ); ?></h2>
			<form method="post" action="options.php"><?php

				// This prints out all hidden setting fields
				settings_fields( 'webdam_settings' ); ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="webdam_account_domain"><?php esc_html_e( 'Domain', 'webdam' ); ?></label>
							</th>
							<td>
								<input
									type="text"
									id="webdam_account_domain"
									name="webdam_settings[webdam_account_domain]"
									value="<?php echo ! empty( $settings['webdam_account_domain'] ) ? esc_attr( $settings['webdam_account_domain'] ) : ''; ?>"
									placeholder="yourdomain.webdamdb.com">
							</td>
						</tr><tr id="api-status-row">
							<th scope="row">
								<label for="webdam_enable_api"><?php esc_html_e( 'API Status', 'webdam' ); ?></label>
							</th><td>
								<p class="api-authentication-status">
									<span class="<?php echo esc_attr( $api_status_class ); ?>">
										<?php echo esc_html( $api_status_text ); ?>
									</span>
								</p><?php

								// Display link to authenticate if needed
								if ( \webdam_is_authenticated() ) {

									// @todo button to manually refresh token?

									// @todo button to test API?

								} else {
									// Once we have client_id/secret show the api auth_code link
									if ( empty( $settings['api_client_secret'] ) || empty( $settings['api_client_id'] ) ) {

										printf(
											'<p>%s<p><p><a target="_blank" href="%s" title="%s">%s</a></p>',
											esc_html__( 'Enter your WebDAM Client ID and Secret Keys below.', 'webdam' ),
											esc_url( 'http://webdam.com/DAM-software/API/' ),
											esc_attr__( 'Obtain your API keys', 'webdam' ),
											esc_html__( 'Click here to obtain your keys.', 'webdam' )
										);
									} else {
										// Display the authorization link
										// this link takes user to webdam to login and authorize our API
										printf(
											'<p><a href="%s" title="%s" class="%s">%s</a></p>',
											esc_url( \webdam_get_authorization_url() ),
											esc_attr__( 'Authorize WebDAM', 'webdam' ),
											esc_attr( 'authorization-url' ),
											esc_html__( 'Click here to authorize API access to your WebDAM account.', 'webdam' )
										);
										// Display a notice for the user to enter their api keys
									}
								} ?>
							</td>
						</tr><tr id="api-client-id-row">
							<th scope="row"><?php esc_html_e( 'API Client ID', 'webdam' ); ?></th>
							<td>
								<input
									type="text"
									id="api_client_id"
									name="webdam_settings[api_client_id]"
									value="<?php echo ! empty( $settings['api_client_id'] ) ? esc_attr( $settings['api_client_id'] ) : ''; ?>">
							</td>
						</tr><tr id="api-client-secret-row">
							<th scope="row"><?php esc_html_e( 'API Client Secret', 'webdam' ); ?></th>
							<td>
								<input
									type="text"
									id="api_client_secret"
									name="webdam_settings[api_client_secret]"
									value="<?php echo ! empty( $settings['api_client_secret'] ) ? esc_attr( $settings['api_client_secret'] ) : ''; ?>">
							</td>
						</tr>
					</tbody>
				</table><?php

				submit_button(); ?>

			</form>
		</div><?php

	}

	/**
	 * Sanitize each setting field as it's saved
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public function webdam_settings_input_sanitization( $input ) {
		$new_settings = array();

		// @todo encrypt the piss outta this stuff for storage
		// ...but in a way we can still retrieve values for sending auth
		// to webdam in the api.

		if( isset( $input['webdam_account_domain'] ) ) {
			$new_settings['webdam_account_domain'] = sanitize_text_field( $input['webdam_account_domain'] );
		}

		if( isset( $input['api_client_id'] ) ) {
			$new_settings['api_client_id'] = sanitize_text_field( $input['api_client_id'] );
		}

		if( isset( $input['api_client_secret'] ) ) {
			$new_settings['api_client_secret'] = sanitize_text_field( $input['api_client_secret'] );
		}

		// Broadcast that changes are being saved
		if ( ! empty( $new_settings ) ) {
			do_action( 'webdam-saved-new-settings' );
		}

		return $new_settings;
	}
}

Admin::get_instance();

// EOF