<?php
/**
 * Stock Snapshot for WooCommerce - Action Scheduler Class
 *
 * @version 1.2.0
 * @since   1.2.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Action_Scheduler' ) ) :

class Alg_WC_Stock_Snapshot_Action_Scheduler {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 *
	 * @see     https://actionscheduler.org/
	 *
	 * @todo    [next] (dev) `wp_clear_scheduled_hook`?
	 */
	function __construct() {
		$this->action = 'alg_wc_stock_snapshot_action';
		if ( 'yes' === get_option( 'alg_wc_stock_snapshot_plugin_enabled', 'yes' ) && 'yes' === get_option( 'alg_wc_stock_snapshot_action_scheduler', 'yes' ) ) {
			add_action( 'init', array( $this, 'schedule_action' ) );
			add_action( $this->action, array( $this, 'run_action' ) );
		} else {
			add_action( 'init', array( $this, 'unschedule_action' ) );
		}
		// Plugin deactivation
		register_deactivation_hook( alg_wc_stock_snapshot()->plugin_file(), array( $this, 'unschedule_action' ) );
		// Clearing WP cron (for backward compatibility)
		wp_clear_scheduled_hook( 'alg_wc_stock_snapshot' );
	}

	/**
	 * run_action.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function run_action( $args ) {
		alg_wc_stock_snapshot()->core->take_stock_snapshot( false );
	}

	/**
	 * unschedule_action.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function unschedule_action() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $this->action );
		}
	}

	/**
	 * schedule_action.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 *
	 * @todo    [later] (dev) move it somewhere else from the `init` action (`register_activation_hook` won't work because of `plugins_loaded` problem)
	 */
	function schedule_action() {
		if (
			function_exists( 'as_has_scheduled_action' ) &&
			( $interval_in_seconds = get_option( 'alg_wc_stock_snapshot_action_scheduler_interval', 24 * HOUR_IN_SECONDS ) ) &&
			false === as_has_scheduled_action( $this->action, array( $interval_in_seconds ) )
		) {
			as_unschedule_all_actions( $this->action );
			as_schedule_recurring_action( time(), $interval_in_seconds, $this->action, array( $interval_in_seconds ) );
		}
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Action_Scheduler();
