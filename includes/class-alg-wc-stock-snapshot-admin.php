<?php
/**
 * Stock Snapshot for WooCommerce - Admin Class
 *
 * @version 2.0.0
 * @since   1.2.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Admin' ) ) :

class Alg_WC_Stock_Snapshot_Admin {

	/**
	 * Constructor.
	 *
	 * @version 1.5.0
	 * @since   1.2.0
	 */
	function __construct() {

		if ( 'yes' === get_option( 'alg_wc_stock_snapshot_plugin_enabled', 'yes' ) ) {

			// Meta box
			add_action( 'add_meta_boxes', array( $this, 'add_stock_snapshot_meta_box' ) );

			// Export CSV
			add_action( 'admin_init', array( $this, 'export_csv' ) );

		}

		// Tools
		add_action( 'alg_wc_stock_snapshot_settings_saved', array( $this, 'admin_tools' ) );

	}

	/**
	 * get_core.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_core() {
		return alg_wc_stock_snapshot()->core;
	}

	/**
	 * export_csv.
	 *
	 * @version 2.0.0
	 * @since   1.5.0
	 */
	function export_csv() {

		// Check
		if ( empty( $_GET['alg_wc_stock_snapshot_export_csv'] ) ) {
			return;
		}

		if ( ! ( $product_id = absint( $_GET['alg_wc_stock_snapshot_export_csv'] ) ) ) {
			wp_die( esc_html__( 'Invalid product ID.', 'stock-snapshot-for-woocommerce' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Invalid user role.', 'stock-snapshot-for-woocommerce' ) );
		}

		if (
			! isset( $_GET['_alg_wc_stock_snapshot_export_csv_nonce'] ) ||
			! wp_verify_nonce(
				$_GET['_alg_wc_stock_snapshot_export_csv_nonce'],
				"alg_wc_stock_snapshot_export_csv_{$product_id}"
			)
		) {
			wp_die( esc_html__( 'Invalid nonce.', 'stock-snapshot-for-woocommerce' ) );
		}

		if ( ! ( $product = wc_get_product( $product_id ) ) ) {
			wp_die( esc_html__( 'Invalid product.', 'stock-snapshot-for-woocommerce' ) );
		}

		// Data
		$csv = array();
		$csv[] = sprintf( '%s,%s,%s',
			esc_html__( 'Date', 'stock-snapshot-for-woocommerce' ),
			esc_html__( 'Time', 'stock-snapshot-for-woocommerce' ),
			esc_html__( 'Stock', 'stock-snapshot-for-woocommerce' )
		);
		if ( ( $stock_snapshot = $product->get_meta( '_alg_wc_stock_snapshot' ) ) ) {
			foreach ( $stock_snapshot as $time => $stock ) {
				if ( $this->get_core()->do_extra_data() ) {
					// Extra data
					$csv[] = sprintf(
						'%s,%s,%s,%s,%s',
						date( 'Y-m-d', $time ),
						date( 'H:i:s', $time ),
						( $stock['stock']   ?? $stock ),
						( isset( $stock['hook'] )    ? '"' . $this->get_hook_desc( $stock['hook'] ) . '"'    : '' ),
						( isset( $stock['user_id'] ) ? '"' . $this->get_user_desc( $stock['user_id'] ) . '"' : '' )
					);
				} else {
					// Simple
					$csv[] = sprintf(
						'%s,%s,%s',
						date( 'Y-m-d', $time ),
						date( 'H:i:s', $time ),
						( $stock['stock'] ?? $stock )
					);
				}
			}
		}
		$csv = implode( PHP_EOL, $csv );

		// CSV
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . "stock-snapshot-product-{$product_id}.csv" );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv;
		die();

	}

	/**
	 * admin_tools.
	 *
	 * @version 2.0.0
	 * @since   1.1.3
	 *
	 * @todo    (dev) `current_user_can( 'manage_woocommerce' )`?
	 */
	function admin_tools() {

		// Clearing plugin transients on every settings save
		$this->get_core()->delete_transients();

		// Take snapshot
		if ( 'yes' === get_option( 'alg_wc_stock_snapshot_tools_take_snapshot', 'no' ) ) {
			update_option( 'alg_wc_stock_snapshot_tools_take_snapshot', 'no' );
			$counter = $this->get_core()->take_stock_snapshot( false );
			if ( method_exists( 'WC_Admin_Settings', 'add_message' ) ) {
				WC_Admin_Settings::add_message(
					sprintf(
						__( 'Snapshot taken for %s product(s).', 'stock-snapshot-for-woocommerce' ),
						$counter
					)
				);
			}
		}

		// Delete all snapshots
		if ( 'yes' === get_option( 'alg_wc_stock_snapshot_tools_delete_snapshots', 'no' ) ) {
			update_option( 'alg_wc_stock_snapshot_tools_delete_snapshots', 'no' );
			global $wpdb;
			$counter = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_alg_wc_stock_snapshot'" );
			if ( method_exists( 'WC_Admin_Settings', 'add_message' ) ) {
				WC_Admin_Settings::add_message(
					sprintf(
						__( '%s product snapshots deleted.', 'stock-snapshot-for-woocommerce' ),
						$counter
					)
				);
			}
		}

		// Clear plugin transients
		if ( 'yes' === get_option( 'alg_wc_stock_snapshot_clear_transients', 'no' ) ) {
			update_option( 'alg_wc_stock_snapshot_clear_transients', 'no' );
			if ( method_exists( 'WC_Admin_Settings', 'add_message' ) ) {
				WC_Admin_Settings::add_message(
					__( 'Plugin transients cleared.', 'stock-snapshot-for-woocommerce' )
				);
			}
		}

	}

	/**
	 * add_stock_snapshot_meta_box.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 */
	function add_stock_snapshot_meta_box() {

		if ( $this->get_core()->do_extra_data() ) {
			$id      = 'alg-wc-stock-snapshot-full';
			$context = 'advanced';
		} else {
			$id      = 'alg-wc-stock-snapshot';
			$context = 'side';
		}

		add_meta_box(
			$id,
			__( 'Stock Snapshot', 'stock-snapshot-for-woocommerce' ),
			array( $this, 'create_stock_snapshot_meta_box' ),
			'product',
			$context,
			'low'
		);

	}

	/**
	 * get_hook_desc.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_hook_desc( $hook ) {
		switch ( $hook ) {

			case 'woocommerce_update_product':
				return __( 'Product update', 'stock-snapshot-for-woocommerce' );

			case 'woocommerce_update_product_variation':
				return __( 'Product variation update', 'stock-snapshot-for-woocommerce' );

			case 'alg_wc_stock_snapshot_settings_saved':
				return __( 'Manual snapshot', 'stock-snapshot-for-woocommerce' );

			case 'alg_wc_stock_snapshot_action':
			case $this->get_core()->action_scheduler->action:
				return __( 'Periodic snapshot', 'stock-snapshot-for-woocommerce' );

			case 'init':
				return __( 'URL snapshot', 'stock-snapshot-for-woocommerce' );

			default:
				return $hook;

		}
	}

	/**
	 * get_user_desc.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_user_desc( $user_id ) {

		if (
			$user_id &&
			( $user = get_user_by( 'ID', $user_id ) ) &&
			( $display_name = $user->get( 'display_name' ) )
		) {

			return $display_name;

		} elseif ( 0 == $user_id ) {

			return __( 'Guest', 'stock-snapshot-for-woocommerce' );

		} else {

			return sprintf(
				/* Translators: %d: User ID. */
				__( 'User #%d', 'stock-snapshot-for-woocommerce' ),
				$user_id
			);

		}

	}

	/**
	 * output_stock_snapshot.
	 *
	 * @version 2.0.0
	 * @since   1.3.0
	 *
	 * @todo    (feature) optionally show *full* product stock snapshot history
	 */
	function output_stock_snapshot( $product_id ) {

		$stock_snapshot = get_post_meta( $product_id, '_alg_wc_stock_snapshot', true );

		if ( $stock_snapshot ) {

			// Table
			$output     = array();
			$last_stock = false;
			$i          = 0;
			$size       = sizeof( $stock_snapshot );

			foreach ( $stock_snapshot as $time => $stock ) {

				if ( is_array( $stock ) ) {
					$hook    = $stock['hook'];
					$user_id = $stock['user_id'];
					$stock   = $stock['stock'];
				} else {
					$hook    = false;
					$user_id = false;
				}

				$i++;

				if (
					1 === $i ||
					$stock !== $last_stock ||
					$size === $i
				) {

					$tip = (
						(
							! $this->get_core()->do_extra_data() &&
							false !== $hook &&
							false !== $user_id
						) ?
						wc_help_tip(
							$this->get_hook_desc( $hook ) .
							' (' . $this->get_user_desc( $user_id ) . ')'
						) :
						''
					);

					$row = '<tr>' .
						'<td>' . date( 'Y-m-d H:i:s', $time ) . '</td>' .
						'<td>' . $stock . $tip . '</td>';
					if ( $this->get_core()->do_extra_data() ) {
						$row .= (
							'<td>' . ( false !== $hook    ? $this->get_hook_desc( $hook )    : '' ) . '</td>' .
							'<td>' . ( false !== $user_id ? $this->get_user_desc( $user_id ) : '' ) . '</td>'
						);
					}
					$row .= '</tr>';

					$output[] = $row;

					$last_stock = $stock;

				}
			}
			echo '<table class="widefat striped">';
			if ( $this->get_core()->do_extra_data() ) {
				echo '<tr>' .
					'<th>' . esc_html__( 'Time', 'stock-snapshot-for-woocommerce' )  . '</th>' .
					'<th>' . esc_html__( 'Stock', 'stock-snapshot-for-woocommerce' ) . '</th>' .
					'<th>' . esc_html__( 'Desc', 'stock-snapshot-for-woocommerce' )  . '</th>' .
					'<th>' . esc_html__( 'User', 'stock-snapshot-for-woocommerce' )  . '</th>' .
				'</tr>';
			}
			echo wp_kses_post( implode( '', array_reverse( $output ) ) );
			echo '</table>';

			// "Export to CSV" link
			$url = wp_nonce_url(
				add_query_arg( 'alg_wc_stock_snapshot_export_csv', $product_id ),
				"alg_wc_stock_snapshot_export_csv_{$product_id}",
				'_alg_wc_stock_snapshot_export_csv_nonce'
			);
			echo '<p><a href="' . $url . '">' .
				esc_html__( 'Export to CSV', 'stock-snapshot-for-woocommerce' ) .
			'</a></p>';

		} else {

			// No data
			echo '<p><em>' .
				esc_html__( 'No data yet.', 'stock-snapshot-for-woocommerce' ) .
			'</em></p>';

		}

	}

	/**
	 * create_stock_snapshot_meta_box.
	 *
	 * @version 2.0.0
	 * @since   1.0.0
	 *
	 * @todo    (dev) `wc_get_formatted_variation()`?
	 */
	function create_stock_snapshot_meta_box() {

		// Product
		$this->output_stock_snapshot( get_the_ID() );

		// Product variations
		if (
			$this->get_core()->do_variations() &&
			( $product = wc_get_product( get_the_ID() ) ) &&
			$product->is_type( 'variable' )
		) {
			foreach ( $product->get_children() as $child_id ) {
				echo '<hr><h4>' . wc_get_formatted_variation( wc_get_product( $child_id ) ) . '</h4>';
				$this->output_stock_snapshot( $child_id );
			}
		}

	}

}

endif;

return new Alg_WC_Stock_Snapshot_Admin();
