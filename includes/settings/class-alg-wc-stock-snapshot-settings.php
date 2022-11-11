<?php
/**
 * Stock Snapshot for WooCommerce - Settings
 *
 * @version 1.2.0
 * @since   1.0.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings' ) ) :

class Alg_WC_Stock_Snapshot_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id    = 'alg_wc_stock_snapshot';
		$this->label = __( 'Stock Snapshot', 'stock-snapshot-for-woocommerce' );
		parent::__construct();
		// Sections
		require_once( 'class-alg-wc-stock-snapshot-settings-section.php' );
		require_once( 'class-alg-wc-stock-snapshot-settings-general.php' );
		require_once( 'class-alg-wc-stock-snapshot-settings-tools.php' );
		require_once( 'class-alg-wc-stock-snapshot-settings-history.php' );
		require_once( 'class-alg-wc-stock-snapshot-settings-emails.php' );
	}

	/**
	 * get_settings.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function get_settings() {
		global $current_section;
		return array_merge( apply_filters( 'woocommerce_get_settings_' . $this->id . '_' . $current_section, array() ), array(
			array(
				'title'     => __( 'Reset Settings', 'stock-snapshot-for-woocommerce' ),
				'type'      => 'title',
				'id'        => $this->id . '_' . $current_section . '_reset_options',
			),
			array(
				'title'     => __( 'Reset section settings', 'stock-snapshot-for-woocommerce' ),
				'desc'      => '<strong>' . __( 'Reset', 'stock-snapshot-for-woocommerce' ) . '</strong>',
				'desc_tip'  => __( 'Check the box and save changes to reset.', 'stock-snapshot-for-woocommerce' ),
				'id'        => $this->id . '_' . $current_section . '_reset',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => $this->id . '_' . $current_section . '_reset_options',
			),
		) );
	}

	/**
	 * maybe_reset_settings.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function maybe_reset_settings() {
		global $current_section;
		if ( 'yes' === get_option( $this->id . '_' . $current_section . '_reset', 'no' ) ) {
			foreach ( $this->get_settings() as $value ) {
				if ( isset( $value['id'] ) ) {
					$id = explode( '[', $value['id'] );
					delete_option( $id[0] );
				}
			}
			add_action( 'admin_notices', array( $this, 'admin_notices_settings_reset_success' ), PHP_INT_MAX );
		}
	}

	/**
	 * admin_notices_settings_reset_success.
	 *
	 * @version 1.2.0
	 * @since   1.0.0
	 */
	function admin_notices_settings_reset_success() {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' .
			esc_html__( 'Your settings have been reset.', 'stock-snapshot-for-woocommerce' ) . '</strong></p></div>';
	}

	/**
	 * save.
	 *
	 * @version 1.1.3
	 * @since   1.0.0
	 */
	function save() {
		parent::save();
		do_action( 'alg_wc_stock_snapshot_settings_saved' );
		$this->maybe_reset_settings();
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Settings();
