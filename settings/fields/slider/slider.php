<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: slider
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'WPDK_Settings_Field_slider' ) ) {
  class WPDK_Settings_Field_slider extends WPDK_Settings_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = wp_parse_args( $this->field, array(
        'max'  => 100,
        'min'  => 0,
        'step' => 1,
        'unit' => '',
      ) );

      $is_unit = ( ! empty( $args['unit'] ) ) ? ' wpdk_settings--is-unit' : '';

      echo $this->field_before();

      echo '<div class="wpdk_settings--wrap">';
      echo '<div class="wpdk_settings-slider-ui"></div>';
      echo '<div class="wpdk_settings--input">';
      echo '<input type="number" name="'. esc_attr( $this->field_name() ) .'" value="'. esc_attr( $this->value ) .'"'. $this->field_attributes( array( 'class' => 'wpdk_settings-input-number'. esc_attr( $is_unit ) ) ) .' data-min="'. esc_attr( $args['min'] ) .'" data-max="'. esc_attr( $args['max'] ) .'" data-step="'. esc_attr( $args['step'] ) .'" step="any" />';
      echo ( ! empty( $args['unit'] ) ) ? '<span class="wpdk_settings--unit">'. esc_attr( $args['unit'] ) .'</span>' : '';
      echo '</div>';
      echo '</div>';

      echo $this->field_after();

    }

    public function enqueue() {

      if ( ! wp_script_is( 'jquery-ui-slider' ) ) {
        wp_enqueue_script( 'jquery-ui-slider' );
      }

    }

    public function output() {

      $output    = '';
      $elements  = ( is_array( $this->field['output'] ) ) ? $this->field['output'] : array_filter( (array) $this->field['output'] );
      $important = ( ! empty( $this->field['output_important'] ) ) ? '!important' : '';
      $mode      = ( ! empty( $this->field['output_mode'] ) ) ? $this->field['output_mode'] : 'width';
      $unit      = ( ! empty( $this->field['unit'] ) ) ? $this->field['unit'] : 'px';

      if ( ! empty( $elements ) && isset( $this->value ) && $this->value !== '' ) {
        foreach ( $elements as $key_property => $element ) {
          if ( is_numeric( $key_property ) ) {
            if ( $mode ) {
              $output = implode( ',', $elements ) .'{'. $mode .':'. $this->value . $unit . $important .';}';
            }
            break;
          } else {
            $output .= $element .'{'. $key_property .':'. $this->value . $unit . $important .'}';
          }
        }
      }

      $this->parent->output_css .= $output;

      return $output;

    }

  }
}
