<?php
/**
 * WPDK SDK Client
 *
 * @version 1.0.9
 * @author WPDK
 */

namespace WPDK;

use WPDK_Settings;

/**
 * Class Client
 *
 * @package WPDK
 */
class Client {

	public $integration_server = '';
	public $plugin_name = null;
	public $text_domain = null;
	public $plugin_reference = null;
	public $plugin_version = null;
	public $plugin_file = null;
	public $plugin_unique_id = null;
	public $license_secret_key = '';

	/**
	 * @var \WPDK\Utils
	 */
	private static $utils;

	/**
	 * @var \WPDK\Notifications
	 */
	private static $notifications;


	/**
	 * @var \WPDK\License
	 */
	private static $license;


	/**
	 * Client constructor.
	 *
	 * @param $plugin_name
	 * @param $text_domain
	 * @param $plugin_reference
	 * @param $file
	 */
	function __construct( $plugin_name, $text_domain, $plugin_reference, $file ) {

		if ( ! class_exists( __NAMESPACE__ . '\Settings' ) ) {
			require_once __DIR__ . '../../settings/classes/setup.class.php';
		}

		// Initialize variables
		$this->plugin_name        = $plugin_name;
		$this->text_domain        = $text_domain;
		$this->plugin_reference   = $plugin_reference;
		$this->plugin_file        = $file;
		$plugin_data              = get_plugin_data( $this->plugin_file );
		$this->plugin_version     = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';
		$this->plugin_unique_id   = str_replace( '-', '_', $this->text_domain );
		$this->integration_server = apply_filters( 'WPDK_Settings/Filters/integration_server_' . $this->plugin_unique_id, '' );
		$this->license_secret_key = apply_filters( 'WPDK_Settings/Filters/license_secret_key_' . $this->plugin_unique_id, '' );

		add_action( 'admin_init', array( $this, 'manage_permanent_dismissible' ) );

		$this::utils();
		WPDK_Settings::init( $this );
	}


	/**
	 * Return Utils class
	 *
	 * @return Utils
	 */
	public function utils() {

		if ( ! class_exists( __NAMESPACE__ . '\Utils' ) ) {
			require_once __DIR__ . '/class-utils.php';
		}

		if ( ! self::$utils ) {
			self::$utils = new Utils( $this );
		}

		return self::$utils;
	}


	/**
	 * Return Notifications class
	 *
	 * @return \WPDK\Notifications
	 */
	public function notifications() {

		if ( ! class_exists( __NAMESPACE__ . '\Notifications' ) ) {
			require_once __DIR__ . '/class-notifications.php';
		}

		if ( ! self::$notifications ) {
			self::$notifications = new Notifications( $this );
		}

		return self::$notifications;
	}

	/**
	 * Return Notifications class
	 *
	 * @return \WPDK\License
	 */
	public function license( $plugin_file = '' ) {

		if ( ! class_exists( __NAMESPACE__ . '\License' ) ) {
			require_once __DIR__ . '/class-license.php';
		}

		if ( ! self::$license ) {
			self::$license = new License( $this, $plugin_file );
		}

		return self::$license;
	}


	/**
	 * Manage permanent dismissible of any notice
	 */
	function manage_permanent_dismissible() {

		$query_args = wp_unslash( array_map( 'sanitize_text_field', $_GET ) );

		if ( Utils::get_args_option( 'pb_action', $query_args ) == 'permanent_dismissible' && ! empty( $id = Utils::get_args_option( 'id', $query_args ) ) ) {

			// update value
			update_option( $this->get_notices_id( $id ), time() );

			// Redirect
			wp_safe_redirect( site_url( 'wp-admin' ) );
			exit;
		}
	}


