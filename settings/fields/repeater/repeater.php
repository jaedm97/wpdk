<?php if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.
/**
 *
 * Field: repeater
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'WPDK_Settings_Field_repeater' ) ) {
	class WPDK_Settings_Field_repeater extends WPDK_Settings_Fields {

		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		public function render() {

			$args = wp_parse_args( $this->field, array(
				'max'             => 0,
				'min'             => 0,
				'button_title'    => '<i class="fas fa-plus-circle"></i>',
				'disable_actions' => array(),
				'max_notice'      => esc_html__( 'You cannot add more.' ),
				'min_notice'      => esc_html__( 'You cannot remove more.' ),
			) );

			if ( preg_match( '/' . preg_quote( '[' . $this->field['id'] . ']' ) . '/', $this->unique ) ) {

				echo '<div class="wpdk_settings-notice wpdk_settings-notice-danger">' . esc_html__( 'Error: Field ID conflict.' ) . '</div>';

			} else {

				echo $this->field_before();

				echo '<div class="wpdk_settings-repeater-item wpdk_settings-repeater-hidden" data-depend-id="' . esc_attr( $this->field['id'] ) . '">';
				echo '<div class="wpdk_settings-repeater-content">';
				foreach ( $this->field['fields'] as $field ) {

					$field_default = ( isset( $field['default'] ) ) ? $field['default'] : '';
					$field_unique  = ( ! empty( $this->unique ) ) ? $this->unique . '[' . $this->field['id'] . '][0]' : $this->field['id'] . '[0]';

					WPDK_Settings::field( $field, $field_default, '___' . $field_unique, 'field/repeater' );

				}
				echo '</div>';
				echo '<div class="wpdk_settings-repeater-helper">';
				echo '<div class="wpdk_settings-repeater-helper-inner">';

				if ( ! in_array( 'sort', $args['disable_actions'] ) ) {
					echo '<i class="wpdk_settings-repeater-sort fas fa-arrows-alt"></i>';
				}

				if ( ! in_array( 'clone', $args['disable_actions'] ) ) {
					echo '<i class="wpdk_settings-repeater-clone far fa-clone"></i>';
				}

				if ( ! in_array( 'remove', $args['disable_actions'] ) ) {
					echo '<i class="wpdk_settings-repeater-remove wpdk_settings-confirm fas fa-times" data-confirm="' . esc_html__( 'Are you sure to delete this item?' ) . '"></i>';
				}

				echo '</div>';
				echo '</div>';
				echo '</div>';

				echo '<div class="wpdk_settings-repeater-wrapper wpdk_settings-data-wrapper" data-field-id="[' . esc_attr( $this->field['id'] ) . ']" data-max="' . esc_attr( $args['max'] ) . '" data-min="' . esc_attr( $args['min'] ) . '">';

				if ( ! empty( $this->value ) && is_array( $this->value ) ) {

					$num = 0;

					foreach ( $this->value as $key => $value ) {

						echo '<div class="wpdk_settings-repeater-item">';
						echo '<div class="wpdk_settings-repeater-content">';
						foreach ( $this->field['fields'] as $field ) {

							$field_unique = ( ! empty( $this->unique ) ) ? $this->unique . '[' . $this->field['id'] . '][' . $key . ']' : $this->field['id'] . '[' . $key . ']';
							$field_value  = ( isset( $field['id'] ) && isset( $this->value[ $key ][ $field['id'] ] ) ) ? $this->value[ $key ][ $field['id'] ] : '';

							WPDK_Settings::field( $field, $field_value, $field_unique, 'field/repeater' );

						}
						echo '</div>';
						echo '<div class="wpdk_settings-repeater-helper">';
						echo '<div class="wpdk_settings-repeater-helper-inner">';

						if ( ! in_array( 'sort', $args['disable_actions'] ) ) {
							echo '<i class="wpdk_settings-repeater-sort fas fa-arrows-alt"></i>';
						}

						if ( ! in_array( 'clone', $args['disable_actions'] ) ) {
							echo '<i class="wpdk_settings-repeater-clone far fa-clone"></i>';
						}

						if ( ! in_array( 'remove', $args['disable_actions'] ) ) {
							echo '<i class="wpdk_settings-repeater-remove wpdk_settings-confirm fas fa-times" data-confirm="' . esc_html__( 'Are you sure to delete this item?' ) . '"></i>';
						}

						echo '</div>';
						echo '</div>';
						echo '</div>';

						$num ++;

					}

				}

				echo '</div>';

				echo '<div class="wpdk_settings-repeater-alert wpdk_settings-repeater-max">' . $args['max_notice'] . '</div>';
				echo '<div class="wpdk_settings-repeater-alert wpdk_settings-repeater-min">' . $args['min_notice'] . '</div>';
				echo '<a href="#" class="button button-primary wpdk_settings-repeater-add">' . $args['button_title'] . '</a>';

				echo $this->field_after();

			}

		}

		public function enqueue() {

			if ( ! wp_script_is( 'jquery-ui-sortable' ) ) {
				wp_enqueue_script( 'jquery-ui-sortable' );
			}

		}

	}
}
