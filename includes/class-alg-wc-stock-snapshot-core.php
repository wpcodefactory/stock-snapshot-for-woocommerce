<?php
/**
 * Stock Snapshot for WooCommerce - Core Class
 *
 * @version 2.0.0
 * @since   1.0.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Core' ) ) :

class Alg_WC_Stock_Snapshot_Core {

	/**
	 * action_scheduler.
	 *
	 * @version 1.5.0
	 * @since   1.5.0
	 */
	public $action_scheduler;

	/**
	 * admin.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	public $admin;

	/**
	 * Constructor.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 *
	 * @todo    (feature) log
	 */
	function __construct() {

		// Admin
		$this->admin = require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-stock-snapshot-admin.php';

		// Action Scheduler
		$this->action_scheduler = require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-stock-snapshot-action-scheduler.php';

		// Snapshots via URL
		if (
			'yes' === get_option( 'alg_wc_stock_snapshot_plugin_enabled', 'yes' ) &&
			'yes' === get_option( 'alg_wc_stock_snapshot_url', 'no' )
		) {
			add_action( 'init', array( $this, 'snapshot_via_url' ) );
		}

		// "Product update" snapshot
		if ( 'yes' === get_option( 'alg_wc_stock_snapshot_product_update', 'no' ) ) {

			// Update product
			add_action(
				'woocommerce_update_product',
				array( $this, 'product_update_snapshot' ),
				PHP_INT_MAX
			);

			// Update product variation
			add_action(
				'woocommerce_update_product_variation',
				array( $this, 'product_variation_update_snapshot' ),
				PHP_INT_MAX
			);

		}

		// Shortcodes
		require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-stock-snapshot-shortcodes.php';

		// Core loaded
		do_action( 'alg_wc_stock_snapshot_core_loaded' );

	}

	/**
	 * product_update_snapshot.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 *
	 * @todo    (dev) more hooks, e.g., `woocommerce_process_product_meta`?
	 */
	function product_update_snapshot( $product_id ) {

		$this->take_product_stock_snapshot( $product_id, $this->do_count_children() );

		$this->delete_transients();

	}

	/**
	 * product_variation_update_snapshot.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function product_variation_update_snapshot( $product_id ) {

		$do_delete_transients = false;

		if ( $this->do_variations() ) {
			$this->take_product_stock_snapshot( $product_id );
			$do_delete_transients = true;
		}

		if ( $this->do_count_children() ) {
			$this->take_product_stock_snapshot( wp_get_post_parent_id( $product_id ), true );
			$do_delete_transients = true;
		}

		if ( $do_delete_transients ) {
			$this->delete_transients();
		}

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
	 * do_count_children.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function do_count_children() {
		return ( 'yes' === get_option( 'alg_wc_stock_snapshot_count_children', 'no' ) );
	}

	/**
	 * do_extra_data.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function do_extra_data() {
		return ( 'yes' === get_option( 'alg_wc_stock_snapshot_extra_data', 'no' ) );
	}

	/**
	 * take_product_stock_snapshot.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 *
	 * @todo    (dev) save each snapshot in a separate meta (i.e., instead of all snapshots in a single array)?
	 */
	function take_product_stock_snapshot( $product_id, $do_count_children = false, $index = 0 ) {
		if ( ( $product = wc_get_product( $product_id ) ) ) {

			// Get product stock quantity
			$stock = $product->get_stock_quantity();

			// Count children
			if (
				$do_count_children &&
				$product->has_child() &&
				$product->is_type( 'variable' )
			) {
				foreach ( $product->get_children() as $child_id ) {
					if (
						( $child = wc_get_product( $child_id ) ) &&
						null !== ( $child_stock = $child->get_stock_quantity() )
					) {
						if ( null === $stock ) {
							$stock = 0;
						}
						$stock += $child_stock;
					}
				}
			}

			// Get previous snapshot
			if ( ! ( $stock_snapshot = get_post_meta( $product_id, '_alg_wc_stock_snapshot', true ) ) ) {
				$stock_snapshot = array();
				$prev_stock = '-';
			} else {
				$prev_stock = end( $stock_snapshot );
				if ( is_array( $prev_stock ) ) {
					$prev_stock = $prev_stock['stock'];
				}
			}

			// Add & save new snapshot
			if ( $this->do_extra_data() ) {
				// Extra data
				$stock_snapshot[ time() ] = array(
					'stock'   => $stock,
					'hook'    => current_filter(),
					'user_id' => get_current_user_id(),
				);
			} else {
				// Simple
				$stock_snapshot[ time() ] = $stock;
			}
			update_post_meta( $product_id, '_alg_wc_stock_snapshot', $stock_snapshot );

			// Action
			do_action(
				'alg_wc_stock_snapshot_take_snapshot_product',
				$product_id,
				$product,
				$stock,
				$index,
				$prev_stock
			);

		}
	}

	/**
	 * take_stock_snapshot.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function take_stock_snapshot( $do_die = true ) {
		$res = 0;

		do_action( 'alg_wc_stock_snapshot_take_snapshot_start' );

		$do_count_children = $this->do_count_children();
		$do_variations     = $this->do_variations();

		foreach ( wc_get_products( array(
			'limit'   => -1,
			'return'  => 'ids',
			'orderby' => 'ID',
			'order'   => 'ASC',
		) ) as $product_id ) {

			// Take product stock snapshot
			$this->take_product_stock_snapshot( $product_id, $do_count_children, $res );
			$res++;

			// Do variations
			if (
				$do_variations &&
				( $product = wc_get_product( $product_id ) ) &&
				$product->is_type( 'variable' )
			) {
				foreach ( $product->get_children() as $child_id ) {
					$this->take_product_stock_snapshot( $child_id, false, $res );
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
