<?php
/**
 * Tab: Custom CSS. Stored in the theme option and printed inline after the token block.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'custom_css',
	'label'    => __( 'Custom CSS', 'webgram' ),
	'icon'     => 'code',
	'priority' => 180,
	'sections' => [
		'css' => [
			'fields' => [
				'custom_css'        => [ 'label' => __( 'All devices', 'webgram' ), 'type' => 'code', 'language' => 'css', 'full' => true ],
				'custom_css_tablet' => [ 'label' => __( 'Tablet and below (max 991px)', 'webgram' ), 'type' => 'code', 'language' => 'css', 'full' => true, 'rows' => 8 ],
				'custom_css_mobile' => [ 'label' => __( 'Mobile (max 767px)', 'webgram' ), 'type' => 'code', 'language' => 'css', 'full' => true, 'rows' => 8 ],
				'js'                => [ 'label' => __( 'Custom JavaScript', 'webgram' ), 'type' => 'info', 'content' => webgram_has_core( 'site_tools' ) ? sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=webgram&tab=custom_js' ) ), esc_html__( 'Custom JS lives in the Webgram Core tab of this panel.', 'webgram' ) ) : esc_html__( 'Custom JavaScript is provided by the Webgram Core plugin (Site Tools module).', 'webgram' ) ],
			],
		],
	],
];
