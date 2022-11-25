<?php if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.
/**
 *
 * Field: text
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'WPDK_Settings_Field_text' ) ) {
	class WPDK_Settings_Field_text extends WPDK_Settings_Fields {

		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		public function render() {

			$field_id    = ( ! empty( $this->field['id'] ) ) ? $this->field['id'] : '';
			$type        = ( ! empty( $this->field['attributes']['type'] ) ) ? $this->field['attributes']['type'] : 'text';
			$field_name  = $this->field_name();
			$field_value = $this->value;

			if ( 'post_title' == $field_id ) {
				$field_name  = 'post_title';
				$field_value = get_the_title();
			}

			echo $this->field_before();


			echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '"' . $this->field_attributes() . ' />';

			echo $this->field_after();

		}

	}
}
