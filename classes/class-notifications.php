<?php
/**
 * WPDK SDK Client
 */

namespace WPDK;

/**
 * Class Notifications
 *
 * @package WPDK
 */
class Notifications {

	protected $cache_key;
	protected $data;

	/**
	 * @var Client null
	 */
	private $client = null;

	/**
	 * Notifications constructor.
	 */
	function __construct( Client $client ) {

		$this->client    = $client;
		$this->cache_key = sprintf( '_%s_notifications_data', md5( $this->client->text_domain ) );

		add_action( 'init', array( $this, 'force_check_notifications' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
	}


	/**
	 * Render notification as notices
	 */
	function render_admin_notices() {
		$this->client->print_notice( $this->get_message(), 'info', false, $this->get_id() );
	}


	/**
	 * Force check notifications
	 */
	function force_check_notifications() {
		if ( Utils::get_args_option( 'force-check', wp_unslash( $_GET ) ) === 'yes' ) {
			$this->set_cached_notification_data( $this->get_latest_notification_data() );
		}
	}


	/**
	 * Return notification content
	 *
	 * @return mixed|string
	 */
	private function get_message() {
		return $this->client->get_parsed_string( Utils::get_args_option( 'message', $this->get_notification_data() ) );
	}


	/**
	 * Return notification unique ID
	 *
	 * @return array|mixed|string
	 */
	private function get_id() {
		return Utils::get_args_option( 'id', $this->get_notification_data() );
	}


	/**
	 * Get version information
	 */
	private function get_notification_data() {

		$notification_data = $this->get_cached_notification_data();

		if ( ! $notification_data ) {
			$notification_data = $this->get_latest_notification_data();
			$this->set_cached_notification_data( $notification_data );
		}

		return $notification_data;
	}


	/**
	 * Get new data from server
	 *
	 * @return false|mixed
	 */
	private function get_latest_notification_data() {

		if ( ! is_wp_error( $data = $this->client->send_request( 'notifications/' . $this->client->plugin_reference ) ) ) {
			return $data;
		}

		return array(
			'id'      => 10000,
			'message' => sprintf( '<p>Thanks for using <strong>%s</strong>. Checkout pro version with <a href="%s/offers-and-coupons/"><strong>discounts and coupons</strong></a></p>',
				$this->client->plugin_name, $this->client->integration_server
			),
		);
	}


	/**
	 * Set cached data
	 *
	 * @param $value
	 */
	private function set_cached_notification_data( $value ) {
		if ( $value ) {
			// check notifications in every 2 days
			set_transient( $this->cache_key, $value, 2 * 24 * HOUR_IN_SECONDS );
		}
	}


	/**
	 * Get cached data
	 *
	 * @return mixed
	 */
	private function get_cached_notification_data() {
		return get_transient( $this->cache_key );
	}
}