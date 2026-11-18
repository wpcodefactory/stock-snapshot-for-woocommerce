<?php
/**
 * Stock Snapshot for WooCommerce - Core Class
 *
 * @version 2.3.1
 * @since   1.0.0
 *
 * @author WPFactory
 *
 * @package WPFactory\WC_Stock_Snapshot
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Core' ) ) :

	/**
	 * Alg_WC_Stock_Snapshot_Core class.
	 *
	 * @version 2.3.1
	 * @since   1.0.0
	 */
	class Alg_WC_Stock_Snapshot_Core {

		/**
		 * Action scheduler.
		 *
		 * @version 1.5.0
		 * @since   1.5.0
		 *
		 * @var Alg_WC_Stock_Snapshot_Action_Scheduler
		 */
		public $action_scheduler;

		/**
		 * Admin.
		 *
		 * @version 2.0.0
		 * @since   2.0.0
		 *
		 * @var Alg_WC_Stock_Snapshot_Admin
		 */
		public $admin;

		/**
		 * Constructor.
		 *
		 * @version 2.1.0
		 * @since   1.0.0
		 *
		 * @todo (feature) Log.
		 */
		public function __construct() {
			// Admin.
			$this->admin = require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-stock-snapshot-admin.php';

			// Action Scheduler.
			$this->action_scheduler = require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-stock-snapshot-action-scheduler.php';

			// Snapshots via URL.
			if ( 'yes' === get_option( 'alg_wc_stock_snapshot_url', 'no' ) ) {
				add_action( 'init', array( $this, 'snapshot_via_url' ) );
			}

			// "Product update" snapshot.
			if ( 'yes' === get_option( 'alg_wc_stock_snapshot_product_update', 'yes' ) ) {
				// Update product.
				add_action(
					'woocommerce_update_product',
					array( $this, 'product_update_snapshot' ),
					PHP_INT_MAX
				);

				// Update product variation.
				add_action(
					'woocommerce_update_product_variation',
					array( $this, 'product_variation_update_snapshot' ),
					PHP_INT_MAX
				);
			}

			// Shortcodes.
			require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-stock-snapshot-shortcodes.php';

			// Core loaded.
			do_action( 'alg_wc_stock_snapshot_core_loaded' );
		}

		/**
		 * Local date.
		 *
		 * @version 2.1.0
		 * @since   2.0.1
		 *
		 * @param string    $format           Date format. Default 'Y-m-d H:i:s'.
		 * @param int|false $server_timestamp Server timestamp. Default false.
		 *
		 * @return string Local date.
		 */
		public function local_date( $format = 'Y-m-d H:i:s', $server_timestamp = false ) {
			return get_date_from_gmt(
				gmdate(
					'Y-m-d H:i:s',
					(
						false === $server_timestamp ?
						time() :
						$server_timestamp
					)
				),
				$format
			);
		}

		/**
		 * Local time.
		 *
		 * @version 2.1.0
		 * @since   2.1.0
		 *
		 * @param int|false $server_timestamp Server timestamp. Default false.
		 *
		 * @return int Local time.
		 */
		public function local_time( $server_timestamp = false ) {
			return strtotime( $this->local_date( 'Y-m-d H:i:s', $server_timestamp ) );
		}

		/**
		 * GMT date.
		 *
		 * @version 2.1.0
		 * @since   2.1.0
		 *
		 * @param int $local_timestamp Local timestamp.
		 *
		 * @return string GMT date.
		 */
		public function gmt_date( $local_timestamp ) {
			return get_gmt_from_date( date( 'Y-m-d H:i:s', $local_timestamp ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		}

		/**
		 * GMT time.
		 *
		 * @version 2.1.0
		 * @since   2.1.0
		 *
		 * @param int $local_timestamp Local timestamp.
		 *
		 * @return int GMT time.
		 */
		public function gmt_time( $local_timestamp ) {
			return strtotime( $this->gmt_date( $local_timestamp ) );
		}

		/**
		 * Product update snapshot.
		 *
		 * @version 2.0.0
		 * @since   2.0.0
		 *
		 * @param int $product_id Product ID.
		 *
		 * @return void
		 *
		 * @todo (dev) More hooks, e.g., `woocommerce_process_product_meta`?
		 */
		public function product_update_snapshot( $product_id ) {
			$this->take_product_stock_snapshot( $product_id, $this->do_count_children() );

			$this->delete_transients();
		}

		/**
		 * Product variation update snapshot.
		 *
		 * @version 2.0.0
		 * @since   2.0.0
		 *
		 * @param int $product_id Product ID.
		 *
		 * @return void
		 */
		public function product_variation_update_snapshot( $product_id ) {
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
		 * Delete transients.
		 *
		 * @version 2.1.1
		 * @since   1.2.0
		 */
		public function delete_transients() {
			delete_transient( 'alg_wc_stock_snapshot_restocked' );
			delete_transient( 'alg_wc_stock_snapshot_history' );
			delete_transient( 'alg_wc_stock_snapshot_report_data' );
			delete_transient( 'alg_wc_stock_snapshot_report' ); // Deprecated.
		}

		/**
		 * Snapshot via URL.
		 *
		 * @version 2.3.1
		 * @since   1.2.0
		 */
		public function snapshot_via_url() {
			if ( ! isset( $_GET['alg_wc_stock_snapshot'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			if ( sanitize_text_field( wp_unslash( $_GET['alg_wc_stock_snapshot'] ) ) !== get_option( 'alg_wc_stock_snapshot_url_secret', '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				die( esc_html__( 'Invalid URL secret key.', 'stock-snapshot-for-woocommerce' ) );
			}

			$this->take_stock_snapshot();
		}

		/**
		 * Do variations.
		 *
		 * @version 1.3.0
		 * @since   1.3.0
		 */
		public function do_variations() {
			return ( 'yes' === get_option( 'alg_wc_stock_snapshot_include_variations', 'yes' ) );
		}

		/**
		 * Do count children.
		 *
		 * @version 2.0.0
		 * @since   2.0.0
		 */
		public function do_count_children() {
			return ( 'yes' === get_option( 'alg_wc_stock_snapshot_count_children', 'no' ) );
		}

		/**
		 * Do extra data.
		 *
		 * @version 2.1.0
		 * @since   2.0.0
		 */
		public function do_extra_data() {
			return ( 'yes' === get_option( 'alg_wc_stock_snapshot_extra_data', 'yes' ) );
		}

		/**
		 * Get order ID.
		 *
		 * @version 2.2.0
		 * @since   2.2.0
		 *
		 * @todo (v2.2.0) Check for `$_REQUEST['post']` (and `'shop_order' === get_post_type()`)?
		 */
		public function get_order_id() {
			if (
				isset( $_REQUEST['order_id'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				( $order_id = (int) $_REQUEST['order_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
			) {
				return $order_id;
			}

			switch ( $_SERVER['REQUEST_URI'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				// Checkout page.
				case '/?wc-ajax=checkout':
					if (
						WC()->session &&
						( $order_id = WC()->session->get( 'order_awaiting_payment' ) ) // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					) {
						return $order_id;
					}
					break;

				// Admin order edit page.
				case '/wp-admin/post.php':
					if (
						// phpcs:disable WordPress.Security.NonceVerification.Recommended
						isset( $_REQUEST['post_type'], $_REQUEST['post_ID'] ) &&
						'shop_order' === sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) ) &&
						( $order_id = (int) $_REQUEST['post_ID'] ) // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
						// phpcs:enable WordPress.Security.NonceVerification.Recommended
					) {
						return $order_id;
					}
					break;
			}

			return false;
		}

		/**
		 * Take product stock snapshot.
		 *
		 * @version 2.3.0
		 * @since   1.0.0
		 *
		 * @param int  $product_id        Product ID.
		 * @param bool $do_count_children Whether to count children.
		 * @param int  $index             Index.
		 *
		 * @return void
		 *
		 * @todo (v2.2.0) Prevent duplicated records?
		 * @todo (dev) Save each snapshot in a separate meta (i.e., instead of all snapshots in a single array)?
		 */
		public function take_product_stock_snapshot( $product_id, $do_count_children = false, $index = 0 ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				// Get product stock quantity.
				$stock = $product->get_stock_quantity();

				// Count children.
				if (
					$do_count_children &&
					$product->has_child() &&
					$product->is_type( 'variable' )
				) {
					foreach ( $product->get_children() as $child_id ) {
						if (
							( $child = wc_get_product( $child_id ) ) && // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
							null !== ( $child_stock = $child->get_stock_quantity() ) // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
						) {
							if ( null === $stock ) {
								$stock = 0;
							}
							$stock += $child_stock;
						}
					}
				}

				// Get previous snapshot.
				$stock_snapshot = get_post_meta( $product_id, '_alg_wc_stock_snapshot', true );
				if ( ! $stock_snapshot ) {
					$stock_snapshot = array();
					$prev_stock     = '-';
				} else {
					$prev_stock = end( $stock_snapshot );
					if ( is_array( $prev_stock ) ) {
						$prev_stock = $prev_stock['stock'];
					}
				}

				// Add new snapshot.
				if ( $this->do_extra_data() ) { // Extra data.
					$data = array(
						'stock'   => $stock,
						'hook'    => current_filter(),
						'user_id' => get_current_user_id(),
					);

					// Request URI, Order ID, etc.
					if ( isset( $_SERVER['REQUEST_URI'] ) ) {
						$data['request_uri'] = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
						if (
							'/wp-admin/admin-ajax.php' === $data['request_uri'] &&
							isset( $_REQUEST['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						) {
							$data['action'] = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						}
						if ( isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$data['post_type'] = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						}
						$order_id = $this->get_order_id();
						if ( $order_id ) {
							$data['order_id'] = $order_id;
						}
					}

					$stock_snapshot[ time() ] = $data;
				} else { // Simple.
					$stock_snapshot[ time() ] = $stock;
				}

				// Save.
				update_post_meta( $product_id, '_alg_wc_stock_snapshot', $stock_snapshot );

				// Action.
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
		 * Take stock snapshot.
		 *
		 * @version 2.0.0
		 * @since   1.0.0
		 *
		 * @param bool $do_die Whether to die after taking the snapshot.
		 *
		 * @return int
		 */
		public function take_stock_snapshot( $do_die = true ) {
			$res = 0;

			do_action( 'alg_wc_stock_snapshot_take_snapshot_start' );

			$do_count_children = $this->do_count_children();
			$do_variations     = $this->do_variations();

			foreach (
				wc_get_products(
					array(
						'limit'   => -1,
						'return'  => 'ids',
						'orderby' => 'ID',
						'order'   => 'ASC',
					)
				) as $product_id
			) {
				// Take product stock snapshot.
				$this->take_product_stock_snapshot( $product_id, $do_count_children, $res );
				++$res;

				// Do variations.
				if (
					$do_variations &&
					( $product = wc_get_product( $product_id ) ) && // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					$product->is_type( 'variable' )
				) {
					foreach ( $product->get_children() as $child_id ) {
						$this->take_product_stock_snapshot( $child_id, false, $res );
						++$res;
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
