<?php
/**
 * Stock Snapshot for WooCommerce - General Section Settings
 *
 * @version 2.1.0
 * @since   1.0.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings_General' ) ) :

class Alg_WC_Stock_Snapshot_Settings_General extends Alg_WC_Stock_Snapshot_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id   = '';
		$this->desc = __( 'General', 'stock-snapshot-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_action_scheduler_info.
	 *
	 * @version 2.1.0
	 * @since   1.2.0
	 */
	function get_action_scheduler_info() {

		if ( 'no' === get_option( 'alg_wc_stock_snapshot_action_scheduler', 'yes' ) ) {
			return '';
		}

		$info   = '';
		$action = alg_wc_stock_snapshot()->core->action_scheduler->action;

		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {

			$info .= '<strong>' .
				sprintf(
					/* Translators: %1$s: Name of the constant, %2$s: "false". */
					__( 'Crons are disabled on your site! You need to enable them by setting %1$s constant to %2$s.', 'stock-snapshot-for-woocommerce' ),
					'<code>' . 'DISABLE_WP_CRON' . '</code>',
					'<code>' . 'false' . '</code>'
				) .
			'</strong>';

		} elseif ( ( $next_scheduled = as_next_scheduled_action( $action ) ) ) {

			$info .= (
				sprintf(
					/* Translators: %1$s: Formatted date, %2$s: Formatted date. */
					__( 'Next stock snapshot is scheduled at %1$s (current time is %2$s).', 'stock-snapshot-for-woocommerce' ),
					'<code>' . alg_wc_stock_snapshot()->core->local_date( 'Y-m-d H:i:s', $next_scheduled ) . '</code>',
					'<code>' . alg_wc_stock_snapshot()->core->local_date( 'Y-m-d H:i:s' ) . '</code>'
				) .
				'<br>* ' .
				sprintf(
					/* Translators: %s: "Action Scheduler". */
					__( 'Plugin uses %s to take the stock snapshots periodically.', 'stock-snapshot-for-woocommerce' ),
					'<a href="https://actionscheduler.org/" target="_blank">' .
						__( 'Action Scheduler', 'stock-snapshot-for-woocommerce' ) .
					'</a>'
				) .
				' ' .
				sprintf(
					/* Translators: %1$s: URL, %2$s: Hook name. */
					__( 'Action Scheduler has a built in <a href="%1$s" target="_blank">administration screen</a> for monitoring, debugging and manually triggering scheduled actions. Search for the %2$s hook there.', 'stock-snapshot-for-woocommerce' ),
					admin_url( 'admin.php?page=wc-status&tab=action-scheduler' ),
					'<code>' . $action . '</code>'
				)
			);

		}

		return $info;

	}

	/**
	 * get_settings.
	 *
	 * @version 2.1.0
	 * @since   1.0.0
	 *
	 * @todo    (dev) `alg_wc_stock_snapshot_action_scheduler`: default to `no`?
	 */
	function get_settings() {

		$plugin_settings = array(

			// Stock Snapshot Options
			array(
				'title'    => __( 'Stock Snapshot Options', 'stock-snapshot-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_stock_snapshot_plugin_options',
			),
			array(
				'title'    => __( 'Product update snapshots', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => __( 'Take snapshot on each product update.', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Enable', 'stock-snapshot-for-woocommerce' ),
				'id'       => 'alg_wc_stock_snapshot_product_update',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Extra data', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => __( 'Collect extra data in stock snapshots, e.g., user ID.', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Enable', 'stock-snapshot-for-woocommerce' ),
				'id'       => 'alg_wc_stock_snapshot_extra_data',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Allow snapshots via URL', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Enable', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => sprintf(
					/* Translators: %s: URL. */
					__( 'If enabled, you can take the snapshot with %s', 'stock-snapshot-for-woocommerce' ),
					'<code>' . add_query_arg( 'alg_wc_stock_snapshot', true, site_url() ) . '</code>'
				),
				'id'       => 'alg_wc_stock_snapshot_url',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_stock_snapshot_plugin_options',
			),

			// Variable Product Options
			array(
				'title'    => __( 'Variable Product Options', 'stock-snapshot-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_stock_snapshot_variable_product_options',
			),
			array(
				'title'    => __( 'Include variations', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => __( 'Include variations as separate products in snapshots.', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Enable', 'stock-snapshot-for-woocommerce' ),
				'id'       => 'alg_wc_stock_snapshot_include_variations',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Append variations', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => __( 'Append variations stock to the main variable product\'s stock in snapshots.', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Enable', 'stock-snapshot-for-woocommerce' ),
				'id'       => 'alg_wc_stock_snapshot_count_children',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_stock_snapshot_variable_product_options',
			),

			// Periodic Snapshots Options
			array(
				'title'             => __( 'Periodic Snapshots Options', 'stock-snapshot-for-woocommerce' ),
				'desc'              => $this->get_action_scheduler_info(),
				'type'              => 'title',
				'id'                => 'alg_wc_stock_snapshot_action_scheduler_options',
			),
			array(
				'title'             => __( 'Periodic snapshots', 'stock-snapshot-for-woocommerce' ),
				'desc'              => __( 'Enable', 'stock-snapshot-for-woocommerce' ),
				'id'                => 'alg_wc_stock_snapshot_action_scheduler',
				'default'           => 'yes',
				'type'              => 'checkbox',
			),
			array(
				'desc'              => __( 'Interval (in seconds)', 'stock-snapshot-for-woocommerce' ),
				'id'                => 'alg_wc_stock_snapshot_action_scheduler_interval',
				'default'           => 24 * HOUR_IN_SECONDS,
				'type'              => 'number',
				'custom_attributes' => array( 'min' => 60 ),
			),
			array(
				'type'              => 'sectionend',
				'id'                => 'alg_wc_stock_snapshot_action_scheduler_options',
			),

		);

		// Shortcodes
		$shortcodes = array(
			array(
				'title'    => __( 'Shortcodes', 'stock-snapshot-for-woocommerce' ),
				'desc'     => sprintf(
					/* Translators: %s: Shortcode name. */
					__( '%s shortcode allows you to display recently restocked products.', 'stock-snapshot-for-woocommerce' ),
					'<code>[alg_wc_stock_snapshot_restocked]</code>' ),
				'type'     => 'title',
				'id'       => 'alg_wc_stock_snapshot_shortcodes',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_stock_snapshot_shortcodes',
			),
		);

		return array_merge( $plugin_settings, $shortcodes );
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Settings_General();
