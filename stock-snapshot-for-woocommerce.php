<?php
/*
Plugin Name: Stock History & Reports Manager for WooCommerce
Plugin URI: https://wpfactory.com/item/stock-snapshot-for-woocommerce/
Description: Keep track of your products stock in WooCommerce.
Version: 2.2.1
Author: WPFactory
Author URI: https://wpfactory.com
Text Domain: stock-snapshot-for-woocommerce
Domain Path: /langs
WC tested up to: 10.1
Requires Plugins: woocommerce
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

defined( 'ABSPATH' ) || exit;

if ( 'stock-snapshot-for-woocommerce.php' === basename( __FILE__ ) ) {
	/**
	 * Check if Pro plugin version is activated.
	 *
	 * @version 1.4.0
	 * @since   1.1.0
	 */
	$plugin = 'stock-snapshot-for-woocommerce-pro/stock-snapshot-for-woocommerce-pro.php';
	if (
		in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) ||
		(
			is_multisite() &&
			array_key_exists( $plugin, (array) get_site_option( 'active_sitewide_plugins', array() ) )
		)
	) {
		defined( 'ALG_WC_STOCK_SNAPSHOT_FILE_FREE' ) || define( 'ALG_WC_STOCK_SNAPSHOT_FILE_FREE', __FILE__ );
		return;
	}
}

defined( 'ALG_WC_STOCK_SNAPSHOT_VERSION' ) || define( 'ALG_WC_STOCK_SNAPSHOT_VERSION', '2.2.1' );

defined( 'ALG_WC_STOCK_SNAPSHOT_FILE' ) || define( 'ALG_WC_STOCK_SNAPSHOT_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-alg-wc-stock-snapshot.php';

if ( ! function_exists( 'alg_wc_stock_snapshot' ) ) {
	/**
	 * Returns the main instance of Alg_WC_Stock_Snapshot to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function alg_wc_stock_snapshot() {
		return Alg_WC_Stock_Snapshot::instance();
	}
}

add_action( 'plugins_loaded', 'alg_wc_stock_snapshot' );
