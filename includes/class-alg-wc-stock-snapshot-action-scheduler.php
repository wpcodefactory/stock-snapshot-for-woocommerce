<?php
/**
 * Stock Snapshot for WooCommerce - Action Scheduler Class
 *
 * @version 2.3.0
 * @since   1.2.0
 *
 * @author WPFactory
 *
 * @package WPFactory\WC_Stock_Snapshot
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Action_Scheduler' ) ) :

	/**
	 * Alg_WC_Stock_Snapshot_Action_Scheduler class.
	 *
	 * @version 2.3.0
	 * @since   1.2.0
	 */
	class Alg_WC_Stock_Snapshot_Action_Scheduler {

		/**
		 * Action.
		 *
		 * @version 1.5.0
		 * @since   1.5.0
		 *
		 * @var string
		 */
		public $action;

		/**
		 * Constructor.
		 *
		 * @version 2.1.0
		 * @since   1.2.0
		 *
		 * @see https://actionscheduler.org/
		 *
		 * @todo (v2.3.0) `register_deactivation_hook`?
		 * @todo (dev) `wp_clear_scheduled_hook`?
		 */
		public function __construct() {
			// Action name.
			$this->action = 'alg_wc_stock_snapshot_action';

			if ( 'yes' === get_option( 'alg_wc_stock_snapshot_action_scheduler', 'yes' ) ) {
				// Schedule & Run.
				add_action( 'init', array( $this, 'schedule_action' ) );
				add_action( $this->action, array( $this, 'run_action' ) );
			} else {
				// Unschedule.
				add_action( 'init', array( $this, 'unschedule_action' ) );
			}

			// Plugin deactivation.
			register_deactivation_hook( ALG_WC_STOCK_SNAPSHOT_FILE, array( $this, 'unschedule_action' ) );

			// Clearing WP cron (for backward compatibility).
			wp_clear_scheduled_hook( 'alg_wc_stock_snapshot' );
		}

		/**
		 * Run action.
		 *
		 * @version 2.3.0
		 * @since   1.2.0
		 */
		public function run_action() {
			alg_wc_stock_snapshot()->core->take_stock_snapshot( false );
		}

		/**
		 * Unschedule action.
		 *
		 * @version 1.2.0
		 * @since   1.2.0
		 */
		public function unschedule_action() {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( $this->action );
			}
		}

		/**
		 * Schedule action.
		 *
		 * @version 1.2.0
		 * @since   1.2.0
		 *
		 * @todo (dev) Move it somewhere else from the `init` action (`register_activation_hook` won't work because of `plugins_loaded` problem).
		 */
		public function schedule_action() {
			if (
				function_exists( 'as_has_scheduled_action' ) &&
				( $interval_in_seconds = get_option( 'alg_wc_stock_snapshot_action_scheduler_interval', 24 * HOUR_IN_SECONDS ) ) && // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
				false === as_has_scheduled_action( $this->action, array( $interval_in_seconds ) )
			) {
				as_unschedule_all_actions( $this->action );
				as_schedule_recurring_action( time(), $interval_in_seconds, $this->action, array( $interval_in_seconds ) );
			}
		}
	}

endif;

return new Alg_WC_Stock_Snapshot_Action_Scheduler();
