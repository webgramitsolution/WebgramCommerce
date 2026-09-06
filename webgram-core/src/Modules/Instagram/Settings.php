<?php
namespace Webgram\Core\Modules\Instagram;

defined( 'ABSPATH' ) || exit;

/** Instagram settings tab in the shared field schema. */
final class Settings {

	public static function definitions( Module $module ): array {
		$test = wp_nonce_url( add_query_arg( 'action', 'webgram_core_instagram_test', admin_url( 'admin-post.php' ) ), 'webgram_core_instagram_test' );
		return [
			'instagram' => [
				'id'          => 'instagram',
				'label'       => __( 'Instagram feed', 'webgram-core' ),
				'icon'        => 'social-instagram',
				'priority'    => 60,
				'description' => __( 'Show your latest posts through the Instagram Graph API (Business or Creator account connected to a Facebook Page) or curate them manually.', 'webgram-core' ),
				'sections'    => [
					'source'  => [
						'title'  => __( 'Source', 'webgram-core' ),
						'fields' => [
							'mode'         => [ 'label' => __( 'Source', 'webgram-core' ), 'type' => 'radio', 'choices' => [ 'api' => __( 'Instagram Graph API (access token)', 'webgram-core' ), 'manual' => __( 'Manual gallery', 'webgram-core' ) ], 'default' => 'manual' ],
							'api_note'     => [ 'label' => '', 'type' => 'info', 'content' => __( 'Meta retired the Instagram Basic Display API in December 2024. API mode needs an Instagram Business or Creator account linked to a Facebook Page, its Instagram Business Account ID and a long-lived access token from a Meta app.', 'webgram-core' ), 'show_if' => [ 'mode', '==', 'api' ] ],
							'ig_user_id'   => [ 'label' => __( 'Instagram Business Account ID', 'webgram-core' ), 'type' => 'text', 'default' => '', 'show_if' => [ 'mode', '==', 'api' ] ],
							'access_token' => [ 'label' => __( 'Long-lived access token', 'webgram-core' ), 'type' => 'secret', 'default' => '', 'description' => __( 'Stored encrypted. Instagram Login tokens are refreshed automatically every 30 days; Facebook Login tokens last 60 days and must be regenerated.', 'webgram-core' ), 'show_if' => [ 'mode', '==', 'api' ] ],
							'api_version'  => [ 'label' => __( 'Graph API version', 'webgram-core' ), 'type' => 'text', 'default' => 'v21.0', 'show_if' => [ 'mode', '==', 'api' ] ],
							'cache_hours'  => [ 'label' => __( 'Cache posts for (hours)', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 168, 'default' => 12, 'show_if' => [ 'mode', '==', 'api' ] ],
							'test'         => [ 'label' => __( 'Connection', 'webgram-core' ), 'type' => 'link', 'url' => $test, 'button' => __( 'Test connection', 'webgram-core' ), 'description' => $module->status_text(), 'show_if' => [ 'mode', '==', 'api' ] ],
							'manual_items' => [ 'label' => __( 'Manual posts', 'webgram-core' ), 'type' => 'repeater', 'max' => 24, 'default' => [], 'fields' => [ 'image' => [ 'label' => __( 'Image', 'webgram-core' ), 'type' => 'image' ], 'link' => [ 'label' => __( 'Link', 'webgram-core' ), 'type' => 'url' ], 'caption' => [ 'label' => __( 'Caption', 'webgram-core' ), 'type' => 'text' ] ], 'show_if' => [ 'mode', '==', 'manual' ] ],
						],
					],
					'display' => [
						'title'  => __( 'Display defaults', 'webgram-core' ),
						'fields' => [
							'title'        => [ 'label' => __( 'Section title', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Instagram Feed', 'webgram-core' ) ],
							'count'        => [ 'label' => __( 'Posts to show', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 24, 'default' => 6 ],
							'columns'      => [ 'label' => __( 'Columns', 'webgram-core' ), 'type' => 'number', 'min' => 2, 'max' => 8, 'default' => 6 ],
							'layout'       => [ 'label' => __( 'Layout', 'webgram-core' ), 'type' => 'radio', 'choices' => [ 'grid' => __( 'Grid', 'webgram-core' ), 'slider' => __( 'Slider', 'webgram-core' ) ], 'default' => 'grid' ],
							'show_caption' => [ 'label' => __( 'Show caption on hover', 'webgram-core' ), 'type' => 'switch', 'default' => true ],
							'follow_url'   => [ 'label' => __( '"Follow us" link', 'webgram-core' ), 'type' => 'url', 'default' => '' ],
							'follow_text'  => [ 'label' => __( '"Follow us" text', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Follow Us', 'webgram-core' ) ],
						],
					],
				],
			],
		];
	}
}
