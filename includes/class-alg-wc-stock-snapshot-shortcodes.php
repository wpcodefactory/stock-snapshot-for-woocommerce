<?php
/**
 * Stock Snapshot for WooCommerce - Pro - Shortcodes Class
 *
 * @version 2.3.0
 * @since   1.2.0
 *
 * @author WPFactory
 *
 * @package WPFactory\WC_Stock_Snapshot\Shortcodes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Shortcodes' ) ) :

	/**
	 * Alg_WC_Stock_Snapshot_Shortcodes class.
	 *
	 * @version 2.3.0
	 * @since   1.2.0
	 */
	class Alg_WC_Stock_Snapshot_Shortcodes {

		/**
		 * Constructor.
		 *
		 * @version 2.1.0
		 * @since   1.2.0
		 */
		public function __construct() {
			add_shortcode(
				'alg_wc_stock_snapshot_restocked',
				array( $this, 'stock_snapshot_restocked_shortcode' )
			);
		}

		/**
		 * Stock snapshot restocked shortcode.
		 *
		 * @version 2.3.0
		 * @since   1.1.0
		 *
		 * @param array $atts Shortcode attributes.
		 *
		 * @return string Shortcode output.
		 *
		 * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
		 * @see https://docs.woocommerce.com/document/woocommerce-shortcodes/
		 *
		 * @todo (dev) `new_stock`: Rethink.
		 * @todo (dev) `wc_get_products`: Variations.
		 * @todo (dev) `$check_stock`: Make `null` optional? (i.e., `$check_stock = ( isset( $stock[ $check_num ] ) ? $stock[ $check_num ] : 0 )`).
		 * @todo (dev) `paginate`: "Sort by ..." dropdown may not match `orderby` and `order` atts.
		 * @todo (dev) `pwb-brand-filter`: List only brands that are really on the page, otherwise the "Max number of brands" widget option must be set to max/all.
		 * @todo (dev) "Filter by price" widget compatibility.
		 * @todo (dev) `$query_args`: Non-empty `_alg_wc_stock_snapshot` meta.
		 * @todo (dev) `$query_args`: `stock_quantity > 0`.
		 * @todo (feature) More `ordering` options, e.g., by most restock diff.
		 * @todo (feature) Customizable period by *date* (i.e., instead of `total_snapshots`).
		 */
		public function stock_snapshot_restocked_shortcode( $atts ) {
			// Atts.
			$atts = shortcode_atts(
				array(
					'min_stock_diff'  => 1,
					'columns'         => 4,
					'paginate'        => 'no',
					'new_stock'       => 'no',
					'total_snapshots' => 1,
					'orderby'         => 'name',
					'order'           => 'ASC',
					'not_found_msg'   => '<p>' . __( 'No restocked products found.', 'stock-snapshot-for-woocommerce' ) . '</p>',
				),
				$atts,
				'alg_wc_stock_snapshot_restocked'
			);
			if ( isset( $_REQUEST['pwb-brand-filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$atts['pwb-brand-filter'] = sanitize_text_field( wp_unslash( $_REQUEST['pwb-brand-filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			// Get products.
			$transient_params = base64_encode( http_build_query( $atts, '', ',' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$transient        = get_transient( 'alg_wc_stock_snapshot_restocked' );
			if (
				false === $transient ||
				! isset( $transient[ $transient_params ] )
			) {
				// Product query args.
				$query_args = array(
					'limit'        => -1,
					'return'       => 'ids',
					'orderby'      => ( 'last_restocked' === $atts['orderby'] ? 'name' : $atts['orderby'] ),
					'order'        => ( 'last_restocked' === $atts['orderby'] ? 'ASC' : $atts['order'] ),
					'stock_status' => 'instock',
				);
				if ( isset( $atts['pwb-brand-filter'] ) ) {
					$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'pwb-brand',
						'field'    => 'slug',
						'terms'    => explode( ',', $atts['pwb-brand-filter'] ),
					),
					);
				}

				// Products loop.
				$products = array();
				foreach ( wc_get_products( $query_args ) as $i => $product_id ) {
					$stock_snapshot = get_post_meta( $product_id, '_alg_wc_stock_snapshot', true );
					if ( ! empty( $stock_snapshot ) ) {
						$count = count( $stock_snapshot );
						$time  = array_keys( $stock_snapshot );
						$stock = array_values( $stock_snapshot );
						if ( 1 === $count ) {
							$_stock = (
								is_array( $stock[0] ) ?
								$stock[0]['stock'] :
								$stock[0]
							);
							if (
								'yes' === $atts['new_stock'] &&
								$_stock > 0
							) {
								$products[] = array(
									'product_id' => $product_id,
									'time'       => $time[0],
								);
							}
						} else {
							$i      = 1;
							$latest = $count - 1;
							while ( $i <= $atts['total_snapshots'] && $i < $count ) {
								$check_num    = $count - ( $i + 1 );
								$check_stock  = (
									isset( $stock[ $check_num ] ) ?
									(
										is_array( $stock[ $check_num ] ) ?
										$stock[ $check_num ]['stock'] :
										$stock[ $check_num ]
									) :
									0
								);
								$stock_latest = (
									is_array( $stock[ $latest ] ) ?
									$stock[ $latest ]['stock'] :
									$stock[ $latest ]
								);
								if ( ( $stock_latest - $check_stock ) >= $atts['min_stock_diff'] ) {
									$products[] = array(
										'product_id' => $product_id,
										'time'       => $time[ $check_num + 1 ],
									);
									break;
								}
								++$i;
							}
						}
					}
				}

				// Custom sorting.
				if ( 'last_restocked' === $atts['orderby'] ) {
					usort(
						$products,
						array(
							$this,
							(
								'ASC' === $atts['order'] ?
								'sort_last_restocked_asc' :
								'sort_last_restocked_desc'
							),
						)
					);
				}

				// Final product ids array.
				$products = wp_list_pluck( $products, 'product_id' );

				// Set transient.
				if ( false === $transient ) {
					$transient = array();
				}
				$transient[ $transient_params ] = $products;
				set_transient( 'alg_wc_stock_snapshot_restocked', $transient, 0 );
			} else {
				// From transient.
				$products = $transient[ $transient_params ];
			}

			// Results.
			if ( ! empty( $products ) ) {
				return do_shortcode(
					'[products' .
						' ids="' . implode( ',', array_map( 'absint', $products ) ) . '"' .
						' limit="-1"' .
						' columns="' . absint( $atts['columns'] ) . '"' .
						' orderby="post__in"' .
						' paginate="' . ( wc_string_to_bool( $atts['paginate'] ) ? 'true' : 'false' ) . '"' .
					']'
				);
			} else {
				return wp_kses_post( $atts['not_found_msg'] );
			}
		}

		/**
		 * Sort last restocked ascending.
		 *
		 * @version 2.3.0
		 * @since   1.1.3
		 *
		 * @param array $a First array.
		 * @param array $b Second array.
		 *
		 * @return int Comparison result.
		 */
		public function sort_last_restocked_asc( $a, $b ) {
			return ( $a['time'] === $b['time'] ? 0 : ( $a['time'] < $b['time'] ? -1 : 1 ) );
		}

		/**
		 * Sort last restocked descending.
		 *
		 * @version 2.3.0
		 * @since   1.1.3
		 *
		 * @param array $a First array.
		 * @param array $b Second array.
		 *
		 * @return int Comparison result.
		 */
		public function sort_last_restocked_desc( $a, $b ) {
			return ( $a['time'] === $b['time'] ? 0 : ( $a['time'] > $b['time'] ? -1 : 1 ) );
		}
	}

endif;

return new Alg_WC_Stock_Snapshot_Shortcodes();
