<?php
/**
 * Stock Snapshot for WooCommerce - Main Class
 *
 * @version 2.3.0
 * @since   1.0.0
 *
 * @author WPFactory
 *
 * @package WPFactory\WC_Stock_Snapshot
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot' ) ) :

	/**
	 * Alg_WC_Stock_Snapshot class.
	 *
	 * @version 2.3.0
	 * @since   1.0.0
	 */
	final class Alg_WC_Stock_Snapshot {

		/**
		 * Plugin version.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @var string
		 */
		public $version = ALG_WC_STOCK_SNAPSHOT_VERSION;

		/**
		 * Core.
		 *
		 * @version 1.5.0
		 * @since   1.5.0
		 *
		 * @var Alg_WC_Stock_Snapshot_Core
		 */
		public $core;

		/**
		 * The single instance of the class.
		 *
		 * @version 2.3.0
		 * @since   1.0.0
		 *
		 * @var Alg_WC_Stock_Snapshot
		 */
		protected static $instance = null;

		/**
		 * Main Alg_WC_Stock_Snapshot Instance.
		 *
		 * Ensures only one instance of Alg_WC_Stock_Snapshot is loaded or can be loaded.
		 *
		 * @version 2.3.0
		 * @since   1.0.0
		 *
		 * @static
		 *
		 * @return Alg_WC_Stock_Snapshot
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Alg_WC_Stock_Snapshot Constructor.
		 *
		 * @version 2.3.0
		 * @since   1.0.0
		 *
		 * @access public
		 */
		public function __construct() {
			// Check for active WooCommerce plugin.
			if ( ! function_exists( 'WC' ) ) {
				return;
			}

			// Load libs.
			if ( is_admin() ) {
				require_once plugin_dir_path( ALG_WC_STOCK_SNAPSHOT_FILE ) . 'vendor/autoload.php';
			}

			// Declare compatibility with custom order tables for WooCommerce.
			add_action( 'before_woocommerce_init', array( $this, 'wc_declare_compatibility' ) );

			// Pro.
			if ( 'stock-snapshot-for-woocommerce-pro.php' === basename( ALG_WC_STOCK_SNAPSHOT_FILE ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'pro/class-alg-wc-stock-snapshot-pro.php';
			}

			// Include required files.
			$this->includes();

			// Admin.
			if ( is_admin() ) {
				$this->admin();
			}
		}

		/**
		 * WC declare compatibility.
		 *
		 * @version 1.4.0
		 * @since   1.4.0
		 *
		 * @see https://developer.woocommerce.com/docs/features/high-performance-order-storage/recipe-book/
		 */
		public function wc_declare_compatibility() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				$files = (
					defined( 'ALG_WC_STOCK_SNAPSHOT_FILE_FREE' ) ?
					array( ALG_WC_STOCK_SNAPSHOT_FILE, ALG_WC_STOCK_SNAPSHOT_FILE_FREE ) :
					array( ALG_WC_STOCK_SNAPSHOT_FILE )
				);
				foreach ( $files as $file ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
						'custom_order_tables',
						$file,
						true
					);
				}
			}
		}

		/**
		 * Includes.
		 *
		 * @version 2.0.0
		 * @since   1.0.0
		 */
		public function includes() {
			// Core.
			$this->core = require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-stock-snapshot-core.php';
		}

		/**
		 * Admin.
		 *
		 * @version 2.2.0
		 * @since   1.0.0
		 */
		public function admin() {
			// Action links.
			add_filter(
				'plugin_action_links_' . plugin_basename( ALG_WC_STOCK_SNAPSHOT_FILE ),
				array( $this, 'action_links' )
			);

			// "Recommendations" page.
			add_action( 'init', array( $this, 'add_cross_selling_library' ) );

			// WC Settings tab as WPFactory submenu item.
			add_action( 'init', array( $this, 'move_wc_settings_tab_to_wpfactory_menu' ) );

			// Settings.
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );

			// Version update.
			if ( get_option( 'alg_wc_stock_snapshot_version', '' ) !== $this->version ) {
				add_action( 'admin_init', array( $this, 'version_updated' ) );
			}
		}

		/**
		 * Action links.
		 *
		 * @version 2.0.0
		 * @since   1.0.0
		 *
		 * @param mixed $links Plugin action links.
		 *
		 * @return array
		 */
		public function action_links( $links ) {
			$custom_links = array();

			$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_stock_snapshot' ) . '">' .
				__( 'Settings', 'stock-snapshot-for-woocommerce' ) .
			'</a>';

			if ( 'stock-snapshot-for-woocommerce.php' === basename( ALG_WC_STOCK_SNAPSHOT_FILE ) ) {
				$custom_links[] = '<a target="_blank" style="font-weight: bold; color: green;" href="https://wpfactory.com/item/stock-snapshot-for-woocommerce/">' .
					__( 'Go Pro', 'stock-snapshot-for-woocommerce' ) .
				'</a>';
			}

			return array_merge( $custom_links, $links );
		}

		/**
		 * Add cross selling library.
		 *
		 * @version 2.0.0
		 * @since   2.0.0
		 */
		public function add_cross_selling_library() {
			if ( ! class_exists( '\WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling' ) ) {
				return;
			}

			$cross_selling = new \WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling();
			$cross_selling->setup( array( 'plugin_file_path' => ALG_WC_STOCK_SNAPSHOT_FILE ) );
			$cross_selling->init();
		}

		/**
		 * Move WC settings tab to WPFactory menu.
		 *
		 * @version 2.2.0
		 * @since   2.0.0
		 */
		public function move_wc_settings_tab_to_wpfactory_menu() {
			if ( ! class_exists( '\WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu' ) ) {
				return;
			}

			$wpfactory_admin_menu = \WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu::get_instance();

			if ( ! method_exists( $wpfactory_admin_menu, 'move_wc_settings_tab_to_wpfactory_menu' ) ) {
				return;
			}

			$wpfactory_admin_menu->move_wc_settings_tab_to_wpfactory_menu(
				array(
					'wc_settings_tab_id' => 'alg_wc_stock_snapshot',
					'menu_title'         => __( 'Stock Snapshot', 'stock-snapshot-for-woocommerce' ),
					'page_title'         => __( 'Stock History & Reports Manager for WooCommerce', 'stock-snapshot-for-woocommerce' ),
					'plugin_icon'        => array(
						'get_url_method'    => 'wporg_plugins_api',
						'wporg_plugin_slug' => 'stock-snapshot-for-woocommerce',
					),
				)
			);
		}

		/**
		 * Add WooCommerce settings tab.
		 *
		 * @version 2.0.0
		 * @since   1.0.0
		 *
		 * @param array $settings WooCommerce settings array.
		 *
		 * @return array Modified WooCommerce settings array.
		 */
		public function add_woocommerce_settings_tab( $settings ) {
			$settings[] = require_once plugin_dir_path( __FILE__ ) . 'settings/class-alg-wc-stock-snapshot-settings.php';
			return $settings;
		}

		/**
		 * Version updated.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @todo (dev) Delete transients?
		 */
		public function version_updated() {
			update_option( 'alg_wc_stock_snapshot_version', $this->version );
		}

		/**
		 * Plugin URL.
		 *
		 * @version 1.1.0
		 * @since   1.0.0
		 *
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugin_dir_url( ALG_WC_STOCK_SNAPSHOT_FILE ) );
		}

		/**
		 * Plugin path.
		 *
		 * @version 1.1.0
		 * @since   1.0.0
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( ALG_WC_STOCK_SNAPSHOT_FILE ) );
		}

		/**
		 * Plugin file.
		 *
		 * @version 1.1.0
		 * @since   1.0.0
		 *
		 * @return string
		 */
		public function plugin_file() {
			return ALG_WC_STOCK_SNAPSHOT_FILE;
		}
	}

endif;
