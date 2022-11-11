<?php
/**
 * Stock Snapshot for WooCommerce - Pro - Shortcodes Class
 *
 * @version 1.2.0
 * @since   1.2.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Shortcodes' ) ) :

class Alg_WC_Stock_Snapshot_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function __construct() {
		if ( 'yes' === get_option( 'alg_wc_stock_snapshot_plugin_enabled', 'yes' ) ) {
			add_shortcode( 'alg_wc_stock_snapshot_restocked', array( $this, 'stock_snapshot_restocked_shortcode' ) );
		}
	}

	/**
	 * stock_snapshot_restocked_shortcode.
	 *
	 * @version 1.1.3
	 * @since   1.1.0
	 *
	 * @see     https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 * @see     https://docs.woocommerce.com/document/woocommerce-shortcodes/
	 *
	 * @todo    [now] (dev) `wc_get_products`: variations
	 * @todo    [next] (dev) `$check_stock`: make `null` optional? (i.e. `$check_stock = ( isset( $stock[ $check_num ] ) ? $stock[ $check_num ] : 0 )`)
	 * @todo    [later] (dev) `paginate`: "Sort by ..." dropdown may not match `orderby` and `order` atts
	 * @todo    [later] (dev) `pwb-brand-filter`: list only brands that are really on the page, otherwise the "Max number of brands" widget option must be set to max/all
	 * @todo    [later] (dev) "filter by price" widget compatibility
	 * @todo    [later] (dev) `$query_args`: non-empty `_alg_wc_stock_snapshot` meta
	 * @todo    [later] (dev) `$query_args`: `stock_quantity > 0`
	 * @todo    [later] (feature) more `ordering` options, e.g. by most restock diff
	 * @todo    [later] (feature) customizable period by *date* (i.e. instead of `total_snapshots`)
	 */
	function stock_snapshot_restocked_shortcode( $atts, $content = '' ) {
		// Atts
		$atts = shortcode_atts( array(
				'min_stock_diff'    => 1,
				'columns'           => 4,
				'paginate'          => 'no',
				'new_stock'         => 'no',
				'total_snapshots'   => 1,
				'orderby'           => 'name',
				'order'             => 'ASC',
				'not_found_msg'     => '<p>' . __( 'No restocked products found.', 'stock-snapshot-for-woocommerce' ) . '</p>',
			), $atts, 'alg_wc_stock_snapshot_restocked' );
		if ( isset( $_REQUEST['pwb-brand-filter'] ) ) {
			$atts['pwb-brand-filter'] = wc_clean( $_REQUEST['pwb-brand-filter'] );
		}
		// Get products
		$transient_params = base64_encode( http_build_query( $atts, '', ',' ) );
		if ( false === ( $transient = get_transient( 'alg_wc_stock_snapshot_restocked' ) ) || ! isset( $transient[ $transient_params ] ) ) {
			// Product query args
			$query_args = array(
					'limit'        => -1,
					'return'       => 'ids',
					'orderby'      => ( 'last_restocked' === $atts['orderby'] ? 'name' : $atts['orderby'] ),
					'order'        => ( 'last_restocked' === $atts['orderby'] ? 'ASC'  : $atts['order'] ),
					'stock_status' => 'instock',
				);
			if ( isset( $atts['pwb-brand-filter'] ) ) {
				$query_args['tax_query'] = array(
						array(
							'taxonomy' => 'pwb-brand',
							'field'    => 'slug',
							'terms'    => explode( ',', $atts['pwb-brand-filter'] ),
						),
					);
			}
			// Products loop
			$products = array();
			foreach ( wc_get_products( $query_args ) as $i => $product_id ) {
				$stock_snapshot = get_post_meta( $product_id, '_alg_wc_stock_snapshot', true );
				if ( ! empty( $stock_snapshot ) ) {
					$count = count( $stock_snapshot );
					$time  = array_keys( $stock_snapshot );
					$stock = array_values( $stock_snapshot );
					if ( 1 == $count ) {
						if ( 'yes' === $atts['new_stock'] && $stock[0] > 0 ) {
							$products[] = array( 'product_id' => $product_id, 'time' => $time[0] );
						}
					} else {
						$i      = 1;
						$latest = $count - 1;
						while ( $i <= $atts['total_snapshots'] && $i < $count ) {
							$check_num   = $count - ( $i + 1 );
							$check_stock = ( isset( $stock[ $check_num ] ) ? $stock[ $check_num ] : 0 );
							if ( ( $stock[ $latest ] - $check_stock ) >= $atts['min_stock_diff'] ) {
								$products[] = array( 'product_id' => $product_id, 'time' => $time[ $check_num + 1 ] );
								break;
							}
							$i++;
						}
					}
				}
			}
			// Custom sorting
			if ( 'last_restocked' === $atts['orderby'] ) {
				usort( $products, array( $this, ( 'ASC' === $atts['order'] ? 'sort_last_restocked_asc' : 'sort_last_restocked_desc' ) ) );
			}
			// Final product ids array
			$products = wp_list_pluck( $products, 'product_id' );
			// Set transient
			if ( false === $transient ) {
				$transient = array();
			}
			$transient[ $transient_params ] = $products;
			set_transient( 'alg_wc_stock_snapshot_restocked', $transient, 0 );
		} else {
			// From transient
			$products = $transient[ $transient_params ];
		}
		// Results
		if ( ! empty( $products ) ) {
			return do_shortcode( '[products ids="' . implode( ',', $products ) . '" limit="-1" columns="' . $atts['columns'] . '" orderby="post__in" paginate="' . $atts['paginate'] . '"]' );
		} else {
			return $atts['not_found_msg'];
		}
	}

	/**
	 * sort_last_restocked_asc.
	 *
	 * @version 1.1.3
	 * @since   1.1.3
	 */
	function sort_last_restocked_asc( $a, $b ) {
		return ( $a['time'] == $b['time'] ? 0 : ( $a['time'] < $b['time'] ? -1 : 1 ) );
	}

	/**
	 * sort_last_restocked_desc.
	 *
	 * @version 1.1.3
	 * @since   1.1.3
	 */
	function sort_last_restocked_desc( $a, $b ) {
		return ( $a['time'] == $b['time'] ? 0 : ( $a['time'] > $b['time'] ? -1 : 1 ) );
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Shortcodes();
