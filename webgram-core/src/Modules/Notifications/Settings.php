<?php
namespace Webgram\Core\Modules\Notifications;

defined( 'ABSPATH' ) || exit;

/** Settings fields for the Core settings screen: channels, event matrix, WhatsApp credentials, templates, opt in. */
final class Settings {

	public static function fields( Module $module ): array {
		$test = wp_nonce_url( add_query_arg( 'action', 'webgram_core_whatsapp_test', admin_url( 'admin-post.php' ) ), 'webgram_core_whatsapp_test' );
		$sync = wp_nonce_url( add_query_arg( 'action', 'webgram_core_whatsapp_sync', admin_url( 'admin-post.php' ) ), 'webgram_core_whatsapp_sync' );
		$out  = [
			[ 'id' => 'h_general', 'label' => __( 'General', 'webgram-core' ), 'type' => 'heading' ],
			[ 'id' => 'default_country', 'label' => __( 'Default country for phone numbers', 'webgram-core' ), 'type' => 'text', 'default' => 'IN', 'description' => __( 'Two letter code used when an order has no billing country.', 'webgram-core' ) ],
			[ 'id' => 'status_out_for_delivery', 'label' => __( 'Add the "Out for delivery" order status', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'retention_days', 'label' => __( 'Keep the notification log for (days)', 'webgram-core' ), 'type' => 'number', 'min' => 7, 'max' => 730, 'default' => 180 ],
			[ 'id' => 'h_channels', 'label' => __( 'Channels', 'webgram-core' ), 'type' => 'heading' ],
			[ 'id' => 'channel_email', 'label' => __( 'Email channel', 'webgram-core' ), 'type' => 'checkbox', 'default' => true, 'description' => __( 'WooCommerce keeps sending its own emails; the matrix below turns them on or off per event. Shipped and Out for delivery emails are sent by Webgram with the Emails module branding.', 'webgram-core' ) ],
			[ 'id' => 'channel_whatsapp', 'label' => __( 'WhatsApp channel (Meta Cloud API)', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
			[ 'id' => 'h_matrix', 'label' => __( 'Events', 'webgram-core' ), 'type' => 'heading', 'description' => __( 'Which events send on which channel. WhatsApp also needs customer consent and a mapped template.', 'webgram-core' ) ],
		];
		foreach ( Events::all() as $event => $def ) {
			foreach ( [ 'email', 'whatsapp' ] as $channel ) {
				$out[] = [ 'id' => 'on_' . $event . '_' . $channel, 'label' => sprintf( '%s: %s', $def['label'], 'email' === $channel ? __( 'Email', 'webgram-core' ) : 'WhatsApp' ), 'type' => 'checkbox', 'default' => (bool) $def['default'] ];
			}
		}
		$status = $module->whatsapp_status();
		$out    = array_merge(
			$out,
			[
				[ 'id' => 'h_wa', 'label' => __( 'WhatsApp Cloud API', 'webgram-core' ), 'type' => 'heading', 'description' => __( 'Uses your own Meta WhatsApp Business account. Webgram never relays or bills messages. Message charges, if any, are billed by Meta to your WhatsApp Business account according to Meta\'s pricing.', 'webgram-core' ) ],
				[ 'id' => 'wa_status', 'label' => __( 'Status', 'webgram-core' ), 'type' => 'info', 'content' => $status['html'] ],
				[ 'id' => 'wa_phone_number_id', 'label' => __( 'Phone Number ID', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
				[ 'id' => 'wa_waba_id', 'label' => __( 'WhatsApp Business Account ID', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
				[ 'id' => 'wa_access_token', 'label' => __( 'Access token', 'webgram-core' ), 'type' => 'secret', 'default' => '', 'description' => __( 'System user token with whatsapp_business_messaging permission. Stored encrypted.', 'webgram-core' ) ],
				[ 'id' => 'wa_api_version', 'label' => __( 'Graph API version', 'webgram-core' ), 'type' => 'text', 'default' => 'v21.0' ],
				[ 'id' => 'wa_app_secret', 'label' => __( 'App secret (webhook signature)', 'webgram-core' ), 'type' => 'secret', 'default' => '' ],
				[ 'id' => 'wa_verify_token', 'label' => __( 'Webhook verify token', 'webgram-core' ), 'type' => 'text', 'default' => '', 'description' => sprintf( /* translators: %s: url */ __( 'Webhook URL to register in the Meta app: %s', 'webgram-core' ), '<code>' . esc_html( rest_url( 'webgram/v1/whatsapp/webhook' ) ) . '</code>' ) ],
				[ 'id' => 'wa_test', 'label' => __( 'Connection', 'webgram-core' ), 'type' => 'link', 'url' => $test, 'button' => __( 'Test connection', 'webgram-core' ) ],
				[ 'id' => 'wa_sync', 'label' => __( 'Templates', 'webgram-core' ), 'type' => 'link', 'url' => $sync, 'button' => __( 'Sync templates from Meta', 'webgram-core' ), 'description' => sprintf( /* translators: %d: count */ __( '%d templates synced. Recommended template texts: docs/whatsapp-templates.md in the plugin folder.', 'webgram-core' ), count( Templates::synced() ) ) ],
				[ 'id' => 'wa_language', 'label' => __( 'Default template language', 'webgram-core' ), 'type' => 'text', 'default' => 'en' ],
				[ 'id' => 'h_tpl', 'label' => __( 'WhatsApp template per event', 'webgram-core' ), 'type' => 'heading', 'description' => __( 'Pick the approved Meta template and list the body parameters in order, for example {customer_name}, {order_number}, {order_total}. Variables: ', 'webgram-core' ) . '{' . implode( '}, {', Templates::VARIABLES ) . '}' ],
			]
		);
		foreach ( Events::all() as $event => $def ) {
			$out[] = [ 'id' => 'wa_tpl_' . $event, 'label' => sprintf( /* translators: %s: event */ __( '%s: template', 'webgram-core' ), $def['label'] ), 'type' => 'select', 'options' => Templates::choices(), 'default' => '' ];
			$out[] = [ 'id' => 'wa_params_' . $event, 'label' => sprintf( /* translators: %s: event */ __( '%s: parameters', 'webgram-core' ), $def['label'] ), 'type' => 'text', 'default' => '{customer_name}, {order_number}' ];
			$out[] = [ 'id' => 'wa_doc_' . $event, 'label' => sprintf( /* translators: %s: event */ __( '%s: attach invoice PDF (document header)', 'webgram-core' ), $def['label'] ), 'type' => 'checkbox', 'default' => false ];
		}
		$out = array_merge(
			$out,
			[
				[ 'id' => 'h_email_tpl', 'label' => __( 'Email texts for shipping events', 'webgram-core' ), 'type' => 'heading' ],
				[ 'id' => 'email_subject_shipped', 'label' => __( 'Shipped: subject', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
				[ 'id' => 'email_body_shipped', 'label' => __( 'Shipped: body', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
				[ 'id' => 'email_subject_out_for_delivery', 'label' => __( 'Out for delivery: subject', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
				[ 'id' => 'email_body_out_for_delivery', 'label' => __( 'Out for delivery: body', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
				[ 'id' => 'h_optin', 'label' => __( 'Consent', 'webgram-core' ), 'type' => 'heading' ],
				[ 'id' => 'optin_enabled', 'label' => __( 'Show the WhatsApp consent checkbox at checkout', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
				[ 'id' => 'optin_label', 'label' => __( 'Checkbox label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Send order updates on WhatsApp', 'webgram-core' ) ],
				[ 'id' => 'optin_default', 'label' => __( 'Checked by default', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
			]
		);
		return $out;
	}
}
