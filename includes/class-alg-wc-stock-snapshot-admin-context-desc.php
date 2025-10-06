<?php
/**
 * Stock Snapshot for WooCommerce - Admin Context Desc Class
 *
 * @version 2.2.2
 * @since   2.2.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Admin_Context_Desc' ) ) :

class Alg_WC_Stock_Snapshot_Admin_Context_Desc {

	/**
	 * Constructor.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function __construct() {
		return true;
	}

	/**
	 * get_all.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 *
	 * @todo    (v2.2.0) less info, e.g., only order ID?
	 */
	function get_all( $data, $product_id ) {
		$res = array();
		$res[] = $this->get_hook_desc( $data );
		$res[] = $this->get_request_uri_desc( $data, $product_id, false );
		$res[] = $this->get_order_id_desc( $data, false );
		return implode( '; ', array_filter( $res ) );
	}

	/**
	 * parse_uri.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function parse_uri( $uri ) {
		$base = false;
		if ( false !== strpos( $uri, '?' ) ) {
			$uri = explode( '?', $uri, 2 );
			if ( ! isset( $uri[1] ) ) {
				return array();
			}
			$base = $uri[0];
			$uri  = $uri[1];
		}
		parse_str( $uri, $res );
		return array( 'base' => $base, 'params' => $res );
	}

	/**
	 * get_hook_desc.
	 *
	 * @version 2.2.0
	 * @since   2.0.0
	 */
	function get_hook_desc( $data ) {

		if ( ! isset( $data['hook'] ) ) {
			return '';
		}

		switch ( $data['hook'] ) {

			case 'woocommerce_update_product':

				return __( 'product update', 'stock-snapshot-for-woocommerce' );

			case 'woocommerce_update_product_variation':

				return __( 'product variation update', 'stock-snapshot-for-woocommerce' );

			case 'alg_wc_stock_snapshot_settings_saved':

				return __( 'manual snapshot', 'stock-snapshot-for-woocommerce' );

			case 'alg_wc_stock_snapshot_action':
			case alg_wc_stock_snapshot()->core->action_scheduler->action:

				return __( 'periodic snapshot', 'stock-snapshot-for-woocommerce' );

			case 'init':

				return __( 'URL snapshot', 'stock-snapshot-for-woocommerce' );

			default:

				return $data['hook'];

		}

	}

	/**
	 * get_request_uri_desc.
	 *
	 * @version 2.2.2
	 * @since   2.2.0
	 *
	 * @todo    (v2.2.0) `return $data['request_uri'] . ' > ' . $data['post_type']`: better desc?
	 * @todo    (v2.2.0) `return $data['request_uri']`: better desc?
	 */
	function get_request_uri_desc( $data, $product_id, $is_short = true ) {

		if ( ! isset( $data['request_uri'] ) ) {
			return '';
		}

		$custom_desc = apply_filters(
			'alg_wc_stock_snapshot_request_uri_desc',
			false,
			$data,
			$product_id,
			$is_short
		);
		if ( false !== $custom_desc ) {
			return $custom_desc;
		}

		switch ( $data['request_uri'] ) {

			case '/?wc-ajax=checkout':

				return (
					$is_short ?
					__( 'checkout', 'stock-snapshot-for-woocommerce' ) :
					__( 'checkout page', 'stock-snapshot-for-woocommerce' )
				);

			case '/wp-admin/post.php':
				if ( isset( $data['post_type'] ) ) {
					if ( 'shop_order' === $data['post_type'] ) {

						return (
							$is_short ?
							__( 'order edit', 'stock-snapshot-for-woocommerce' ) :
							__( 'order edit page', 'stock-snapshot-for-woocommerce' )
						);

					} elseif ( 'product' === $data['post_type'] ) {

						return (
							$is_short ?
							__( 'product edit', 'stock-snapshot-for-woocommerce' ) :
							__( 'product edit page', 'stock-snapshot-for-woocommerce' )
						);

					} else {

						return $data['request_uri'] . ' > ' . $data['post_type'];

					}
				} else {

					return $data['request_uri'];

				}

			case '/wp-admin/edit.php?post_type=product&page=tom-product-editor':

				return __( 'Tom\'s product editor', 'stock-snapshot-for-woocommerce' );

			case '/wp-admin/admin-ajax.php':

				if ( isset( $data['action'] ) ) {

					switch ( $data['action'] ) {

						case 'woocommerce_save_variations':

							return __( 'save variations', 'stock-snapshot-for-woocommerce' );

						case 'woobe_update_page_field':

							return __( 'BEAR bulk editor', 'stock-snapshot-for-woocommerce' );

						case 'woocommerce_save_order_items':

							return __( 'save items', 'stock-snapshot-for-woocommerce' );

						case 'woocommerce_refund_line_items':

							return __( 'refund items', 'stock-snapshot-for-woocommerce' );

						default:

							return __( 'AJAX', 'stock-snapshot-for-woocommerce' ) . ' > ' . $data['action'];

					}

				}

				return __( 'AJAX', 'stock-snapshot-for-woocommerce' );

			default:
				$_data = $this->parse_uri( $data['request_uri'] );

				if (
					isset( $_data['params']['post'] ) &&
					is_numeric( $_data['params']['post'] )
				) {
					if ( 'shop_order' === get_post_type( $_data['params']['post'] ) ) {

						return (
							$is_short ?
							__( 'order edit', 'stock-snapshot-for-woocommerce' ) :
							__( 'order edit page', 'stock-snapshot-for-woocommerce' )
						);

					} elseif ( 'product' === get_post_type( $_data['params']['post'] ) ) {

						return (
							$is_short ?
							__( 'product edit', 'stock-snapshot-for-woocommerce' ) :
							__( 'product edit page', 'stock-snapshot-for-woocommerce' )
						);

					}
				}

				if (
					isset( $_data['params']['action'] ) &&
					'barcodeScannerAction' === $_data['params']['action']
				) {

					return __( 'barcode scanner', 'stock-snapshot-for-woocommerce' );

				}

				if (
					isset( $_data['params']['tom-frontend-stock-update-mode'] ) &&
					'1' === $_data['params']['tom-frontend-stock-update-mode']
				) {

					if ( isset( $_data['params'][ 'tom-frontend-stock-update' . $product_id ] ) ) {

						return __( 'frontend stock update', 'stock-snapshot-for-woocommerce' );

					}

					$child_ids = get_children( array(
						'post_parent' => $product_id,
						'post_type'   => 'product_variation',
						'fields'      => 'ids',
						'numberposts' => -1,
					) );
					foreach ( $child_ids as $child_id ) {
						if ( isset( $_data['params'][ 'tom-frontend-stock-update' . $child_id ] ) ) {

							return __( 'frontend stock update (variation)', 'stock-snapshot-for-woocommerce' );

						}
					}

				}

				if (
					'/wp-admin/admin-ajax.php' === $_data['base'] &&
					isset( $_data['params']['action'] ) &&
					'as_async_request_queue_runner' === $_data['params']['action']
				) {

					return __( 'action scheduler', 'stock-snapshot-for-woocommerce' );

				}

				return $data['request_uri'];

		}

	}

	/**
	 * get_order_id_desc.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function get_order_id_desc( $data, $is_short = true ) {
		$order_id = false;

		if ( isset( $data['order_id'] ) ) {

			$order_id = $data['order_id'];

		} elseif ( isset( $data['request_uri'] ) ) {

			$_data = $this->parse_uri( $data['request_uri'] );
			if (
				isset( $_data['params']['post'] ) &&
				is_numeric( $_data['params']['post'] ) &&
				'shop_order' === get_post_type( $_data['params']['post'] )
			) {
				$order_id = (int) $_data['params']['post'];
			}

		}

		if ( $order_id ) {
			return (
				$is_short ?
				$order_id :
				sprintf(
					/* Translators: %d: Order ID. */
					__( 'order #%d', 'stock-snapshot-for-woocommerce' ),
					$order_id
				)
			);
		}

		return '';
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Admin_Context_Desc();