	/**
	 * Send request to remote endpoint
	 *
	 * @param $route
	 * @param array $params
	 * @param false $is_post
	 * @param false $blocking
	 * @param false $return_associative
	 *
	 * @return array|mixed|\WP_Error
	 */
	public function send_request( $route, $params = array(), $is_post = false, $blocking = false, $return_associative = true ) {

		$url = trailingslashit( $this->integration_server ) . 'wp-json/data/' . $route;

		if ( $is_post ) {
			$response = wp_remote_post( $url, array(
				'timeout'     => 30,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => $blocking,
				'headers'     => array(
					'user-agent' => 'WPDK/' . md5( esc_url( $this->get_website_url() ) ) . ';',
					'Accept'     => 'application/json',
				),
				'body'        => array_merge( $params, array( 'version' => $this->plugin_version ) ),
				'cookies'     => array(),
				'sslverify'   => false,
			) );
		} else {
			$response = wp_remote_get( $url, array( 'timeout' => 30, 'sslverify' => false ) );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ), $return_associative );
	}


	/**
	 * Print notices
	 *
	 * @param string $message
	 * @param string $type
	 * @param bool $is_dismissible
	 * @param bool $permanent_dismiss
	 */
	public function print_notice( $message = '', $type = 'success', $is_dismissible = true, $permanent_dismiss = false ) {

		if ( $permanent_dismiss && ! empty( get_option( $this->get_notices_id( $permanent_dismiss ) ) ) ) {
			return;
		}

		$is_dismissible = $is_dismissible ? 'is-dismissible' : '';
		$pb_dismissible = '';

		// Manage permanent dismissible
		if ( $permanent_dismiss ) {
			$is_dismissible = 'pb-is-dismissible';
			$pb_dismissible = sprintf( '<a href="%s" class="notice-dismiss"><span class="screen-reader-text">%s</span></a>',
				esc_url_raw( add_query_arg(
					array(
						'pb_action' => 'permanent_dismissible',
						'id'        => $permanent_dismiss
					), site_url( 'wp-admin' )
				) ),
				esc_html__( 'Dismiss', $this->text_domain )
			);
		}

		if ( ! empty( $message ) ) {
			printf( '<div class="notice notice-%s %s">%s%s</div>', $type, $is_dismissible, $message, $pb_dismissible );
			?>
            <style>
                .pb-is-dismissible {
                    position: relative;
                }

                .notice-dismiss, .notice-dismiss:active, .notice-dismiss:focus {
                    top: 50%;
                    transform: translateY(-50%);
                    text-decoration: none;
                    outline: none;
                    box-shadow: none;
                }
            </style>
			<?php
		}
	}


	/**
	 * Return notices id with prefix
	 *
	 * @param $id
	 *
	 * @return string
	 */
	public function get_notices_id( $id ) {
		return $this->integration_server . $id;
	}


	/**
	 * Parsed string
	 *
	 * @param $string
	 *
	 * @return mixed|string
	 */
	public function get_parsed_string( $string ) {

		preg_match_all( '#\{(.*?)\}#', $string, $matches, PREG_SET_ORDER, 0 );

		foreach ( $matches as $match ) {

			$match_object = explode( '.', $match[1] );

			if ( isset( $match_object[0] ) ) {
				switch ( $match_object[0] ) {
					case 'user':
						global $current_user;
						$string = str_replace( $match[0], $current_user->{$match_object[1]}, $string );
						break;
				}
			}
		}

		return $string;
	}


	/**
	 * Return url of client website
	 *
	 * @param $path
	 *
	 * @return string|void
	 */
	public function get_website_url( $path = '' ) {

		if ( is_multisite() && isset( $_SERVER['SERVER_NAME'] ) ) {
			return sanitize_text_field( $_SERVER['SERVER_NAME'] ) . '/' . $path;
		}

		return site_url( $path );
	}


	/**
	 * Translate function _e()
	 */
	public function _etrans( $text ) {
		call_user_func( '_e', $text, $this->text_domain );
	}


	/**
	 * Translate function __()
	 */
	public function __trans( $text ) {
		return call_user_func( '__', $text, $this->text_domain );
	}


	/**
	 * Return Plugin Basename
	 *
	 * @return string
	 */
	public function basename() {
		return sprintf( '%1$s/%1$s.php', $this->text_domain );
	}
}
