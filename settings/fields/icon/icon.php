<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: icon
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'WPDK_Settings_Field_icon' ) ) {
  class WPDK_Settings_Field_icon extends WPDK_Settings_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = wp_parse_args( $this->field, array(
        'button_title' => esc_html__( 'Add Icon' ),
        'remove_title' => esc_html__( 'Remove Icon' ),
      ) );

      echo $this->field_before();

      $nonce  = wp_create_nonce( 'pb_settings_icon_nonce' );
      $hidden = ( empty( $this->value ) ) ? ' hidden' : '';

      echo '<div class="wpdk_settings-icon-select">';
      echo '<span class="wpdk_settings-icon-preview'. esc_attr( $hidden ) .'"><i class="'. esc_attr( $this->value ) .'"></i></span>';
      echo '<a href="#" class="button button-primary wpdk_settings-icon-add" data-nonce="'. esc_attr( $nonce ) .'">'. $args['button_title'] .'</a>';
      echo '<a href="#" class="button wpdk_settings-warning-primary wpdk_settings-icon-remove'. esc_attr( $hidden ) .'">'. $args['remove_title'] .'</a>';
      echo '<input type="hidden" name="'. esc_attr( $this->field_name() ) .'" value="'. esc_attr( $this->value ) .'" class="wpdk_settings-icon-value"'. $this->field_attributes() .' />';
      echo '</div>';

      echo $this->field_after();

    }

    public function enqueue() {
      add_action( 'admin_footer', array( 'WPDK_Settings_Field_icon', 'add_footer_modal_icon' ) );
      add_action( 'customize_controls_print_footer_scripts', array( 'WPDK_Settings_Field_icon', 'add_footer_modal_icon' ) );
    }

    public static function add_footer_modal_icon() {
    ?>
      <div id="wpdk_settings-modal-icon" class="wpdk_settings-modal wpdk_settings-modal-icon hidden">
        <div class="wpdk_settings-modal-table">
          <div class="wpdk_settings-modal-table-cell">
            <div class="wpdk_settings-modal-overlay"></div>
            <div class="wpdk_settings-modal-inner">
              <div class="wpdk_settings-modal-title">
                <?php esc_html_e( 'Add Icon' ); ?>
                <div class="wpdk_settings-modal-close wpdk_settings-icon-close"></div>
              </div>
              <div class="wpdk_settings-modal-header">
                <input type="text" placeholder="<?php esc_html_e( 'Search...' ); ?>" class="wpdk_settings-icon-search" />
              </div>
              <div class="wpdk_settings-modal-content">
                <div class="wpdk_settings-modal-loading"><div class="wpdk_settings-loading"></div></div>
                <div class="wpdk_settings-modal-load"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php
    }

  }
}
