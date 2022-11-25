<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: backup
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'WPDK_Settings_Field_backup' ) ) {
  class WPDK_Settings_Field_backup extends WPDK_Settings_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $unique = $this->unique;
      $nonce  = wp_create_nonce( 'pb_settings_backup_nonce' );
      $export = add_query_arg( array( 'action' => 'pbsettings-export', 'unique' => $unique, 'nonce' => $nonce ), admin_url( 'admin-ajax.php' ) );

      echo $this->field_before();

      echo '<textarea name="pb_settings_import_data" class="pbsettings-import-data"></textarea>';
      echo '<button type="submit" class="button button-primary pbsettings-confirm pbsettings-import" data-unique="'. esc_attr( $unique ) .'" data-nonce="'. esc_attr( $nonce ) .'">'. esc_html__( 'Import' ) .'</button>';
      echo '<hr />';
      echo '<textarea readonly="readonly" class="pbsettings-export-data">'. esc_attr( json_encode( get_option( $unique ) ) ) .'</textarea>';
      echo '<a href="'. esc_url( $export ) .'" class="button button-primary pbsettings-export" target="_blank">'. esc_html__( 'Export & Download' ) .'</a>';
      echo '<hr />';
      echo '<button type="submit" name="pb_settings_transient[reset]" value="reset" class="button pbsettings-warning-primary pbsettings-confirm pbsettings-reset" data-unique="'. esc_attr( $unique ) .'" data-nonce="'. esc_attr( $nonce ) .'">'. esc_html__( 'Reset' ) .'</button>';

      echo $this->field_after();

    }

  }
}
