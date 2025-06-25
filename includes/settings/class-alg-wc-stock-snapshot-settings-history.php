<?php
/**
 * Stock Snapshot for WooCommerce - History Section Settings
 *
 * @version 2.2.0
 * @since   1.2.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings_History' ) ) :

class Alg_WC_Stock_Snapshot_Settings_History extends Alg_WC_Stock_Snapshot_Settings_Report_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.1.0
	 * @since   1.2.0
	 *
	 * @todo    (v2.1.1) prepare data in background
	 */
	function __construct() {
		$this->id   = 'history';
		$this->desc = __( 'History', 'stock-snapshot-for-woocommerce' );
		parent::__construct();
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
	 * @version 2.2.0
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
				$date = alg_wc_stock_snapshot()->core->local_date( $date_points, $_time );
				if ( isset( $stock_snapshot[ $_time ] ) ) {
					$_stock_snapshot = $stock_snapshot[ $_time ];
					$do_add_row      = true;
					$row[ $date ]    = (
						is_array( $_stock_snapshot ) ?
						$_stock_snapshot['stock'] :
						$_stock_snapshot
					);
					if (
						'html' === $output_type &&
						is_array( $_stock_snapshot ) &&
						isset( $_stock_snapshot['hook'] ) &&
						isset( $_stock_snapshot['user_id'] )
					) {
						$row[ $date ] .= wc_help_tip(
							$this->get_admin()->context_desc->get_all( $_stock_snapshot, $product_id ) .
							' (' . $this->get_admin()->get_user_desc( $_stock_snapshot['user_id'] ) . ')'
						);
					}
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
			$date = alg_wc_stock_snapshot()->core->local_date( $date_points, $_time );
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
				wp_kses_post( $this->get_no_results_html() )
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
				$result .= $this->get_export_csv_link();
			}
		}

		// Footer
		if ( 'html' === $output_type ) {
			$result .= $this->get_footer();
		}

		// Set transient
		if ( 'html' === $output_type ) {
			$transient[ $transient_id ] = $result;
			set_transient( 'alg_wc_stock_snapshot_history', $transient, 0 );
		}

		return $result;
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
