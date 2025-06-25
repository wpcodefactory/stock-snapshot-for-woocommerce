<?php
/**
 * Stock Snapshot for WooCommerce - Report Section Settings
 *
 * @version 2.2.0
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
	 * @version 2.2.0
	 * @since   2.1.0
	 */
	function __construct() {

		$this->id   = 'report';
		$this->desc = __( 'Report', 'stock-snapshot-for-woocommerce' );

		$this->default_days                = 1;
		$this->do_use_datetime             = true;
		$this->do_add_user_selector        = true;
		$this->do_add_product_cat_selector = true;
		$this->do_add_report_type_selector = true;

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
	 * @version 2.2.0
	 * @since   2.1.0
	 */
	function get_product_name( $child_product, $product ) {
		return (
			true === apply_filters( 'alg_wc_stock_snapshot_report_product_name_add_category_list', false ) ?
			sprintf(
				'%s (%s)',
				wp_strip_all_tags( $child_product->get_formatted_name() ),
				wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) )
			) :
			wp_strip_all_tags( $child_product->get_formatted_name() )
		);
	}

	/**
	 * get_raw_data.
	 *
	 * @version 2.2.0
	 * @since   2.1.1
	 */
	function get_raw_data( $args ) {

		// Try transient
		$transient_id = http_build_query(
			array(
				'user_id'       => $args['user_id'],
				'product_cat'   => $args['product_cat'],
				'after'         => $args['after'],
				'before'        => $args['before'],
				'do_variations' => alg_wc_stock_snapshot()->core->do_variations(),
				'version'       => ALG_WC_STOCK_SNAPSHOT_VERSION,
			)
		);
		if ( false !== ( $transient = get_transient( 'alg_wc_stock_snapshot_report_data' ) ) ) {
			if ( isset( $transient[ $transient_id ] ) ) {
				return $transient[ $transient_id ];
			}
		} else {
			$transient = array();
		}

		// Do in background
		if (
			$args['do_in_background'] &&
			function_exists( 'as_enqueue_async_action' ) &&
			function_exists( 'as_has_scheduled_action' )
		) {
			if ( ! as_has_scheduled_action(
				'alg_wc_stock_snapshot_report_action',
				array( 'args' => $args )
			) ) {
				as_enqueue_async_action(
					'alg_wc_stock_snapshot_report_action',
					array( 'args' => $args )
				);
			}
			return false;
		}

		// Product query args
		$product_query_args = array(
			'return'              => 'ids',
			'limit'               => -1,
			'orderby'             => 'name',
			'order'               => 'ASC',
			'product_category_id' => $args['product_cat'],
		);

		// Prepare rows
		$rows = array();
		foreach ( wc_get_products( $product_query_args ) as $product_id ) {

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

				if ( $_product_id === $product_id ) {
					$_product = $product;
				} elseif ( ! ( $_product = wc_get_product( $_product_id ) ) ) {
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
						$time >= $args['after'] &&
						( ! $args['before'] || $time < $args['before'] ) &&
						(
							empty( $args['user_id'] ) ||
							(
								! empty( $_snapshot['user_id'] ) &&
								$_snapshot['user_id'] == $args['user_id']
							)
						) &&
						0 !== ( $diff = ( (int) $stock - (int) $last_stock ) )
					) {

						$rows[] = array(
							'product_name'     => $this->get_product_name( $_product, $product ),
							'product_id'       => $_product->get_id(),
							'product_type'     => $_product->get_type(),
							'formatted_time'   => alg_wc_stock_snapshot()->core->local_date( 'Y-m-d H:i:s', $time ),
							'last_stock'       => ( (int) $last_stock ),
							'diff'             => $diff,
							'stock'            => $stock,
							'hook_desc'        => (
								isset( $_snapshot['hook'] ) ?
								$this->get_admin()->context_desc->get_hook_desc( $_snapshot ) :
								''
							),
							'request_uri_desc' => (
								isset( $_snapshot['request_uri'] ) ?
								$this->get_admin()->context_desc->get_request_uri_desc( $_snapshot, $_product_id ) :
								''
							),
							'order_id_desc'    => (
								( isset( $_snapshot['order_id'] ) || isset( $_snapshot['request_uri'] ) ) ?
								$this->get_admin()->context_desc->get_order_id_desc( $_snapshot ) :
								''
							),
							'user_desc'        => (
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

		// Reverse rows
		if ( ! empty( $rows ) ) {
			$rows = array_reverse( $rows );
		}

		// Set transient
		$transient[ $transient_id ] = $rows;
		set_transient( 'alg_wc_stock_snapshot_report_data', $transient, 0 );

		// Result
		return $rows;

	}

	/**
	 * loader_css_js.
	 *
	 * @version 2.1.1
	 * @since   2.1.1
	 *
	 * @todo    (v2.1.1) CSS: rename `spin`?
	 */
	function loader_css_js() {

		if (
			! isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) ||
			'wc-settings'           !== $_GET['page'] ||
			'alg_wc_stock_snapshot' !== $_GET['tab'] ||
			'report'                !== $_GET['section']
		) {
			return;
		}

		?>
		<script>
		jQuery( document ).ready( function () {
			var interval_id = setInterval( function () {
				jQuery.ajax( {
					type:     'POST',
					dataType: 'json',
					url:      ajaxurl,
					data:     {
						action:      'alg_wc_stock_snapshot_report',
						user_id:     '<?php echo intval( $this->user_id ); ?>',
						product_cat: '<?php echo intval( $this->product_cat ); ?>',
						after:       '<?php echo intval( $this->after ); ?>',
						before:      '<?php echo intval( $this->before ); ?>',
					},
					success:  function ( msg ) {
						if ( msg ) {
							clearInterval( interval_id );
							location.reload();
						}
					},
				} );
			}, 5000 );
		} );
		</script>
		<?php

		?>
		<style>
			.alg-wc-stock-snapshot-report-loader {
				border: 1px solid #3c434a;
				border-top: 1px solid #f0f0f1;
				border-radius: 50%;
				width: 8px;
				height: 8px;
				animation: spin 2s linear infinite;
				display: inline-block;
			}
			@keyframes spin {
				0% {
					transform: rotate( 0deg );
				}
				100% {
					transform: rotate( 360deg );
				}
			}
			.alg-wc-stock-snapshot-report-loader-wrapper {
				display: inline-block;
			}
		</style>
		<?php

	}

	/**
	 * get_data.
	 *
	 * @version 2.2.0
	 * @since   2.1.0
	 *
	 * @todo    (v2.1.0) optimize algorithm
	 * @todo    (v2.1.0) sortable columns, e.g., "Time"
	 * @todo    (v2.1.0) visually group by product
	 * @todo    (v2.1.0) "no user" (vs. "any user")?
	 * @todo    (v2.1.0) filter by the `hook`?
	 */
	function get_data( $output_type = 'html' ) {

		// Get data
		$rows = $this->get_raw_data(
			array(
				'user_id'          => $this->user_id,
				'product_cat'      => $this->product_cat,
				'after'            => $this->after,
				'before'           => $this->before,
				'do_in_background' => (
					'yes' === get_option( 'alg_wc_stock_snapshot_report_do_in_background', 'no' ) &&
					'html' === $output_type
				),
			)
		);

		// Output data
		if ( false === $rows ) {

			// Doing in background
			add_action( 'admin_footer', array( $this, 'loader_css_js' ) );
			ob_start();
			?>
			<div class="alg-wc-stock-snapshot-report-loader"></div>
			<p class="alg-wc-stock-snapshot-report-loader-wrapper"><strong><?php
				esc_html_e( 'Preparing data...', 'stock-snapshot-for-woocommerce' );
			?></strong></p>
			<?php
			$result = ob_get_clean();

		} elseif ( ! empty( $rows ) ) {

			$is_restocked = ( in_array(
				$this->report_type,
				array( 'restocked', 'restocked_excl_variations' )
			) );

			if ( $is_restocked ) {

				// Filter
				$_products = array();
				foreach ( $rows as $i => $row ) {
					if (
						$row['diff'] < 0 ||
						isset( $_products[ $row['product_id'] ] ) ||
						(
							'restocked_excl_variations' === $this->report_type &&
							'variation' === $row['product_type']
						)
					) {
						unset( $rows[ $i ] );
					} else {
						$_products[ $row['product_id'] ] = true;
						if ( $this->user_id ) {
							$rows[ $i ] = array(
								'product_name' => $row['product_name'],
								'user_desc'    => $row['user_desc'],
							);
						} else {
							$rows[ $i ] = array(
								'product_name' => $row['product_name'],
							);
						}
					}
				}

				// Sort
				usort( $rows, function ( $a, $b ) {
					return ( $a['product_name'] < $b['product_name'] ? -1 : 1 );
				} );

				// Table head
				if ( $this->user_id ) {
					$head = array(
						__( 'Product', 'stock-snapshot-for-woocommerce' ),
						__( 'User', 'stock-snapshot-for-woocommerce' ),
					);
				} else {
					$head = array(
						__( 'Product', 'stock-snapshot-for-woocommerce' ),
					);
				}

			} else {

				// Table head
				$head = array(
					__( 'Product', 'stock-snapshot-for-woocommerce' ),
					__( 'Product ID', 'stock-snapshot-for-woocommerce' ),
					__( 'Product type', 'stock-snapshot-for-woocommerce' ),
					__( 'Time', 'stock-snapshot-for-woocommerce' ),
					__( 'Before', 'stock-snapshot-for-woocommerce' ),
					__( 'Adjustment', 'stock-snapshot-for-woocommerce' ),
					__( 'After', 'stock-snapshot-for-woocommerce' ),
					__( 'Action', 'stock-snapshot-for-woocommerce' ),
					__( 'Context', 'stock-snapshot-for-woocommerce' ),
					__( 'Order ID', 'stock-snapshot-for-woocommerce' ),
					__( 'User', 'stock-snapshot-for-woocommerce' ),
				);

			}

			if ( in_array( $output_type, array( 'html', 'email' ) ) ) {

				ob_start();

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
							if ( $is_restocked ) {
								?>
								<tr>
									<td><?php echo esc_html( $row['product_name'] ); ?></td>
									<?php if ( $this->user_id ) { ?>
										<td><?php echo esc_html( $row['user_desc'] ); ?></td>
									<?php } ?>
								</tr>
								<?php
							} else {
								?>
								<tr>
									<td><?php echo esc_html( $row['product_name'] ); ?></td>
									<td><?php echo esc_html( $row['product_id'] ); ?></td>
									<td><?php echo esc_html( $row['product_type'] ); ?></td>
									<td><?php echo esc_html( $row['formatted_time'] ); ?></td>
									<td><?php echo esc_html( $row['last_stock'] ); ?></td>
									<td><?php echo wp_kses_post( $this->get_admin()->get_stock_diff_html( $row['diff'] ) ); ?></td>
									<td><?php echo esc_html( $row['stock'] ); ?></td>
									<td><?php echo esc_html( $row['hook_desc'] ); ?></td>
									<td><?php echo esc_html( $row['request_uri_desc'] ); ?></td>
									<td><?php echo esc_html( $row['order_id_desc'] ); ?></td>
									<td><?php echo esc_html( $row['user_desc'] ); ?></td>
								</tr>
								<?php
							}
						}
					?></tbody><?php

				?></table><?php

				$result = ob_get_clean();

				// Export link
				if ( 'html' === $output_type ) {
					$result .= $this->get_export_csv_link();
				}

			} else { // 'csv'

				$result = array();

				$result[] = '"' . implode( '","', $head ) . '"';

				foreach ( $rows as $row ) {
					$result[] = '"' . implode( '","', str_replace( '"', '\'', $row ) ) . '"';
				}

				$result = implode( PHP_EOL, $result );

			}

		} else {

			if ( in_array( $output_type, array( 'html', 'email' ) ) ) {

				ob_start();
				echo wp_kses_post( $this->get_no_results_html() );
				$result = ob_get_clean();

			} else { // 'csv'

				$result = '';

			}

		}

		// Footer
		if ( in_array( $output_type, array( 'html', 'email' ) ) ) {
			$result .= $this->get_footer();
		}

		// Result
		return $result;

	}

}

endif;

return new Alg_WC_Stock_Snapshot_Settings_Report();
