<?php
/**
 * Stock Snapshot for WooCommerce - Section Settings
 *
 * @version 1.5.0
 * @since   1.0.0
 *
 * @author WPFactory
 *
 * @package WPFactory\WC_Stock_Snapshot\Settings
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings_Section' ) ) :

	/**
	 * Alg_WC_Stock_Snapshot_Settings_Section class.
	 *
	 * @version 1.5.0
	 * @since   1.0.0
	 */
	class Alg_WC_Stock_Snapshot_Settings_Section {

		/**
		 * ID.
		 *
		 * @version 1.5.0
		 * @since   1.5.0
		 *
		 * @var string
		 */
		public $id;

		/**
		 * Description.
		 *
		 * @version 1.5.0
		 * @since   1.5.0
		 *
		 * @var string
		 */
		public $desc;

		/**
		 * Constructor.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function __construct() {
			add_filter(
				'woocommerce_get_sections_alg_wc_stock_snapshot',
				array( $this, 'settings_section' )
			);
			add_filter(
				'woocommerce_get_settings_alg_wc_stock_snapshot_' . $this->id,
				array( $this, 'get_settings' ),
				PHP_INT_MAX
			);
		}

		/**
		 * Settings section.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $sections Sections.
		 *
		 * @return array Modified sections.
		 */
		public function settings_section( $sections ) {
			$sections[ $this->id ] = $this->desc;
			return $sections;
		}
	}

endif;
