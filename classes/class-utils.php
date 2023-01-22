<?php
/**
 * WPDK SDK - Utils class
 */

namespace WPDK;

/**
 * Class Utils
 *
 * @package WPDK
 */
class Utils {

	/**
	 * @var Client null
	 */
	private $client = null;

	private static $settings_id = null;


	/**
	 * Notifications constructor.
	 */
	function __construct( Client $client ) {

		$this->client      = $client;
		self::$settings_id = $client->plugin_unique_id;
	}


	/**
	 * Register Shortcode
	 *
	 * @param string $shortcode
	 * @param string $callable_func
	 */
	public static function register_shortcode( $shortcode = '', $callable_func = '' ) {

		if ( empty( $shortcode ) || empty( $callable_func ) ) {
			return;
		}

		add_shortcode( $shortcode, $callable_func );
	}


	/**
	 * Register Taxonomy
	 *
	 * @param $tax_name
	 * @param $obj_type
	 * @param array $args
	 */
	function register_taxonomy( $tax_name, $obj_type, $args = array() ) {

		if ( taxonomy_exists( $tax_name ) ) {
			return;
		}

		$singular = Utils::get_args_option( 'singular', $args, '' );
		$plural   = Utils::get_args_option( 'plural', $args, '' );
		$labels   = Utils::get_args_option( 'labels', $args, array() );

		$args = wp_parse_args( $args,
			array(
				'description'         => sprintf( $this->client->__trans( 'This is where you can create and manage %s.' ), $plural ),
				'public'              => true,
				'show_ui'             => true,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => false,
				'hierarchical'        => false,
				'rewrite'             => true,
				'query_var'           => true,
				'show_in_nav_menus'   => true,
				'show_in_menu'        => true,
			)
		);

		$args['labels'] = wp_parse_args( $labels,
			array(
				'name'               => sprintf( $this->client->__trans( '%s' ), $plural ),
				'singular_name'      => $singular,
				'menu_name'          => $this->client->__trans( $plural ),
				'all_items'          => sprintf( $this->client->__trans( '%s' ), $plural ),
				'add_new'            => sprintf( $this->client->__trans( 'Add %s' ), $singular ),
				'add_new_item'       => sprintf( $this->client->__trans( 'Add %s' ), $singular ),
				'edit'               => $this->client->__trans( 'Edit' ),
				'edit_item'          => sprintf( $this->client->__trans( '%s Details' ), $singular ),
				'new_item'           => sprintf( $this->client->__trans( 'New %s' ), $singular ),
				'view'               => sprintf( $this->client->__trans( 'View %s' ), $singular ),
				'view_item'          => sprintf( $this->client->__trans( 'View %s' ), $singular ),
				'search_items'       => sprintf( $this->client->__trans( 'Search %s' ), $plural ),
				'not_found'          => sprintf( $this->client->__trans( 'No %s found' ), $plural ),
				'not_found_in_trash' => sprintf( $this->client->__trans( 'No %s found in trash' ), $plural ),
				'parent'             => sprintf( $this->client->__trans( 'Parent %s' ), $singular ),
			)
		);

		register_taxonomy( $tax_name, $obj_type, apply_filters( "WPDK/Utils/register_taxonomy_$tax_name", $args, $obj_type ) );
	}


