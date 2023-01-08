<?php
/**
 * WPDK License - Client
 */

namespace WPDK;

use WP_REST_Request;

class License {

	protected $client;
	protected $cache_key;
	protected $data;
	public $plugin_version = '1.0.0';
	protected $plugin_file = null;
	public $plugin_basename = null;
	protected $option_key = null;
	protected $menu_args = array();
	protected $license_page_url = null;

	/**
	 * License constructor.
	 */
	function __construct( Client $client, $plugin_file ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		if ( ! empty( $plugin_file ) ) {

			$plugin_data           = get_plugin_data( $plugin_file );
			$this->client          = $client;
			$this->plugin_file     = $plugin_file;
			$this->plugin_basename = plugin_basename( $plugin_file );
			$this->plugin_version  = Utils::get_args_option( 'Version', $plugin_data );
			$this->option_key      = sprintf( 'pb_%s_license_data', md5( $this->client->text_domain . esc_attr( '-pro' ) ) );
			$this->cache_key       = sprintf( 'pb_%s_version_info', md5( $this->client->text_domain . esc_attr( '-pro' ) ) );
			$this->data            = get_option( $this->option_key, array() );

			add_action( 'init', array( $this, 'schedule_event' ) );
			add_action( 'rest_api_init', array( $this, 'add_license_activation_endpoint' ) );
			add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
			add_action( 'pb_license_check', array( $this, 'check_license_validity' ) );
		}
	}

	/**
	 * check the license validity.
	 * @return void
	 */
	function check_license_validity() {
		$response = $this->license_api_request( array() );

		if ( $response instanceof \WP_Error ) {
			$this->flush_license_data();

			return;
		}

		$registered_domains  = isset( $response['registered_domains'] ) ? Utils::get_args_option( 'registered_domains', $response ) : array();
		$site_url            = $this->client->get_website_url();
		$_registered_domains = array_column( $registered_domains, 'registered_domain' );

		if ( isset( $_registered_domains ) && ! in_array( $site_url, $_registered_domains ) ) {
			$this->flush_license_data();
		}

	}

	/**
	 *check the crone schedule and  fire the crone hook.
	 *
	 * @return void
	 */
	function schedule_event() {
		if ( ! wp_next_scheduled( 'pb_license_check' ) ) {
			wp_schedule_event( time(), 'daily', 'pb_license_check' );
		}
	}

	/**
	 * Adds a custom cron schedule for every day.
	 *
	 * @param array $schedules An array of non-default cron schedules.
	 *
	 * @return array of non-default cron schedules.
	 */
	function add_cron_interval( $schedules ) {
		if ( ! isset( $schedules['daily'] ) ) {
			$schedules['daily'] = array(
				'interval' => 24 * HOUR_IN_SECONDS,
				'display'  => esc_html__( 'Daily' ),
			);
		}

		return $schedules;
	}


	/**
	 * Handle license activation endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|\WP_REST_Response
	 */
	function handle_activation_endpoint( WP_REST_Request $request ) {

		$params = $request->get_body_params();

		if ( empty( $license_data = Utils::get_args_option( 'license_data', $params ) ) ) {
			return new \WP_REST_Response( array( 'code' => 404, 'message' => esc_html__( 'License data not found.', $this->client->text_domain ) ) );
		}

		update_option( $this->option_key, $license_data );

		return true;
	}


