<?php
/**
 * Stock Snapshot for WooCommerce - Emails Section Settings
 *
 * @version 1.2.0
 * @since   1.2.0
 *
 * @author  Algoritmika Ltd
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Stock_Snapshot_Settings_Emails' ) ) :

class Alg_WC_Stock_Snapshot_Settings_Emails extends Alg_WC_Stock_Snapshot_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function __construct() {
		$this->id   = 'emails';
		$this->desc = __( 'Emails', 'stock-snapshot-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function get_settings() {
		return array(
			array(
				'title'    => __( 'Email Options', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => __( 'Enable stock snapshot report emails.', 'stock-snapshot-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_stock_snapshot_email_options',
			),
			array(
				'title'    => __( 'Emails', 'stock-snapshot-for-woocommerce' ),
				'desc'     => __( 'Enable', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => apply_filters( 'alg_wc_stock_snapshot_settings',
					'<p style="padding:15px;color:black;background-color:white;font-weight:bold;">' .
						'You will need <a target="_blank" href="https://wpfactory.com/item/stock-snapshot-for-woocommerce/">Stock Snapshot for WooCommerce Pro</a> plugin version to enable this section.' .
					'</p>' ),
				'id'       => 'alg_wc_stock_snapshot_email_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_stock_snapshot_settings', array( 'disabled' => 'disabled' ) ),
			),
			array(
				'title'    => __( 'Email address', 'stock-snapshot-for-woocommerce' ),
				'desc_tip' => __( 'Sends stock snapshot report emails to this address.', 'stock-snapshot-for-woocommerce' ),
				'id'       => 'alg_wc_stock_snapshot_email_address',
				'default'  => get_option( 'admin_email' ),
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Email subject', 'stock-snapshot-for-woocommerce' ),
				'desc'     => sprintf( __( 'Available placeholders: %s', 'stock-snapshot-for-woocommerce' ),
					'<code>' . implode( '</code>, <code>', array( '{site_title}', '{date}' ) ) . '</code>' ),
				'id'       => 'alg_wc_stock_snapshot_email_subject',
				'default'  => esc_html__( '{site_title} - Stock Snapshot - {date}', 'stock-snapshot-for-woocommerce' ),
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Email heading', 'stock-snapshot-for-woocommerce' ),
				'id'       => 'alg_wc_stock_snapshot_email_heading',
				'default'  => esc_html__( 'Stock Snapshot', 'stock-snapshot-for-woocommerce' ),
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Email content', 'stock-snapshot-for-woocommerce' ),
				'desc'     => sprintf( __( 'Available placeholders: %s', 'stock-snapshot-for-woocommerce' ),
					'<code>' . implode( '</code>, <code>', array( '{stock_changes}', '{all_stock}' ) ) . '</code>' ),
				'id'       => 'alg_wc_stock_snapshot_email_content',
				'default'  =>
					'<h3>' . esc_html__( 'Stock Changes', 'stock-snapshot-for-woocommerce' ) . '</h3>' . PHP_EOL .
					'<div style="margin-bottom: 40px;">' . '{stock_changes}' . '</div>' . PHP_EOL .
					'<h3>' . esc_html__( 'All Stock', 'stock-snapshot-for-woocommerce' ) . '</h3>' . PHP_EOL .
					'<div style="margin-bottom: 40px;">' . '{all_stock}' . '</div>',
				'type'     => 'textarea',
				'css'      => 'width:100%;height:200px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_stock_snapshot_email_options',
			),
		);
	}

}

endif;

return new Alg_WC_Stock_Snapshot_Settings_Emails();
