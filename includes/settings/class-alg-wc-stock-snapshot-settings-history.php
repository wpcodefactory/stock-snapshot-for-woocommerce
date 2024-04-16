<?php
/**
 * Stock Snapshot for WooCommerce - History Section Settings
 *
 * @version 1.6.0
 * @since   1.2.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings_History' ) ) :

class Alg_WC_Stock_Snapshot_Settings_History extends Alg_WC_Stock_Snapshot_Settings_Section {

	/**
	 * after.
	 *
	 * @version 1.5.0
	 * @since   1.5.0
	 */
	public $after;

	/**
	 * before.
	 *
	 * @version 1.6.0
	 * @since   1.6.0
	 */
	public $before;

	/**
	 * current_time.
	 *
	 * @version 1.5.0
	 * @since   1.5.0
	 */
	public $current_time;

	/**
	 * Constructor.
	 *
	 * @version 1.6.0
	 * @since   1.2.0
	 */
	function __construct() {

		$this->id   = 'history';
		$this->desc = __( 'History', 'stock-snapshot-for-woocommerce' );

		parent::__construct();

		$this->init();

	}

	/**
	 * init.
	 *
	 * @version 1.6.0
	 * @since   1.6.0
	 */
	function init() {

		$this->current_time = time();
		$this->after        = ( ! empty( $_GET['after'] ) ?
			strtotime( wc_clean( $_GET['after'] ) ) :
			strtotime( date( 'Y-m-d', ( $this->current_time - 7 * DAY_IN_SECONDS ) ) ) // default: "Last 7 days"
		);
		$this->before       = ( ! empty( $_GET['before'] ) ?
			strtotime( wc_clean( $_GET['before'] ) ) :
			false
		);

		add_action( 'woocommerce_admin_field_alg_wc_stock_snapshot', array( $this, 'alg_wc_stock_snapshot_admin_field' ) );

		add_action( 'admin_footer', array( $this, 'custom_dates_admin_js' ) );

		add_action( 'admin_init', array( $this, 'export_history_csv' ) );

	}

	/**
	 * export_history_csv.
	 *
	 * @version 1.6.0
	 * @since   1.6.0
	 */
	function export_history_csv() {

		// Check URL param
		if ( ! isset( $_GET['alg_wc_stock_snapshot_export_history_csv'] ) ) {
			return;
		}

		// Check user
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Invalid user role.', 'stock-snapshot-for-woocommerce' ) );
		}

		// Check nonce
		if (
			! isset( $_GET['_alg_wc_stock_snapshot_export_history_csv_nonce'] ) ||
			! wp_verify_nonce(
				$_GET['_alg_wc_stock_snapshot_export_history_csv_nonce'],
				'alg_wc_stock_snapshot_export_history_csv_action'
			)
		) {
			wp_die( esc_html__( 'Invalid nonce.', 'stock-snapshot-for-woocommerce' ) );
		}

		// Get data
		$csv = $this->get_data( 'csv' );

		// CSV headers
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . "stock-snapshot-history.csv" );
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
	 * @version 1.6.0
	 * @since   1.6.0
	 *
	 * @see     `custom_dates_admin_js()`
	 * @see     https://github.com/woocommerce/woocommerce/blob/8.7.0/plugins/woocommerce/includes/admin/class-wc-admin-settings.php#L207
	 *
	 * @todo    (dev) `wp_kses_post`?
	 */
	function alg_wc_stock_snapshot_admin_field( $value ) {
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
	 * @version 1.6.0
	 * @since   1.6.0
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
		if ( 'history' !== $current_section ) {
			return;
		}

		?><script>
			jQuery( '#alg_wc_stock_snapshot_history_date_submit' ).on( 'click', function () {
				window.onbeforeunload = null;
				var url = new URL( window.location.href );
				url.searchParams.set( 'after',  jQuery( '#alg_wc_stock_snapshot_history_date_after'  ).val() );
				url.searchParams.set( 'before', jQuery( '#alg_wc_stock_snapshot_history_date_before' ).val() );
				window.location.replace( url.href );
			} );
		</script><?php

	}

	/**
	 * get_menu.
	 *
	 * @version 1.6.0
	 * @since   1.2.0
	 */
	function get_menu() {

		// Last X days
		$menu  = array();
		$after = date( 'Y-m-d', $this->after );
		foreach ( array( 1, 7, 14, 30, 60, 90, 120, 150, 180, 360 ) as $days  ) {
			$date   = date( 'Y-m-d', ( $this->current_time - $days * DAY_IN_SECONDS ) );
			$style  = ( $after === $date && ( ! $this->before || date( 'Y-m-d', $this->before ) === date( 'Y-m-d', $this->current_time ) ) ?
				' font-weight: 600; color: #000;' : '' );
			$menu[] = '<a href="' . add_query_arg( array( 'after' => $date, 'before' => '' ) ) . '" style="text-decoration: none;' . $style . '">' .
				esc_html( sprintf( _n( 'Last %d day', 'Last %d days', $days, 'stock-snapshot-for-woocommerce' ), number_format_i18n( $days ) ) ) . '</a>';
		}
		$menu = '<p>' . implode( ' | ', $menu ) . '</p>';

		// Custom dates
		$custom_dates = '';
		$custom_dates .= sprintf( '<input id="%s" type="date" value="%s"> ',
			'alg_wc_stock_snapshot_history_date_after',
			( $this->after  ? date( 'Y-m-d', $this->after )  : '' )
		);
		$custom_dates .= sprintf( '<input id="%s" type="date" value="%s"> ',
			'alg_wc_stock_snapshot_history_date_before',
			( $this->before ? date( 'Y-m-d', $this->before ) : '' )
		);
		$custom_dates .= sprintf( '<button type="button" class="button wc-reload" id="%s">%s</button>',
			'alg_wc_stock_snapshot_history_date_submit',
			'<span class="dashicons dashicons-arrow-right-alt2"></span>'
		);
		$custom_dates = '<p>' . $custom_dates . '</p>';

		// Result
		return $menu . $custom_dates;

	}

	/**
	 * filter_not_null.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function filter_not_null( $value ) {
		return ( null !== $value );
	}

	/**
	 * filter_dates.
	 *
	 * @version 1.6.0
	 * @since   1.2.0
	 */
	function filter_dates( $time ) {
		return ( $time >= $this->after && ( ! $this->before || $time < $this->before ) );
	}

	/**
	 * get_data.
	 *
	 * @version 1.6.0
	 * @since   1.2.0
	 */
	function get_data( $output_type = 'html' ) {

		// Options
		$date_points  = get_option( 'alg_wc_stock_snapshot_history_date_points', 'Y-m-d' );
		$product_cats = get_option( 'alg_wc_stock_snapshot_history_product_cats', array() );

		// Try transient
		if ( 'html' === $output_type ) {
			$transient_id = http_build_query( array(
				'date_points'  => $date_points,
				'product_cats' => $product_cats,
				'after'        => $this->after,
				'before'       => $this->before,
			) );
			if ( false !== ( $transient = get_transient( 'alg_wc_stock_snapshot_history' ) ) ) {
				if ( isset( $transient[ $transient_id ] ) ) {
					return $transient[ $transient_id ];
				}
			} else {
				$transient = array();
			}
		}

		// Get data (from products meta)
		$stock_snapshots    = array();
		$time               = array();
		$product_query_args = array(
			'limit'               => -1,
			'return'              => 'ids',
			'orderby'             => 'name',
			'order'               => 'ASC',
			'product_category_id' => $product_cats,
		);
		foreach ( wc_get_products( $product_query_args ) as $product_id ) {
			$product_ids = array( $product_id );
			if (
				alg_wc_stock_snapshot()->core->do_variations() &&
				( $product = wc_get_product( $product_id ) ) &&
				$product->is_type( 'variable' )
			) {
				$product_ids = array_merge( $product_ids, $product->get_children() );
			}
			foreach ( $product_ids as $_product_id ) {
				$stock_snapshot = get_post_meta( $_product_id, '_alg_wc_stock_snapshot', true );
				if ( ! empty( $stock_snapshot ) ) {
					$stock_snapshot = array_filter( $stock_snapshot, array( $this, 'filter_not_null' ) );
					if ( ! empty( $stock_snapshot ) ) {
						$stock_snapshots[ $_product_id ] = $stock_snapshot;
						$time = array_merge( $time, array_keys( $stock_snapshot ) );
					}
				}
			}
		}
		$time = array_unique( $time );
		$time = array_filter( $time, array( $this, 'filter_dates' ) );
		sort( $time );

		// Table rows
		$rows = array();
		foreach ( $stock_snapshots as $product_id => $stock_snapshot ) {
			$row        = array();
			$do_add_row = false;
			foreach ( $time as $_time ) {
				$date = date( $date_points, $_time );
				if ( isset( $stock_snapshot[ $_time ] ) ) {
					$row[ $date ] = $stock_snapshot[ $_time ];
					$do_add_row   = true;
				} elseif ( ! isset( $row[ $date ] ) ) {
					$row[ $date ] = '-';
				}
			}
			if ( $do_add_row ) {
				$parent_id = wp_get_post_parent_id( $product_id );
				$prefix    = ( 0 != $parent_id ? '> ' : '' );
				$title     = get_the_title( $product_id ) . ' (#' . $product_id . ')';
				if ( 'html' === $output_type ) {
					$url = admin_url( 'post.php?post=' . ( 0 != $parent_id ? $parent_id : $product_id ) . '&action=edit' );
					$rows[] = '<tr><td>' . implode( '</td><td>', array_merge(
						array( 'product_id' => $prefix . '<a href="' . $url . '" target="_blank">' . $title . '</a>' ),
						array_reverse( $row )
					) ) . '</tr></td>';
				} else { // 'csv'
					$rows[] = implode( ',', array_merge(
						array( 'product_id' => $prefix . $title ),
						array_reverse( $row )
					) );
				}
			}
		}

		// Table head
		$head = array();
		foreach ( array_reverse( $time ) as $_time ) {
			$date = date( $date_points, $_time );
			$head[ $date ] = $date;
		}

		// Table result
		if ( 'html' === $output_type ) {
			$result = ( ! empty( $rows ) ?
				'<table class="widefat striped">' .
					'<thead><tr><th>' .
						implode( '</th><th>', array_merge(
							array( 'product_id' => __( 'Product', 'stock-snapshot-for-woocommerce' ) ),
							$head
						) ) .
					'</tr></th></thead>' .
					'<tbody>' . implode( '', $rows ) . '</tbody>' .
				'</table>' :
				'<p><em><strong>' . __( 'No snapshots found.', 'stock-snapshot-for-woocommerce' ) . '</strong></em></p>'
			);
		} else { // 'csv'
			$result = ( ! empty( $rows ) ?
				implode( PHP_EOL, array_merge(
					array(
						implode( ',', array_merge(
							array( '' ),
							$head
						) )
					),
					$rows
				) ) :
				''
			);
		}

		// Export link
		if ( 'html' === $output_type ) {
			if ( ! empty( $rows ) ) {
				$result .= sprintf( '<p style="%s">[<a href="%s">%s</a>]</p>',
					'float:right;',
					wp_nonce_url(
						add_query_arg( 'alg_wc_stock_snapshot_export_history_csv', true ),
						'alg_wc_stock_snapshot_export_history_csv_action',
						'_alg_wc_stock_snapshot_export_history_csv_nonce'
					),
					esc_html__( 'export', 'stock-snapshot-for-woocommerce' )
				);
			}
		}

		// Footer
		if ( 'html' === $output_type ) {
			$footer = '';
			$footer .= sprintf( esc_html__( 'From %s', 'stock-snapshot-for-woocommerce' ), date( 'Y-m-d H:i:s', $this->after ) );
			if ( $this->before ) {
				$footer .= ' ' . sprintf( esc_html__( 'to %s', 'stock-snapshot-for-woocommerce' ), date( 'Y-m-d H:i:s', ( $this->before - 1 ) ) );
			}
			$result .= "<p><em><small>{$footer}</small></em></p>";
		}

		// Set transient
		if ( 'html' === $output_type ) {
			$transient[ $transient_id ] = $result;
			set_transient( 'alg_wc_stock_snapshot_history', $transient, 0 );
		}

		return $result;
	}

	/**
	 * get_product_cat_options.
	 *
	 * @version 1.6.0
	 * @since   1.6.0
	 */
	function get_product_cat_options() {
		$product_cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		return ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ?
			wp_list_pluck( $product_cats, 'name', 'term_id' ) :
			array()
		);
	}

	/**
	 * get_settings.
	 *
	 * @version 1.6.0
	 * @since   1.2.0
	 *
	 * @todo    (dev) No snapshots found: hide "Scale" option then?
	 */
	function get_settings() {
		return array(

			array(
				'title'    => __( 'Snapshots', 'stock-snapshot-for-woocommerce' ),
				'desc'     => $this->get_menu() . $this->get_data(),
				'type'     => 'alg_wc_stock_snapshot',
				'id'       => 'alg_wc_stock_snapshot_history',
			),

			array(
				'title'    => __( 'Scale', 'stock-snapshot-for-woocommerce' ),
				'type'     => 'select',
				'class'    => 'chosen_select',
				'id'       => 'alg_wc_stock_snapshot_history_date_points',
				'default'  => 'Y-m-d',
				'options'  => array(
					'Y'           => __( 'Years', 'stock-snapshot-for-woocommerce' ),
					'Y-m'         => __( 'Months', 'stock-snapshot-for-woocommerce' ),
					'Y-m-d'       => __( 'Days', 'stock-snapshot-for-woocommerce' ),
					'Y-m-d H'     => __( 'Hours', 'stock-snapshot-for-woocommerce' ),
					'Y-m-d H:i'   => __( 'Minutes', 'stock-snapshot-for-woocommerce' ),
					'Y-m-d H:i:s' => __( 'Seconds', 'stock-snapshot-for-woocommerce' ),
				),
			),

			array(
				'title'    => __( 'Product categories', 'stock-snapshot-for-woocommerce' ),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'id'       => 'alg_wc_stock_snapshot_history_product_cats',
				'default'  => array(),
				'options'  => $this->get_product_cat_options(),
			),

			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_stock_snapshot_history',
			),

		);
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Settings_History();
