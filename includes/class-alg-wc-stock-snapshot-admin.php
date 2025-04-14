<?php
/**
 * Stock Snapshot for WooCommerce - Admin Class
 *
 * @version 2.1.1
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
	 * @version 2.1.1
	 * @since   1.2.0
	 */
	function __construct() {

		// Meta box
		add_action( 'add_meta_boxes', array( $this, 'add_stock_snapshot_meta_box' ) );

		// Export CSV
		add_action( 'admin_init', array( $this, 'export_csv' ) );

		// Tools
		add_action( 'alg_wc_stock_snapshot_settings_saved', array( $this, 'admin_tools' ) );

		// Report (background)
		add_action( 'alg_wc_stock_snapshot_report_action', array( $this, 'prepare_report_data' ) );
		add_action( 'wp_ajax_alg_wc_stock_snapshot_report', array( $this, 'report_ajax' ) );

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
	 * prepare_report_data.
	 *
	 * @version 2.1.1
	 * @since   2.1.1
	 */
	function prepare_report_data( $args ) {

		require_once plugin_dir_path( __FILE__ ) . 'settings/class-alg-wc-stock-snapshot-settings-section.php';
		require_once plugin_dir_path( __FILE__ ) . 'settings/class-alg-wc-stock-snapshot-settings-report-section.php';
		$report = require_once plugin_dir_path( __FILE__ ) . 'settings/class-alg-wc-stock-snapshot-settings-report.php';

		$args['do_in_background'] = false;
		$report->get_raw_data( $args );

	}

	/**
	 * report_ajax.
	 *
	 * @version 2.1.1
	 * @since   2.1.1
	 *
	 * @todo    (v2.1.1) return report HTML
	 */
	function report_ajax() {

		$transient_id = http_build_query(
			array(
				'user_id'     => (
					isset( $_REQUEST['user_id'] ) ?
					sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) ) :
					false
				),
				'product_cat' => (
					isset( $_REQUEST['product_cat'] ) ?
					sanitize_text_field( wp_unslash( $_REQUEST['product_cat'] ) ) :
					false
				),
				'after'       => (
					isset( $_REQUEST['after'] ) ?
					sanitize_text_field( wp_unslash( $_REQUEST['after'] ) ) :
					false
				),
				'before'      => (
					isset( $_REQUEST['before'] ) ?
					sanitize_text_field( wp_unslash( $_REQUEST['before'] ) ) :
					false
				),
				'do_variations' => $this->get_core()->do_variations(),
			)
		);

		$res = (
			(
				false !== ( $transient = get_transient( 'alg_wc_stock_snapshot_report_data' ) ) &&
				isset( $transient[ $transient_id ] )
			) ?
			1 :
			0
		);

		echo $res;
		die();

	}

	/**
	 * export_csv.
	 *
	 * @version 2.1.0
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
				sanitize_text_field( wp_unslash( $_GET['_alg_wc_stock_snapshot_export_csv_nonce'] ) ),
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
		if ( $this->get_core()->do_extra_data() ) {
			// Extra data
			$csv[] = sprintf( '%s,%s,%s,%s,%s',
				esc_html__( 'Date', 'stock-snapshot-for-woocommerce' ),
				esc_html__( 'Time', 'stock-snapshot-for-woocommerce' ),
				esc_html__( 'Stock', 'stock-snapshot-for-woocommerce' ),
				esc_html__( 'Desc', 'stock-snapshot-for-woocommerce' ),
				esc_html__( 'User', 'stock-snapshot-for-woocommerce' )
			);
		} else {
			// Simple
			$csv[] = sprintf( '%s,%s,%s',
				esc_html__( 'Date', 'stock-snapshot-for-woocommerce' ),
				esc_html__( 'Time', 'stock-snapshot-for-woocommerce' ),
				esc_html__( 'Stock', 'stock-snapshot-for-woocommerce' )
			);
		}
		if ( ( $stock_snapshot = $product->get_meta( '_alg_wc_stock_snapshot' ) ) ) {
			foreach ( $stock_snapshot as $time => $stock ) {
				$formatted_date = alg_wc_stock_snapshot()->core->local_date( 'Y-m-d', $time );
				$formatted_time = alg_wc_stock_snapshot()->core->local_date( 'H:i:s', $time );
				$_stock         = ( is_array( $stock ) ? $stock['stock'] : $stock );
				if ( $this->get_core()->do_extra_data() ) {
					// Extra data
					$csv[] = sprintf(
						'%s,%s,%s,%s,%s',
						$formatted_date,
						$formatted_time,
						$_stock,
						( is_array( $stock ) ? '"' . $this->get_hook_desc( $stock['hook'] ) . '"'    : '' ),
						( is_array( $stock ) ? '"' . $this->get_user_desc( $stock['user_id'] ) . '"' : '' )
					);
				} else {
					// Simple
					$csv[] = sprintf(
						'%s,%s,%s',
						$formatted_date,
						$formatted_time,
						$_stock,
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
						/* Translators: %s: Number of products. */
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
			$counter = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_alg_wc_stock_snapshot'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( method_exists( 'WC_Admin_Settings', 'add_message' ) ) {
				WC_Admin_Settings::add_message(
					sprintf(
						/* Translators: %s: Number of products. */
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
			$id      = 'alg-wc-stock-snapshot-extra-data';
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

		if ( false === $hook ) {
			return '';
		}

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
	 * @version 2.1.0
	 * @since   2.0.0
	 */
	function get_user_desc( $user_id ) {

		if ( false === $user_id ) {
			return '';
		}

		if (
			$user_id &&
			( $user = get_user_by( 'ID', $user_id ) ) &&
			( $display_name = $user->get( 'display_name' ) )
		) {

			return sprintf(
				'%s (#%d)',
				$display_name,
				$user_id
			);

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
	 * get_stock_diff_html.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_stock_diff_html( $diff ) {
		if ( 0 != $diff ) {

			$class     = 'alg_wc_stock_snapshot_' . ( $diff > 0 ? 'green' : 'red' );
			$diff_html = '<span class="' . $class . '">' .
				$diff .
			'</span>';

		} else {

			$diff_html = '';

		}
		return $diff_html;
	}

	/**
	 * stock_diff_style.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function stock_diff_style() {
		?>
		<style>
			.alg_wc_stock_snapshot_red {
				color: red;
			}
			.alg_wc_stock_snapshot_green {
				color: green;
			}
		</style>
		<?php
	}

	/**
	 * output_stock_snapshot.
	 *
	 * @version 2.1.0
	 * @since   1.3.0
	 *
	 * @todo    (feature) optionally show *full* product stock snapshot history
	 */
	function output_stock_snapshot( $product_id ) {

		// Get product's stock snapshot
		$stock_snapshot = get_post_meta( $product_id, '_alg_wc_stock_snapshot', true );

		if ( $stock_snapshot ) {

			// Vars
			$output     = array();
			$last_stock = false;
			$i          = 0;
			$size       = sizeof( $stock_snapshot );

			// Loop
			foreach ( $stock_snapshot as $time => $stock ) {

				// Extra data vs. Simple
				if ( is_array( $stock ) ) {
					$hook    = $stock['hook'];
					$user_id = $stock['user_id'];
					$stock   = $stock['stock'];
				} else {
					$hook    = false;
					$user_id = false;
				}

				// Row counter
				$i++;

				// Add row
				if (
					1 === $i ||
					$stock !== $last_stock ||
					$size === $i
				) {

					// Time
					$formatted_time = alg_wc_stock_snapshot()->core->local_date( 'Y-m-d H:i:s', $time );

					// Stock adjustment
					$diff      = ( (int) $stock - (int) $last_stock );
					$diff_html = $this->get_stock_diff_html( $diff );

					if ( $this->get_core()->do_extra_data() ) { // Extra data view

						// Row
						$row = '<tr>' .
							'<td>' . $formatted_time . '</td>' .
							'<td>' . $stock . '</td>' .
							'<td>' . $diff_html . '</td>' .
							'<td>' . $this->get_hook_desc( $hook ) . '</td>' .
							'<td>' . $this->get_user_desc( $user_id ) . '</td>' .
						'</tr>';

					} else { // Simple view

						// Stock adjustment
						if ( '' !== $diff_html ) {
							$diff_html = " ({$diff_html})";
						}

						// Extra data (tip)
						$tip = (
							(
								false !== $hook &&
								false !== $user_id
							) ?
							wc_help_tip(
								$this->get_hook_desc( $hook ) .
								' (' . $this->get_user_desc( $user_id ) . ')'
							) :
							''
						);

						// Row
						$row = '<tr>' .
							'<td>' . $formatted_time . '</td>' .
							'<td>' . $stock . $diff_html . $tip . '</td>' .
						'</tr>';

					}

					// Add row
					$output[] = $row;

					// Last stock
					$last_stock = $stock;

				}

			}

			// Style
			$this->stock_diff_style();

			// Table
			echo '<table class="widefat striped">';
			if ( $this->get_core()->do_extra_data() ) {
				// Extra data
				echo '<tr>' .
					'<th>' . esc_html__( 'Time', 'stock-snapshot-for-woocommerce' )       . '</th>' .
					'<th>' . esc_html__( 'Stock', 'stock-snapshot-for-woocommerce' )      . '</th>' .
					'<th>' . esc_html__( 'Adjustment', 'stock-snapshot-for-woocommerce' ) . '</th>' .
					'<th>' . esc_html__( 'Desc', 'stock-snapshot-for-woocommerce' )       . '</th>' .
					'<th>' . esc_html__( 'User', 'stock-snapshot-for-woocommerce' )       . '</th>' .
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
			echo '<p><a href="' . esc_url( $url ) . '">' .
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
	 * @version 2.1.0
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
				echo '<hr><h4>' .
					wp_kses_post( wc_get_formatted_variation( wc_get_product( $child_id ) ) ) .
				'</h4>';
				$this->output_stock_snapshot( $child_id );
			}
		}

	}

}

endif;

return new Alg_WC_Stock_Snapshot_Admin();
