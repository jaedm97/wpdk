<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.

/**
 *
 * Field: image_select_sortable
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'WPDK_Settings_Field_image_select_sortable' ) ) {
	class WPDK_Settings_Field_image_select_sortable extends WPDK_Settings_Fields {

		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		public function render() {

			$args    = wp_parse_args( $this->field, array(
				'multiple' => false,
				'inline'   => false,
				'options'  => array(),
			) );
			$inline  = ( $args['inline'] ) ? ' pbsettings--inline-list' : '';
			$value   = ( is_array( $this->value ) ) ? $this->value : array_filter( (array) $this->value );
			$options = array();

			foreach ( $value as $_value ) {
				$options[ $_value ] = isset( $args['options'] [ $_value ] ) ? $args['options'] [ $_value ] : '';
			}

			foreach ( $args['options'] as $__key => $__value ) {
				if ( ! in_array( $__key, $value ) ) {
					$options[ $__key ] = $__value;
				}
			}

			echo $this->field_before();

			if ( ! empty( $args['options'] ) ) {

				echo '<div class="pbsettings-siblings pbsettings--image-group' . esc_attr( $inline ) . '" data-multiple="' . esc_attr( $args['multiple'] ) . '">';

				$num = 1;

				foreach ( $options as $key => $option ) {

					$type    = ( $args['multiple'] ) ? 'checkbox' : 'radio';
					$extra   = ( $args['multiple'] ) ? '[' . $num . ']' : '';
					$active  = ( in_array( $key, $value ) ) ? ' pbsettings--active' : '';
					$checked = ( in_array( $key, $value ) ) ? ' checked' : '';

					echo '<div class="pbsettings--sibling-wrap">';
					echo '<span class="sortable"><i class="fa fa-arrows" aria-hidden="true"></i></span>';
					echo '<div class="pbsettings--sibling pbsettings--image' . esc_attr( $active ) . '">';
					echo '<figure>';
					echo '<img src="' . esc_url( $option ) . '" alt="img-' . esc_attr( $num ++ ) . '" />';
					echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $this->field_name( $extra ) ) . '" value="' . esc_attr( $key ) . '"' . $this->field_attributes() . esc_attr( $checked ) . '/>';
					echo '</figure>';
					echo '</div>';
					echo '</div>';

				}

				echo '</div>';

			}

			echo $this->field_after();

		}

		public function output() {

			$output    = '';
			$bg_image  = array();
			$important = ( ! empty( $this->field['output_important'] ) ) ? '!important' : '';
			$elements  = ( is_array( $this->field['output'] ) ) ? join( ',', $this->field['output'] ) : $this->field['output'];

			if ( ! empty( $elements ) && isset( $this->value ) && $this->value !== '' ) {
				$output = $elements . '{background-image:url(' . $this->value . ')' . $important . ';}';
			}

			$this->parent->output_css .= $output;

			return $output;

		}

	}
}