	function add_license_activation_endpoint() {
		register_rest_route( 'wpdk', '/activate_license', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_activation_endpoint' ),
			'permission_callback' => function () {
				return '';
			},
		) );
	}


	/**
	 * Check for Update for this specific project
	 *
	 * @param $transient_data
	 *
	 * @return mixed
	 */
	public function check_plugin_update( $transient_data ) {
		global $pagenow;

		if ( ! is_object( $transient_data ) ) {
			$transient_data = new \stdClass;
		}

		if (
//			( current_time( 'U' ) - get_option( $this->cache_key . '_last_checked' ) < 5 ) ||
			( 'plugins.php' == $pagenow && is_multisite() ) ||
			( ! empty( $transient_data->response ) && ! empty( $transient_data->response[ $this->plugin_basename ] ) )
		) {
			return $transient_data;
		}

		$version_info = $this->get_version_info();

		if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {

			if ( isset( $version_info->sections ) ) {
				unset( $version_info->sections );
			}

			if ( version_compare( $this->plugin_version, $version_info->new_version, '<' ) ) {
				$transient_data->response[ $this->plugin_basename ] = $version_info;
			} else {
				$transient_data->no_update[ $this->plugin_basename ] = $version_info;
			}

			$transient_data->last_checked                      = time();
			$transient_data->checked[ $this->plugin_basename ] = $this->plugin_version;

			update_option( $this->cache_key . '_last_checked', current_time( 'U' ) );
		}

		return $transient_data;
	}


	/**
	 * Get version information
	 */
	private function get_version_info() {
		$version_info = $this->get_cached_version_info();

		if ( false === $version_info ) {
			$version_info = $this->get_project_latest_version();
			$this->set_cached_version_info( $version_info );
		}

		return $version_info;
	}


	/**
	 * Get latest version information from server
	 *
	 * @return array
	 */
	function get_project_latest_version() {

		$response = $this->client->send_request( 'plugin/' . $this->get_license_data( 'license_key' ), array(), false, false, false );

		if ( isset( $response->icons ) ) {
			$response->icons = (array) $response->icons;
		}

		if ( isset( $response->banners ) ) {
			$response->banners = (array) $response->banners;
		}

		if ( isset( $response->sections ) ) {
			$response->sections = (array) $response->sections;
		}

		return $response;
	}


	/**
	 * Set version info to database
	 *
	 * @param $value
	 */
	private function set_cached_version_info( $value ) {
		if ( $value ) {
			set_transient( $this->cache_key, $value, 3 * HOUR_IN_SECONDS );
		}
	}


	/**
	 * Get version info from database
	 *
	 * @return false|mixed
	 */
	private function get_cached_version_info() {
		global $pagenow;

		if ( 'update-core.php' == $pagenow ) {
			return false;
		}

		$value = get_transient( $this->cache_key );

		if ( ! $value && ! isset( $value->name ) ) {
			return false;
		}

		if ( isset( $value->icons ) ) {
			$value->icons = (array) $value->icons;
		}

		if ( isset( $value->banners ) ) {
			$value->banners = (array) $value->banners;
		}

		if ( isset( $value->sections ) ) {
			$value->sections = (array) $value->sections;
		}

		return $value;
	}


	/**
	 * Add License action link on pro plugin actions
	 *
	 * @param $links
	 *
	 * @return array
	 */
	function add_plugin_action_links( $links ) {

		return array_merge( array(
			'license' => sprintf( '<a href="%s">%s</a>', $this->license_page_url, esc_html__( 'License', $this->client->text_domain ) ),
		), $links );
	}


	/**
	 * Send message if License is not valid
	 */
	function license_activation_notices() {

		if ( $this->is_valid() || ( isset( $_GET['page'] ) && sanitize_text_field( $_GET['page'] == $this->menu_args['menu_slug'] ) ) ) {
			return;
		}

		$license_message = sprintf( __( '<p>You must activate <strong>%s</strong> to unlock the premium features, enable single-click download, and etc. Dont have your key? <a href="%s" target="_blank">Your license keys</a></p><p><a class="button-primary" href="%s">Activate License</a></p>' ),
			$this->client->plugin_name, sprintf( '%s/my-account/license-keys/', $this->client->integration_server ), $this->license_page_url
		);

		$this->client->print_notice( $license_message, 'warning' );
	}


	/**
	 * Add menu page for license page
	 *
	 * @param array $args
	 */
	public function add_settings_page( $args = array() ) {

		$defaults = array(
			'type'        => 'submenu', // Can be: menu, options, submenu
			'page_title'  => sprintf( __( 'Manage License - %s', $this->client->text_domain ), $this->client->plugin_name ),
			'menu_title'  => __( 'Manage License', $this->client->text_domain ),
			'capability'  => 'manage_options',
			'menu_slug'   => $this->client->text_domain . '-manage-license',
			'position'    => null,
			'icon_url'    => '',
			'parent_slug' => '',
		);

		$this->menu_args = wp_parse_args( $args, $defaults );

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 999 );
		add_action( 'admin_notices', array( $this, 'license_activation_notices' ) );
		add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'add_plugin_action_links' ), 10, 1 );
	}


	/**
	 * Add admin Menu
	 */
	public function admin_menu() {
		switch ( $type = Utils::get_args_option( 'type', $this->menu_args ) ) {
			case 'menu':
				call_user_func( 'add_' . $type . '_page',
					$this->menu_args['page_title'],
					$this->menu_args['menu_title'],
					$this->menu_args['capability'],
					$this->menu_args['menu_slug'],
					array( $this, 'render_license_page' ),
					$this->menu_args['icon_url'],
					$this->menu_args['position']
				);
				break;

			case 'submenu':
				call_user_func( 'add_' . $type . '_page',
					$this->menu_args['parent_slug'],
					$this->menu_args['page_title'],
					$this->menu_args['menu_title'],
					$this->menu_args['capability'],
					$this->menu_args['menu_slug'],
					array( $this, 'render_license_page' ),
					$this->menu_args['position']
				);
				break;

			case 'options':
				call_user_func( 'add_' . $type . '_page',
					$this->menu_args['page_title'],
					$this->menu_args['menu_title'],
					$this->menu_args['capability'],
					$this->menu_args['menu_slug'],
					array( $this, 'render_license_page' ),
					$this->menu_args['position']
				);
				break;
		}

		$this->license_page_url = menu_page_url( $this->menu_args['menu_slug'], false );
	}


	/**
	 * Render License page
	 */
	public function render_license_page() {

		if ( isset( $_POST['submit'] ) ) {
			$this->process_form_submission();
		}

		$this->render_licenses_style();

		$get_string         = array_map( 'sanitize_text_field', $_GET );
		$script_name        = sanitize_text_field( $_SERVER['SCRIPT_NAME'] );
		$license_form_url   = add_query_arg( $get_string, admin_url( basename( $script_name ) ) );
		$license_action     = $this->is_valid() ? 'slm_deactivate' : 'slm_activate';
		$license_readonly   = $this->is_valid() ? 'readonly="readonly"' : '';
		$license_submit_btn = $this->is_valid() ? __( 'Deactivate License', $this->client->text_domain ) : __( 'Activate License', $this->client->text_domain );

		?>
        <div class="wrap pb-license-settings-wrapper">
            <h1>
				<?php printf( __( 'License settings for <strong>%s</strong>', $this->client->text_domain ), $this->client->plugin_name ); ?>
				<?php printf( __( '<sub style="font-size: 12px; vertical-align: middle;">%s</sub>' ), $this->plugin_version ); ?>
            </h1>

            <div class="pb-license-settings action-<?php echo esc_attr( $license_action ); ?>">

                <div class="pb-license-title">
                    <svg viewBox="0 0 300 300" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                        <path d="m150 161.48c-8.613 0-15.598 6.982-15.598 15.598 0 5.776 3.149 10.807 7.817 13.505v17.341h15.562v-17.341c4.668-2.697 7.817-7.729 7.817-13.505 0-8.616-6.984-15.598-15.598-15.598z"/>
                        <path d="m150 85.849c-13.111 0-23.775 10.665-23.775 23.775v25.319h47.548v-25.319c-1e-3 -13.108-10.665-23.775-23.773-23.775z"/>
                        <path d="m150 1e-3c-82.839 0-150 67.158-150 150 0 82.837 67.156 150 150 150s150-67.161 150-150c0-82.839-67.161-150-150-150zm46.09 227.12h-92.173c-9.734 0-17.626-7.892-17.626-17.629v-56.919c0-8.491 6.007-15.582 14.003-17.25v-25.697c0-27.409 22.3-49.711 49.711-49.711 27.409 0 49.709 22.3 49.709 49.711v25.697c7.993 1.673 14 8.759 14 17.25v56.919h2e-3c0 9.736-7.892 17.629-17.626 17.629z"/>
                    </svg>
                    <span><?php esc_html_e( 'Manage License', $this->client->text_domain ); ?></span>
                </div>

                <div class="pb-license-details">
                    <p>
                        <label for="pb-license-field">
							<?php printf( __( 'Activate or Deactivate <strong>%s</strong> by your license key to get support and automatic update from your WordPress dashboard.' ), $this->client->plugin_name ); ?>
                        </label>
                    </p>
                    <form method="post" action="<?php echo esc_url_raw( $license_form_url ); ?>" novalidate="novalidate" spellcheck="false">
                        <input type="hidden" name="license_action" value="<?php echo esc_attr( $license_action ); ?>">
						<?php wp_nonce_field( $this->license_nonce() ); ?>
                        <div class="license-input-fields">
                            <div class="license-input-key">
                                <svg viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                                    <path d="m463.75 48.251c-64.336-64.336-169.01-64.335-233.35 1e-3 -43.945 43.945-59.209 108.71-40.181 167.46l-185.82 185.82c-2.813 2.813-4.395 6.621-4.395 10.606v84.858c0 8.291 6.709 15 15 15h84.858c3.984 0 7.793-1.582 10.605-4.395l21.211-21.226c3.237-3.237 4.819-7.778 4.292-12.334l-2.637-22.793 31.582-2.974c7.178-0.674 12.847-6.343 13.521-13.521l2.974-31.582 22.793 2.651c4.233 0.571 8.496-0.85 11.704-3.691 3.193-2.856 5.024-6.929 5.024-11.206v-27.929h27.422c3.984 0 7.793-1.582 10.605-4.395l38.467-37.958c58.74 19.043 122.38 4.929 166.33-39.046 64.336-64.335 64.336-169.01 0-233.35zm-42.435 106.07c-17.549 17.549-46.084 17.549-63.633 0s-17.549-46.084 0-63.633 46.084-17.549 63.633 0 17.548 46.084 0 63.633z"/>
                                </svg>
                                <input <?php echo esc_attr( $license_readonly ); ?>
                                        type="text"
                                        name="license_key"
                                        id="pb-license-field"
                                        autocomplete="off"
                                        value="<?php echo esc_attr( $this->get_license_key_for_input_field( $license_action ) ); ?>"
                                        placeholder="<?php echo esc_attr( __( 'Enter your license key to activate', $this->client->text_domain ) ); ?>"/>
                            </div>
                            <button type="submit" name="submit"><?php echo esc_html( $license_submit_btn ); ?></button>
                        </div>
                    </form>
                    <p>
						<?php printf( __( 'Find your %s and %s latest version from your account.', $this->client->text_domain ),
							sprintf( '<a target="_blank" href="%s/my-account/license-keys/"><strong>%s</strong></a>', $this->client->integration_server, esc_html__( 'License keys', $this->client->text_domain ) ),
							sprintf( '<a target="_blank" href="%s/my-account/downloads/"><strong>%s</strong></a>', $this->client->integration_server, esc_html__( 'Download', $this->client->text_domain ) )
						); ?>
                    </p>
                </div>
            </div>
        </div>
		<?php
	}


	/**
	 * Process license form submission
	 */
	function process_form_submission() {

		if ( ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '', $this->license_nonce() ) ) {
			return;
		}

		$license_key    = isset( $_POST['license_key'] ) ? trim( sanitize_text_field( $_POST['license_key'] ) ) : '';
		$license_key    = str_replace( ' ', '', $license_key );
		$license_action = isset( $_POST['license_action'] ) ? sanitize_text_field( $_POST['license_action'] ) : '';

		if ( empty( $license_key ) || empty( $license_action ) ) {
			$this->client->print_notice( sprintf( '<p>%s</p>', __( 'Invalid license key', $this->client->text_domain ) ), 'error' );

			return;
		}

		// Sending request to server
		$api_params = array(
			'slm_action'        => $license_action,
			'registered_domain' => $this->client->get_website_url(),
		);

		if ( $license_action == 'slm_activate' ) {

			// add license key to the api param
			$api_params['license_key'] = $license_key;

			// Settings license to the data
			$this->data = array( 'license_key' => $license_key );
		}

		$api_response = $this->license_api_request( $api_params );

		if ( $api_response instanceof \WP_Error ) {

			$this->client->print_notice( sprintf( '<p>%s</p>', $api_response->get_error_message() ), 'error' );
			$this->flush_license_data();

			return;
		}

		$message = Utils::get_args_option( 'message', $api_response );

		if ( $this->is_error( $api_response ) ) {

			$this->client->print_notice( sprintf( '<p>%s</p>', $message ), 'error' );

			if ( Utils::get_args_option( 'error_code', $api_response ) == 80 ) {
				$this->flush_license_data();
			}

			return;
		}

		$this->client->print_notice( sprintf( '<p>%s</p>', $api_response['message'] ) );

		$this->data = $api_response;
		update_option( $this->option_key, $api_response );
	}


	/**
	 * Send request to license server
	 *
	 * @param array $api_params
	 *
	 * @return array|mixed
	 */
	private function license_api_request( $api_params = array() ) {

		$defaults = array(
			'slm_action'     => 'slm_check',
			'secret_key'     => $this->client->license_secret_key,
			'license_key'    => $this->get_license_data( 'license_key' ),
			'item_reference' => $this->client->plugin_reference,
		);

		$api_params = wp_parse_args( $api_params, $defaults );
		$api_query  = esc_url_raw( add_query_arg( $api_params, $this->client->integration_server ) );
		$response   = wp_remote_post( $api_query, array( 'timeout' => 30, 'sslverify' => false ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}


	/**
	 * Return bool if response is error or not
	 *
	 * @param array $response
	 *
	 * @return bool
	 */
	private function is_error( $response = array() ) {
		if ( is_array( $response ) && isset( $response['result'] ) && $response['result'] !== 'success' ) {
			return true;
		}

		return false;
	}


	/**
	 * Return license key for input field
	 *
	 * @param $license_action
	 *
	 * @return string
	 */
	private function get_license_key_for_input_field( $license_action ) {

		$license_key = $this->get_license_data( 'license_key' );
		$key_length  = strlen( $license_key );

		if ( $license_action == 'slm_activate' ) {
			return '';
		}

		return str_pad(
			substr( $license_key, 0, $key_length / 2 ), $key_length, '*'
		);
	}


	/**
	 * Return license nonce action
	 *
	 * @return string
	 */
	private function license_nonce() {
		return sprintf( 'pb_license_%s', str_replace( '-', '_', $this->client->text_domain ) );
	}


	/**
	 * Check if license key is activated in this website or not
	 *
	 * @return bool
	 */
	public function is_valid() {
		return is_array( $registered_domains = $this->get_registered_domains() ) && in_array( $this->client->get_website_url(), $registered_domains );
	}


	/**
	 * Flush license data
	 */
	private function flush_license_data() {
		delete_option( $this->option_key );
	}


	/**
	 * Return array of registered domains for this license key
	 *
	 * @return array
	 */
	private function get_registered_domains() {

		$registered_domains = $this->get_license_data( 'registered_domains' );

		if ( ! is_array( $registered_domains ) ) {
			return array();
		}

		$registered_domains = array_map( function ( $domain ) {
			if ( isset( $domain['registered_domain'] ) ) {
				return $domain['registered_domain'];
			}

			return '';
		}, $registered_domains );

		return array_filter( $registered_domains );
	}


	/**
	 * Return license data
	 *
	 * @param $retrieve_data
	 *
	 * @return false|mixed|void
	 */
	public function get_license_data( $retrieve_data = '' ) {

		$license_data = $this->data;

		if ( empty( $retrieve_data ) ) {
			return $license_data;
		}

		return isset( $license_data[ $retrieve_data ] ) ? $license_data[ $retrieve_data ] : '';
	}


	/**
	 * Render css for license form
	 */
	private function render_licenses_style() {
		?>
        <style type="text/css">

            .pb-license-settings-wrapper h1 {
                margin-bottom: 30px;
            }

            .pb-license-settings {
                background-color: #fff;
                box-shadow: 0 3px 10px rgba(16, 16, 16, 0.05);
                width: 100%;
                max-width: 1100px;
                min-height: 1px;
                box-sizing: border-box;
            }

            .pb-license-settings a {
                text-decoration: none;
            }

            .pb-license-settings * {
                box-sizing: border-box;
            }

            .pb-license-title {
                background-color: #F8FAFB;
                border-bottom: 2px solid #EAEAEA;
                display: flex;
                align-items: center;
                padding: 10px 20px;
            }

            .pb-license-title svg {
                width: 30px;
                height: 30px;
                fill: #00bcd4;
            }

            .pb-license-title span {
                font-size: 17px;
                color: #444444;
                margin-left: 10px;
            }

            .pb-license-details {
                padding: 20px;
            }

            .pb-license-details p {
                font-size: 15px;
                margin: 0 0 20px 0;
            }

            .license-input-key {
                position: relative;
                flex: 0 0 72%;
                max-width: 72%;
            }

            .license-input-key input {
                background-color: #F9F9F9;
                padding: 10px 15px 10px 48px;
                border: 1px solid #E8E5E5;
                border-radius: 3px;
                height: 45px;
                font-size: 16px;
                color: #71777D;
                width: 100%;
                box-shadow: 0 0 0 transparent;
            }

            .license-input-key input:focus {
                outline: 0 none;
                border: 1px solid #E8E5E5;
                box-shadow: 0 0 0 transparent;
            }

            .license-input-key svg {
                width: 22px;
                height: 22px;
                fill: #00bcd4;
                position: absolute;
                left: 14px;
                top: 13px;
            }

            .action-slm_deactivate .pb-license-title svg,
            .action-slm_deactivate .license-input-key svg {
                fill: #E40055;
            }

            .license-input-fields {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
                max-width: 850px;
                width: 100%;
            }

            .license-input-fields button {
                color: #fff;
                font-size: 17px;
                padding: 8px;
                height: 46px;
                background-color: #00bcd4;
                border-radius: 3px;
                cursor: pointer;
                flex: 0 0 25%;
                max-width: 25%;
                border: 1px solid #00bcd4;
            }

            .action-slm_deactivate .license-input-fields button {
                background-color: #E40055;
                border-color: #E40055;
            }

            .license-input-fields button:focus {
                outline: 0 none;
            }

            .active-license-info {
                display: flex;
            }

            .single-license-info {
                min-width: 220px;
                flex: 0 0 30%;
            }

            .single-license-info h3 {
                font-size: 18px;
                margin: 0 0 12px 0;
            }

            .single-license-info p {
                margin: 0;
                color: #00C000;
            }

            .single-license-info p.occupied {
                color: #E40055;
            }
        </style>
		<?php
	}
}