<?php
/**
 * Stock Snapshot for WooCommerce - Report Section Settings
 *
 * @version 2.1.0
 * @since   2.1.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings_Report_Section' ) ) :

class Alg_WC_Stock_Snapshot_Settings_Report_Section extends Alg_WC_Stock_Snapshot_Settings_Section {

	/**
	 * default_days.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public $default_days = 7;

	/**
	 * do_use_datetime.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public $do_use_datetime = false;

	/**
	 * do_add_user_selector.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public $do_add_user_selector = false;

	/**
	 * do_add_product_cat_selector.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public $do_add_product_cat_selector = false;

	/**
	 * user_id.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public $user_id;

	/**
	 * product_cat.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public $product_cat;

	/**
	 * after.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public $after;

	/**
	 * before.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public $before;

	/**
	 * current_local_time.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public $current_local_time;

	/**
	 * Constructor.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function __construct() {
		parent::__construct();
		$this->init();
	}

	/**
	 * init.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 *
	 * @todo    (v2.1.0) nonces
	 */
	function init() {

		// User ID
		$this->user_id = (
			! empty( $_GET['user_id'] ) && 'null' !== $_GET['user_id'] ?
			(int) $_GET['user_id'] :
			false
		);

		// Product category
		$this->product_cat = (
			! empty( $_GET['product_cat'] ) ?
			(int) $_GET['product_cat'] :
			false
		);

		// Current local time
		$this->current_local_time = alg_wc_stock_snapshot()->core->local_time();

		// After & Before
		$this->after = (
			! empty( $_GET['after'] ) ?
			strtotime( sanitize_text_field( wp_unslash( $_GET['after'] ) ) ) :
			strtotime(
				date( // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'Y-m-d',
					( $this->current_local_time - $this->default_days * DAY_IN_SECONDS ) // default: "Last X days"
				)
			)
		);
		$this->before = (
			! empty( $_GET['before'] ) ?
			strtotime( sanitize_text_field( wp_unslash( $_GET['before'] ) ) ) :
			false
		);

		// After & Before: To GMT
		$this->after = alg_wc_stock_snapshot()->core->gmt_time( $this->after );
		if ( false !== $this->before ) {
			$this->before = alg_wc_stock_snapshot()->core->gmt_time( $this->before );
		}

		// Custom admin field
		add_action( 'woocommerce_admin_field_alg_wc_stock_snapshot', array( $this, 'alg_wc_stock_snapshot_admin_field' ) );

		// JS
		add_action( 'admin_footer', array( $this, 'custom_dates_admin_js' ) );

		// Export
		add_action( 'admin_init', array( $this, 'export_csv' ) );

	}

	/**
	 * export_csv.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function export_csv() {

		// Check URL param
		if ( ! isset( $_GET[ "alg_wc_stock_snapshot_export_report_{$this->id}_csv" ] ) ) {
			return;
		}

		// Check user
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Invalid user role.', 'stock-snapshot-for-woocommerce' ) );
		}

		// Check nonce
		if (
			! isset( $_GET['_alg_wc_stock_snapshot_export_report_csv_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_GET['_alg_wc_stock_snapshot_export_report_csv_nonce'] ) ),
				'alg_wc_stock_snapshot_export_report_csv_action'
			)
		) {
			wp_die( esc_html__( 'Invalid nonce.', 'stock-snapshot-for-woocommerce' ) );
		}

		// Get data
		$csv = $this->get_data( 'csv' );

		// CSV headers
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . "stock-snapshot-{$this->id}.csv" );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $csv ) );

		// CSV content
		echo $csv;

		// Exit
		die();

	}

	/**
	 * alg_wc_stock_snapshot_admin_field.
	 *
	 * @version 2.1.0
	 * @since   1.6.0
	 *
	 * @see     `custom_dates_admin_js()`
	 * @see     https://github.com/woocommerce/woocommerce/blob/8.7.0/plugins/woocommerce/includes/admin/class-wc-admin-settings.php#L207
	 *
	 * @todo    (dev) `wp_kses_post`?
	 */
	function alg_wc_stock_snapshot_admin_field( $value ) {

		global $current_tab;
		if ( 'alg_wc_stock_snapshot' !== $current_tab ) {
			return;
		}

		global $current_section;
		if ( $this->id !== $current_section ) {
			return;
		}

		if ( ! empty( $value['title'] ) ) {
			echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
		}
		if ( ! empty( $value['desc'] ) ) {
			echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
			echo wpautop( wptexturize( $value['desc'] ) );
			echo '</div>';
		}
		echo '<table class="form-table">' . "\n\n";
		if ( ! empty( $value['id'] ) ) {
			do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) );
		}

	}

	/**
	 * custom_dates_admin_js.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 *
	 * @see     https://stackoverflow.com/questions/40425682/disable-changes-you-made-may-not-be-saved-pop-up-window
	 * @see     https://stackoverflow.com/questions/1034621/get-the-current-url-with-javascript
	 * @see     https://stackoverflow.com/questions/486896/adding-a-parameter-to-the-url-with-javascript
	 * @see     https://stackoverflow.com/questions/503093/how-do-i-redirect-to-another-webpage
	 */
	function custom_dates_admin_js() {

		global $current_tab;
		if ( 'alg_wc_stock_snapshot' !== $current_tab ) {
			return;
		}

		global $current_section;
		if ( $this->id !== $current_section ) {
			return;
		}

		?><script>
			jQuery( '#alg_wc_stock_snapshot_report_submit' ).on( 'click', function () {
				window.onbeforeunload = null;
				var url = new URL( window.location.href );
				if ( undefined !== jQuery( '#alg_wc_stock_snapshot_report_user_id' ).val() ) {
					url.searchParams.set( 'user_id', jQuery( '#alg_wc_stock_snapshot_report_user_id' ).val() );
				}
				if ( undefined !== jQuery( '#alg_wc_stock_snapshot_report_product_cat' ).val() ) {
					url.searchParams.set( 'product_cat', jQuery( '#alg_wc_stock_snapshot_report_product_cat' ).val() );
				}
				url.searchParams.set( 'after',  jQuery( '#alg_wc_stock_snapshot_report_date_after'  ).val() );
				url.searchParams.set( 'before', jQuery( '#alg_wc_stock_snapshot_report_date_before' ).val() );
				window.location.replace( url.href );
			} );
		</script><?php

	}

	/**
	 * get_menu.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_menu() {

		// Last X days
		$menu  = array();
		$after = alg_wc_stock_snapshot()->core->local_date( 'Y-m-d', $this->after );
		foreach ( array( 1, 7, 14, 30, 60, 90, 120, 150, 180, 360 ) as $days ) {
			$date   = date( 'Y-m-d', ( $this->current_local_time - $days * DAY_IN_SECONDS ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$style  = (
				(
					$after === $date &&
					(
						! $this->before ||
						alg_wc_stock_snapshot()->core->local_date( 'Y-m-d', $this->before ) === date( 'Y-m-d', $this->current_local_time ) // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					)
				) ?
				' font-weight: 600; color: #000;' :
				''
			);
			$url = add_query_arg( array( 'after' => $date, 'before' => '' ) );
			$menu[] = '<a href="' . $url . '" style="text-decoration: none;' . $style . '">' .
				esc_html(
					(
						1 === $days ?
						__( 'Last day', 'stock-snapshot-for-woocommerce' ) :
						sprintf(
							/* Translators: %d: Number of days. */
							_n( 'Last %d day', 'Last %d days', $days, 'stock-snapshot-for-woocommerce' ),
							number_format_i18n( $days )
						)
					)
				) .
			'</a>';
		}
		$menu = '<p>' . implode( ' | ', $menu ) . '</p>';

		// Secondary menu: Start
		$secondary_menu = '';

		// User selector
		if ( $this->do_add_user_selector ) {

			ob_start();
			?>
			<style>
				#alg_wc_stock_snapshot_<?php echo esc_attr( $this->id ); ?>-description .select2-container {
					min-width: 300px;
					margin-right: 5px;
					top: -4px;
				}
			</style>
			<?php
			$secondary_menu .= ob_get_clean();

			$secondary_menu .= sprintf(
				'<select id="%s" class="%s" data-placeholder="%s" data-allow_clear="true">',
				'alg_wc_stock_snapshot_report_user_id',
				'wc-customer-search',
				esc_attr__( 'Search for a user&hellip;', 'stock-snapshot-for-woocommerce' )
			);

			if ( $this->user_id ) {

				$customer_label = (
					( $customer = new WC_Customer( $this->user_id ) ) && $customer->get_id() ?
					sprintf(
						/* Translators: %1$s: Customer name, %2$s Customer ID, %3$s: Customer email. */
						esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
						$customer->get_first_name() . ' ' . $customer->get_last_name(),
						$customer->get_id(),
						$customer->get_email()
					) :
					sprintf(
						/* Translators: %d: Customer ID. */
						esc_html__( 'Customer #%d', 'stock-snapshot-for-woocommerce' ),
						$this->user_id
					)
				);

				$secondary_menu .= '<option value="' . esc_attr( $this->user_id ) . '" selected>' .
					esc_html( $customer_label ) .
				'</option>';

			}

			$secondary_menu .= '</select>';

		}

		// Product category selector
		if ( $this->do_add_product_cat_selector ) {

			$secondary_menu .= sprintf(
				'<select id="%s" class="%s" data-placeholder="%s" data-allow_clear="true">',
				'alg_wc_stock_snapshot_report_product_cat',
				'wc-enhanced-select',
				esc_attr__( 'Search for a category&hellip;', 'stock-snapshot-for-woocommerce' )
			);

			$secondary_menu .= '<option value=""></option>';

			foreach ( $this->get_product_cat_options() as $cat_id => $cat_title ) {
				$secondary_menu .= sprintf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $cat_id ),
					selected( $cat_id, $this->product_cat, false ),
					esc_attr( $cat_title )
				);
			}

			$secondary_menu .= '</select>';

		}

		// Custom dates
		$date_type   = ( $this->do_use_datetime ? 'datetime-local' : 'date' );
		$date_format = ( $this->do_use_datetime ? 'Y-m-d H:i'      : 'Y-m-d' );
		$secondary_menu .= sprintf(
			'<input id="%s" type="%s" value="%s"> ',
			'alg_wc_stock_snapshot_report_date_after',
			$date_type,
			( $this->after  ? alg_wc_stock_snapshot()->core->local_date( $date_format, $this->after )  : '' )
		);
		$secondary_menu .= sprintf(
			'<input id="%s" type="%s" value="%s"> ',
			'alg_wc_stock_snapshot_report_date_before',
			$date_type,
			( $this->before ? alg_wc_stock_snapshot()->core->local_date( $date_format, $this->before ) : '' )
		);

		// Submit button
		$secondary_menu .= sprintf(
			'<button type="button" class="button wc-reload" id="%s">%s</button>',
			'alg_wc_stock_snapshot_report_submit',
			'<span class="dashicons dashicons-arrow-right-alt2"></span>'
		);

		// Secondary menu: End
		$secondary_menu = '<p>' . $secondary_menu . '</p>';

		// Result
		return $menu . $secondary_menu;

	}

	/**
	 * filter_not_null.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function filter_not_null( $value ) {
		return ( null !== $value );
	}

	/**
	 * get_admin.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_admin() {
		return alg_wc_stock_snapshot()->core->admin;
	}

	/**
	 * get_product_cat_options.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_product_cat_options() {
		$product_cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		return ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ?
			wp_list_pluck( $product_cats, 'name', 'term_id' ) :
			array()
		);
	}

	/**
	 * get_export_csv_link.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_export_csv_link() {
		return sprintf(
			'<p style="%s">[<a href="%s">%s</a>]</p>',
			'float:right;',
			wp_nonce_url(
				add_query_arg( "alg_wc_stock_snapshot_export_report_{$this->id}_csv", true ),
				'alg_wc_stock_snapshot_export_report_csv_action',
				'_alg_wc_stock_snapshot_export_report_csv_nonce'
			),
			esc_html__( 'export', 'stock-snapshot-for-woocommerce' )
		);
	}

	/**
	 * get_footer.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_footer() {
		$footer = '';

		// From ... to ...
		$footer .= sprintf(
			/* Translators: %s: Formatted date and time. */
			esc_html__( 'From %s', 'stock-snapshot-for-woocommerce' ),
			alg_wc_stock_snapshot()->core->local_date( 'Y-m-d H:i:s', $this->after )
		);
		if ( $this->before ) {
			$footer .= ' ' . sprintf(
				/* Translators: %s: Formatted date and time. */
				esc_html__( 'to %s', 'stock-snapshot-for-woocommerce' ),
				alg_wc_stock_snapshot()->core->local_date( 'Y-m-d H:i:s', ( $this->before - 1 ) )
			);
		}
		$footer .= ' (' . wp_timezone_string() . ')';

		// Local time
		$footer .= '<br>' . sprintf(
			/* Translators: %1$s: Formatted date and time, %2$s: Timezone name. */
			esc_html__( 'Local time is %1$s (%2$s)', 'stock-snapshot-for-woocommerce' ),
			date( 'Y-m-d H:i:s', $this->current_local_time ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			wp_timezone_string()
		);

		return "<p><em><small>{$footer}</small></em></p>";
	}

	/**
	 * get_no_results_html.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_no_results_html() {
		ob_start();
		?><p><em><strong><?php esc_html_e( 'No snapshots found.', 'stock-snapshot-for-woocommerce' ); ?></strong></em></p><?php
		return ob_get_clean();
	}

	/**
	 * get_settings.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_settings() {
		return array(
			array(
				'title'    => __( 'Snapshots', 'stock-snapshot-for-woocommerce' ),
				'desc'     => $this->get_menu() . $this->get_data(),
				'type'     => 'alg_wc_stock_snapshot',
				'id'       => 'alg_wc_stock_snapshot_report',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_stock_snapshot_report',
			),
		);
	}

	/**
	 * get_data.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_data( $output_type = 'html' ) {
		return '';
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Settings_Report_Section();
