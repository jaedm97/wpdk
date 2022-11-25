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

      echo '<div class="pbsettings-icon-select">';
      echo '<span class="pbsettings-icon-preview'. esc_attr( $hidden ) .'"><i class="'. esc_attr( $this->value ) .'"></i></span>';
      echo '<a href="#" class="button button-primary pbsettings-icon-add" data-nonce="'. esc_attr( $nonce ) .'">'. $args['button_title'] .'</a>';
      echo '<a href="#" class="button pbsettings-warning-primary pbsettings-icon-remove'. esc_attr( $hidden ) .'">'. $args['remove_title'] .'</a>';
      echo '<input type="hidden" name="'. esc_attr( $this->field_name() ) .'" value="'. esc_attr( $this->value ) .'" class="pbsettings-icon-value"'. $this->field_attributes() .' />';
      echo '</div>';

      echo $this->field_after();

    }

    public function enqueue() {
      add_action( 'admin_footer', array( 'WPDK_Settings_Field_icon', 'add_footer_modal_icon' ) );
      add_action( 'customize_controls_print_footer_scripts', array( 'WPDK_Settings_Field_icon', 'add_footer_modal_icon' ) );
    }

    public static function add_footer_modal_icon() {
    ?>
      <div id="pbsettings-modal-icon" class="pbsettings-modal pbsettings-modal-icon hidden">
        <div class="pbsettings-modal-table">
          <div class="pbsettings-modal-table-cell">
            <div class="pbsettings-modal-overlay"></div>
            <div class="pbsettings-modal-inner">
              <div class="pbsettings-modal-title">
                <?php esc_html_e( 'Add Icon' ); ?>
                <div class="pbsettings-modal-close pbsettings-icon-close"></div>
              </div>
              <div class="pbsettings-modal-header">
                <input type="text" placeholder="<?php esc_html_e( 'Search...' ); ?>" class="pbsettings-icon-search" />
              </div>
              <div class="pbsettings-modal-content">
                <div class="pbsettings-modal-loading"><div class="pbsettings-loading"></div></div>
                <div class="pbsettings-modal-load"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php
    }

  }
}
