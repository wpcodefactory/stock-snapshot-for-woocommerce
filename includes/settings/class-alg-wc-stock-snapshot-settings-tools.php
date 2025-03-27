<?php
/**
 * Stock Snapshot for WooCommerce - Tools Section Settings
 *
 * @version 2.1.0
 * @since   1.2.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings_Tools' ) ) :

class Alg_WC_Stock_Snapshot_Settings_Tools extends Alg_WC_Stock_Snapshot_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function __construct() {
		$this->id   = 'tools';
		$this->desc = __( 'Tools', 'stock-snapshot-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 2.1.0
	 * @since   1.2.0
	 */
	function get_settings() {
		return array(
			array(
				'title'    => __( 'Tools', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Check the box and "Save changes" to run the tool.', 'stock-snapshot-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_stock_snapshot_tools',
			),
			array(
				'title'    => __( 'Take snapshot', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Run', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => __( 'Takes snapshot manually.', 'stock-snapshot-for-woocommerce' ),
				'id'       => 'alg_wc_stock_snapshot_tools_take_snapshot',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Delete all snapshots', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Run', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => (
					__( 'Deletes all snapshots.', 'stock-snapshot-for-woocommerce' ) . ' ' .
					'<span style="color:red;">' .
						__( 'Please note that there is no undo for this tool.', 'stock-snapshot-for-woocommerce' ) .
					'</span>'
				),
				'id'       => 'alg_wc_stock_snapshot_tools_delete_snapshots',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_stock_snapshot_tools',
			),
			array(
				'title'    => __( 'Advanced Options', 'stock-snapshot-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_stock_snapshot_advanced_options',
			),
			array(
				'title'    => __( 'Clear plugin transients', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Run', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => __( 'Affects the "Report" and "History" sections, shortcode, etc.', 'stock-snapshot-for-woocommerce' ),
				'id'       => 'alg_wc_stock_snapshot_clear_transients',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_stock_snapshot_advanced_options',
			),
		);
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Settings_Tools();
