<?php
/**
 * Stock Snapshot for WooCommerce - History Section Settings
 *
 * @version 1.3.0
 * @since   1.2.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings_History' ) ) :

class Alg_WC_Stock_Snapshot_Settings_History extends Alg_WC_Stock_Snapshot_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function __construct() {
		$this->id   = 'history';
		$this->desc = __( 'History', 'stock-snapshot-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_menu.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function get_menu() {
		$menu  = array();
		$after = date( 'Y-m-d', $this->after );
		foreach ( array( 1, 7, 14, 30, 60, 90, 120, 150, 180, 360 ) as $days  ) {
			$date   = date( 'Y-m-d', ( $this->current_time - $days * DAY_IN_SECONDS ) );
			$style  = ( $after === $date ? ' font-weight: 600; color: #000;' : '' );
			$menu[] = '<a href="' . add_query_arg( 'after', $date ) . '" style="text-decoration: none;' . $style . '">' .
				esc_html( sprintf( _n( 'Last %d day', 'Last %d days', $days, 'stock-snapshot-for-woocommerce' ), number_format_i18n( $days ) ) ) . '</a>';
		}
		return '<p>' . implode( ' | ', $menu ) . '</p>';
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
	 * filter_time_after.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function filter_time_after( $value ) {
		return ( $value >= $this->after );
	}

	/**
	 * get_data.
	 *
	 * @version 1.3.0
	 * @since   1.2.0
	 */
	function get_data() {

		$date_points = get_option( 'alg_wc_stock_snapshot_history_date_points', 'Y-m-d' );

		// Try transient
		if ( false !== ( $transient = get_transient( 'alg_wc_stock_snapshot_history' ) ) ) {
			if ( isset( $transient[ $date_points ][ $this->after ] ) ) {
				return $transient[ $date_points ][ $this->after ];
			}
		} else {
			$transient = array();
		}

		// Get data
		$stock_snapshots = array();
		$time            = array();
		foreach ( wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'orderby' => 'name', 'order' => 'ASC' ) ) as $product_id ) {
			$product_ids = array( $product_id );
			if ( alg_wc_stock_snapshot()->core->do_variations() && ( $product = wc_get_product( $product_id ) ) && $product->is_type( 'variable' ) ) {
				$product_ids = array_merge( $product_ids, $product->get_children() );
			}
			foreach ( $product_ids as $_product_id ) {
				$stock_snapshot = get_post_meta( $_product_id, '_alg_wc_stock_snapshot', true );
				if ( ! empty( $stock_snapshot ) ) {
					$stock_snapshot = array_filter( $stock_snapshot, array( $this, 'filter_not_null' ) );
					if ( ! empty( $stock_snapshot ) ) {
						$stock_snapshots[ $_product_id ] = $stock_snapshot;
						$time                            = array_merge( $time, array_keys( $stock_snapshot ) );
					}
				}
			}
		}
		$time = array_unique( $time );
		$time = array_filter( $time, array( $this, 'filter_time_after' ) );
		sort( $time );

		// Create table
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
				$parent_id   = wp_get_post_parent_id( $product_id );
				$_product_id = ( 0 != $parent_id ? $parent_id : $product_id );
				$rows[] = '<tr><td>' . implode( '</td><td>', array_merge(
						array( 'product_id' => ( 0 != $parent_id ? '> ' : '' ) . '<a href="' . admin_url( 'post.php?post=' . $_product_id . '&action=edit' ) . '" target="_blank">' .
							get_the_title( $product_id ) . ' (#' . $product_id . ')' . '</a>' ),
						array_reverse( $row )
					) ) . '</tr></td>';
			}
		}
		$head = array();
		foreach ( array_reverse( $time ) as $_time ) {
			$date = date( $date_points, $_time );
			$head[ $date ] = $date;
		}
		$result = ( ! empty( $rows ) ?
			'<table class="widefat striped">' .
				'<thead><tr><th>' . implode( '</th><th>', array_merge( array( 'product_id' => __( 'Product', 'stock-snapshot-for-woocommerce' ) ), $head ) ) . '</tr></th></thead>' .
				'<tbody>' . implode( '', $rows ) . '</tbody>' .
			'</table>' :
			'<p><em><strong>' . __( 'No snapshots found.', 'stock-snapshot-for-woocommerce' ) . '</strong></em></p>'
		);

		// Set transient
		$transient[ $date_points ][ $this->after ] = $result;
		set_transient( 'alg_wc_stock_snapshot_history', $transient, 0 );

		return $result;
	}

	/**
	 * get_settings.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 *
	 * @todo    (dev) No snapshots found: hide "Scale" option then?
	 */
	function get_settings() {
		$this->current_time = time();
		$this->after        = ( isset( $_GET['after'] ) ? strtotime( wc_clean( $_GET['after'] ) ) : strtotime( date( 'Y-m-d', ( $this->current_time - 7 * DAY_IN_SECONDS ) ) ) );
		return array(
			array(
				'title'    => __( 'Snapshots', 'stock-snapshot-for-woocommerce' ),
				'desc'     => $this->get_menu() . $this->get_data(),
				'type'     => 'title',
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
				'type'     => 'sectionend',
				'id'       => 'alg_wc_stock_snapshot_history',
			),
		);
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Settings_History();
