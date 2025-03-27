<?php
/**
 * Stock Snapshot for WooCommerce - Report Section Settings
 *
 * @version 2.1.0
 * @since   2.1.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings_Report' ) ) :

class Alg_WC_Stock_Snapshot_Settings_Report extends Alg_WC_Stock_Snapshot_Settings_Report_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function __construct() {

		$this->id   = 'report';
		$this->desc = __( 'Report', 'stock-snapshot-for-woocommerce' );

		$this->default_days                = 1;
		$this->do_use_datetime             = true;
		$this->do_add_user_selector        = true;
		$this->do_add_product_cat_selector = true;

		parent::__construct();

		add_action( 'admin_notices', array( $this, 'requirement_notice' ) );

	}

	/**
	 * requirement_notice.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function requirement_notice() {

		global $current_tab;
		if ( 'alg_wc_stock_snapshot' !== $current_tab ) {
			return;
		}

		global $current_section;
		if ( $this->id !== $current_section ) {
			return;
		}

		if ( ! alg_wc_stock_snapshot()->core->do_extra_data() ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'Enable the "Extra data" option on the "General" settings page to filter by the user.', 'stock-snapshot-for-woocommerce' ); ?></p>
			</div>
			<?php
		}

	}

	/**
	 * get_product_name.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_product_name( $child_product, $product ) {
		return (
			true === apply_filters( 'alg_wc_stock_snapshot_report_product_name_add_category_list', false ) ?
			sprintf(
				'%s%s (%s)',
				( $child_product->get_parent_id() ? '> ' : '' ),
				wp_strip_all_tags( $child_product->get_formatted_name() ),
				wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) )
			) :
			sprintf(
				'%s%s',
				( $child_product->get_parent_id() ? '> ' : '' ),
				wp_strip_all_tags( $child_product->get_formatted_name() )
			)
		);
	}

	/**
	 * get_data.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 *
	 * @todo    (v2.1.0) "no user" (vs. "any user")?
	 * @todo    (v2.1.0) filter by the `hook`?
	 */
	function get_data( $output_type = 'html' ) {

		// Try transient
		if ( 'html' === $output_type ) {
			$transient_id = http_build_query( array(
				'user_id'     => $this->user_id,
				'product_cat' => $this->product_cat,
				'after'       => $this->after,
				'before'      => $this->before,
			) );
			if ( false !== ( $transient = get_transient( 'alg_wc_stock_snapshot_report' ) ) ) {
				if ( isset( $transient[ $transient_id ] ) ) {
					return $transient[ $transient_id ];
				}
			} else {
				$transient = array();
			}
		}

		// Product query args
		$args = array(
			'return'              => 'ids',
			'limit'               => -1,
			'orderby'             => 'name',
			'order'               => 'ASC',
			'product_category_id' => $this->product_cat,
		);

		// Prepare rows
		$rows = array();
		foreach ( wc_get_products( $args ) as $product_id ) {

			if ( ! ( $product = wc_get_product( $product_id ) ) ) {
				continue;
			}

			$product_ids = array( $product_id );
			if (
				alg_wc_stock_snapshot()->core->do_variations() &&
				$product->is_type( 'variable' )
			) {
				$product_ids = array_merge( $product_ids, $product->get_children() );
			}

			foreach ( $product_ids as $_product_id ) {

				if ( ! ( $_product = wc_get_product( $_product_id ) ) ) {
					continue;
				}

				$snapshot = $_product->get_meta( '_alg_wc_stock_snapshot' );

				if ( empty( $snapshot ) ) {
					continue;
				}

				$snapshot = array_filter( $snapshot, array( $this, 'filter_not_null' ) );

				if ( empty( $snapshot ) ) {
					continue;
				}

				$last_stock = false;

				foreach ( $snapshot as $time => $_snapshot ) {

					$stock = ( is_array( $_snapshot ) ? $_snapshot['stock'] : $_snapshot );

					if (
						$time >= $this->after &&
						( ! $this->before || $time < $this->before ) &&
						(
							empty( $this->user_id ) ||
							(
								! empty( $_snapshot['user_id'] ) &&
								$_snapshot['user_id'] == $this->user_id
							)
						) &&
						0 !== ( $diff = ( (int) $stock - (int) $last_stock ) )
					) {

						$rows[] = array(
							'product_name'   => $this->get_product_name( $_product, $product ),
							'formatted_time' => alg_wc_stock_snapshot()->core->local_date( 'Y-m-d H:i:s', $time ),
							'last_stock'     => ( (int) $last_stock ),
							'diff'           => $diff,
							'stock'          => $stock,
							'hook_desc'      => (
								isset( $_snapshot['hook'] ) ?
								$this->get_admin()->get_hook_desc( $_snapshot['hook'] ) :
								''
							),
							'user_desc'      => (
								isset( $_snapshot['user_id'] ) ?
								$this->get_admin()->get_user_desc( $_snapshot['user_id'] ) :
								''
							),
						);

					}

					$last_stock = $stock;

				}

			}

		}

		// Table head
		if ( ! empty( $rows ) ) {
			$head = array(
				__( 'Product', 'stock-snapshot-for-woocommerce' ),
				__( 'Time', 'stock-snapshot-for-woocommerce' ),
				__( 'Before', 'stock-snapshot-for-woocommerce' ),
				__( 'Adjustment', 'stock-snapshot-for-woocommerce' ),
				__( 'After', 'stock-snapshot-for-woocommerce' ),
				__( 'Desc', 'stock-snapshot-for-woocommerce' ),
				__( 'User', 'stock-snapshot-for-woocommerce' ),
			);
		}

		// Reverse rows
		if ( ! empty( $rows ) ) {
			$rows = array_reverse( $rows );
		}

		// Output
		if ( 'html' === $output_type ) {

			ob_start();

			if ( ! empty( $rows ) ) {

				$this->get_admin()->stock_diff_style();

				?><table class="widefat striped"><?php

					?><thead><?php
						?><tr><?php
							foreach ( $head as $_head ) {
								?><th><?php echo esc_html( $_head ); ?></th><?php
							}
						?></tr><?php
					?></thead><?php

					?><tbody><?php
						foreach ( $rows as $row ) {
							?>
							<tr>
								<td><?php echo esc_html( $row['product_name'] ); ?></td>
								<td><?php echo esc_html( $row['formatted_time'] ); ?></td>
								<td><?php echo esc_html( $row['last_stock'] ); ?></td>
								<td><?php echo wp_kses_post( $this->get_admin()->get_stock_diff_html( $row['diff'] ) ); ?></td>
								<td><?php echo esc_html( $row['stock'] ); ?></td>
								<td><?php echo esc_html( $row['hook_desc'] ); ?></td>
								<td><?php echo esc_html( $row['user_desc'] ); ?></td>
							</tr>
							<?php
						}
					?></tbody><?php

				?></table><?php

			} else {

				echo wp_kses_post( $this->get_no_results_html() );

			}

			$result = ob_get_clean();

		} else { // 'csv'

			if ( ! empty( $rows ) ) {

				$result = array();

				$result[] = '"' . implode( '","', $head ) . '"';

				foreach ( $rows as $row ) {
					$result[] = '"' . implode( '","', $row ) . '"';
				}

				$result = implode( PHP_EOL, $result );

			} else {

				$result = '';

			}
		}

		// Export link
		if ( 'html' === $output_type ) {
			if ( ! empty( $rows ) ) {
				$result .= $this->get_export_csv_link();
			}
		}

		// Footer
		if ( 'html' === $output_type ) {
			$result .= $this->get_footer();
		}

		// Set transient
		if ( 'html' === $output_type ) {
			$transient[ $transient_id ] = $result;
			set_transient( 'alg_wc_stock_snapshot_report', $transient, 0 );
		}

		return $result;

	}

}

endif;

return new Alg_WC_Stock_Snapshot_Settings_Report();