	/**
	 * Register Post Type
	 *
	 * @param $post_type
	 * @param array $args
	 */
	public function register_post_type( $post_type, $args = array() ) {

		if ( post_type_exists( $post_type ) ) {
			return;
		}

		$singular = Utils::get_args_option( 'singular', $args, '' );
		$plural   = Utils::get_args_option( 'plural', $args, '' );
		$labels   = Utils::get_args_option( 'labels', $args, array() );

		$args = wp_parse_args( $args,
			array(
				'description'         => sprintf( $this->client->__trans( 'This is where you can create and manage %s.' ), $plural ),
				'public'              => true,
				'show_ui'             => true,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => false,
				'hierarchical'        => false,
				'rewrite'             => true,
				'query_var'           => true,
				'supports'            => array( 'title', 'thumbnail', 'editor', 'author' ),
				'show_in_nav_menus'   => true,
				'show_in_menu'        => true,
				'menu_icon'           => '',
			)
		);

		$args['labels'] = wp_parse_args( $labels,
			array(
				'name'               => sprintf( $this->client->__trans( '%s' ), $plural ),
				'singular_name'      => $singular,
				'menu_name'          => $this->client->__trans( $plural ),
				'all_items'          => sprintf( $this->client->__trans( '%s' ), $plural ),
				'add_new'            => sprintf( $this->client->__trans( 'Add %s' ), $singular ),
				'add_new_item'       => sprintf( $this->client->__trans( 'Add %s' ), $singular ),
				'edit'               => $this->client->__trans( 'Edit' ),
				'edit_item'          => sprintf( $this->client->__trans( 'Edit %s' ), $singular ),
				'new_item'           => sprintf( $this->client->__trans( 'New %s' ), $singular ),
				'view'               => sprintf( $this->client->__trans( 'View %s' ), $singular ),
				'view_item'          => sprintf( $this->client->__trans( 'View %s' ), $singular ),
				'search_items'       => sprintf( $this->client->__trans( 'Search %s' ), $plural ),
				'not_found'          => sprintf( $this->client->__trans( 'No %s found' ), $plural ),
				'not_found_in_trash' => sprintf( $this->client->__trans( 'No %s found in trash' ), $plural ),
				'parent'             => sprintf( $this->client->__trans( 'Parent %s' ), $singular ),
			)
		);

		register_post_type( $post_type, apply_filters( "WPDK/Utils/register_post_type$post_type", $args ) );
	}


	/**
	 * Return Arguments Value
	 *
	 * @param string $key
	 * @param string $default
	 * @param array $args
	 *
	 * @return mixed|string
	 */
	public static function get_args_option( $key = '', $args = array(), $default = '' ) {

		$default = is_array( $default ) && empty( $default ) ? array() : $default;
		$value   = ! is_array( $default ) && ! is_bool( $default ) && empty( $default ) ? '' : $default;
		$key     = empty( $key ) ? '' : $key;

		if ( isset( $args[ $key ] ) && ! empty( $args[ $key ] ) ) {
			$value = $args[ $key ];
		}

		if ( isset( $args[ $key ] ) && is_bool( $default ) ) {
			$value = ! ( 0 == $args[ $key ] || '' == $args[ $key ] );
		}

		return $value;
	}


	/**
	 * Return Post Meta Value
	 *
	 * @param bool $meta_key
	 * @param bool $post_id
	 * @param string $default
	 *
	 * @return mixed|string|void
	 */
	public static function get_meta( $meta_key = false, $post_id = false, $default = '' ) {

		if ( ! $meta_key ) {
			return false;
		}

		$post_id    = ! $post_id ? get_the_ID() : $post_id;
		$meta_value = get_post_meta( $post_id, $meta_key, true );
		$meta_value = "" === $meta_value ? $default : $meta_value;

		return apply_filters( 'WPDK/Utils/get_meta', $meta_value, $meta_key, $post_id, $default );
	}


	/**
	 * Return option value
	 *
	 * @param string $option_key
	 * @param string $default
	 *
	 * @return mixed|string|void
	 */
	public static function get_option( $option_key = '', $default = '' ) {

		if ( empty( $option_key ) ) {
			return '';
		}

		$all_options  = get_option( self::$settings_id, array() );
		$option_value = self::get_args_option( $option_key, $all_options, $default );

		return apply_filters( 'WPDK/Utils/get_option' . $option_key, $option_value );

	}


	/**
	 * Update option for settings panel
	 *
	 * @param string $option_key
	 * @param string $new_value
	 *
	 * @return bool|string
	 */
	public static function update_option( $option_key = '', $new_value = '' ) {

		if ( empty( $option_key ) ) {
			return '';
		}

		$all_options                = get_option( self::$settings_id, array() );
		$all_options[ $option_key ] = $new_value;
		$all_options                = apply_filters( 'WPDK/Utils/update_option', $all_options, $option_key, $new_value );

		return update_option( self::$settings_id, $all_options );
	}
}