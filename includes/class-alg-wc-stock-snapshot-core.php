<?php
/**
 * Stock Snapshot for WooCommerce - Core Class
 *
 * @version 1.3.0
 * @since   1.0.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Core' ) ) :

class Alg_WC_Stock_Snapshot_Core {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 * @since   1.0.0
	 *
	 * @todo    (feature) log
	 * @todo    (feature) hook into every quantity update, i.e., not only scheduled snapshots
	 */
	function __construct() {
		// Admin
		require_once( 'class-alg-wc-stock-snapshot-admin.php' );
		// Action Scheduler
		$this->action_scheduler = require_once( 'class-alg-wc-stock-snapshot-action-scheduler.php' );
		// Snapshots via URL
		if ( 'yes' === get_option( 'alg_wc_stock_snapshot_plugin_enabled', 'yes' ) && 'yes' === get_option( 'alg_wc_stock_snapshot_url', 'no' ) ) {
			add_action( 'init', array( $this, 'snapshot_via_url' ) );
		}
		// Shortcodes
		require_once( 'class-alg-wc-stock-snapshot-shortcodes.php' );
		// Core loaded
		do_action( 'alg_wc_stock_snapshot_core_loaded' );
	}

	/**
	 * delete_transients.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function delete_transients() {
		delete_transient( 'alg_wc_stock_snapshot_restocked' );
		delete_transient( 'alg_wc_stock_snapshot_history' );
	}

	/**
	 * snapshot_via_url.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function snapshot_via_url() {
		if ( isset( $_GET['alg_wc_stock_snapshot'] ) ) {
			$this->take_stock_snapshot();
		}
	}

	/**
	 * do_variations.
	 *
	 * @version 1.3.0
	 * @since   1.3.0
	 */
	function do_variations() {
		return ( 'yes' === get_option( 'alg_wc_stock_snapshot_include_variations', 'yes' ) );
	}

	/**
	 * take_product_stock_snapshot.
	 *
	 * @version 1.3.0
	 * @since   1.0.0
	 *
	 * @todo    (dev) save each snapshot in a separate meta (i.e., instead of all snapshots in a single array)?
	 */
	function take_product_stock_snapshot( $product_id, $index, $do_count_children = false ) {
		if ( ( $product = wc_get_product( $product_id ) ) ) {
			$stock = $product->get_stock_quantity();
			if ( $do_count_children && $product->has_child() && $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $child_id ) {
					if ( ( $child = wc_get_product( $child_id ) ) && null !== ( $child_stock = $child->get_stock_quantity() ) ) {
						if ( null === $stock ) {
							$stock = 0;
						}
						$stock += $child_stock;
					}
				}
			}
			if ( ! ( $stock_snapshot = get_post_meta( $product_id, '_alg_wc_stock_snapshot', true ) ) ) {
				$stock_snapshot = array();
				$prev_stock     = '-';
			} else {
				$prev_stock     = end( $stock_snapshot );
			}
			$stock_snapshot[ time() ] = $stock;
			update_post_meta( $product_id, '_alg_wc_stock_snapshot', $stock_snapshot );
			do_action( 'alg_wc_stock_snapshot_take_snapshot_product', $product_id, $product, $stock, $index, $prev_stock );
		}
	}

	/**
	 * take_stock_snapshot.
	 *
	 * @version 1.3.0
	 * @since   1.0.0
	 */
	function take_stock_snapshot( $do_die = true ) {
		$res = 0;
		do_action( 'alg_wc_stock_snapshot_take_snapshot_start' );
		$do_count_children = ( 'yes' === get_option( 'alg_wc_stock_snapshot_count_children', 'no' ) );
		$do_variations     = $this->do_variations();
		foreach ( wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) ) as $product_id ) {
			$this->take_product_stock_snapshot( $product_id, $res, $do_count_children );
			$res++;
			if ( $do_variations && ( $product = wc_get_product( $product_id ) ) && $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $child_id ) {
					$this->take_product_stock_snapshot( $child_id, $res );
					$res++;
				}
			}
		}
		$this->delete_transients();
		do_action( 'alg_wc_stock_snapshot_take_snapshot_end' );
		if ( $do_die ) {
			die();
		}
		return $res;
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Core();
