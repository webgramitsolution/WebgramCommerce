<?php
/**
 * Tab: Cart and checkout (slide cart drawer, cart page, checkout, thank you).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'cart_checkout',
	'label'    => __( 'Cart and checkout', 'webgram' ),
	'icon'     => 'cart',
	'priority' => 145,
	'sections' => [
		'drawer' => [
			'label'  => __( 'Cart drawer', 'webgram' ),
			'fields' => [
				'cart_drawer'           => [ 'label' => __( 'Slide-in cart drawer', 'webgram' ), 'type' => 'switch', 'description' => __( 'Opens from the header cart icon. When off, the icon links to the cart page.', 'webgram' ) ],
				'cart_after_add'        => [ 'label' => __( 'After adding to cart', 'webgram' ), 'type' => 'radio', 'choices' => [ 'drawer' => __( 'Open the drawer', 'webgram' ), 'toast' => __( 'Show a toast', 'webgram' ), 'none' => __( 'Do nothing', 'webgram' ) ], 'show_if' => [ 'cart_drawer', '==', true ] ],
				'cart_drawer_progress'  => [ 'label' => __( 'Offer progress (Core Coupons)', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'cart_drawer', '==', true ] ],
				'cart_drawer_recommend' => [ 'label' => __( 'Recommendations row', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'cart_drawer', '==', true ] ],
				'cart_drawer_coupon'    => [ 'label' => __( 'Coupon field in drawer', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'cart_drawer', '==', true ] ],
				'cart_drawer_savings'   => [ 'label' => __( 'Show savings line', 'webgram' ), 'description' => __( 'Sale discounts plus applied coupons.', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'cart_drawer', '==', true ] ],
				'cart_drawer_note'      => [ 'label' => __( 'Note under subtotal', 'webgram' ), 'type' => 'text', 'show_if' => [ 'cart_drawer', '==', true ] ],
				'cart_drawer_button'    => [ 'label' => __( 'Checkout button label', 'webgram' ), 'type' => 'text', 'show_if' => [ 'cart_drawer', '==', true ] ],
				'cart_drawer_subline'   => [ 'label' => __( 'Button subline', 'webgram' ), 'type' => 'text', 'show_if' => [ 'cart_drawer', '==', true ] ],
				'cart_drawer_payments'  => [ 'label' => __( 'Payment icons under the button', 'webgram' ), 'type' => 'multicheck', 'choices' => webgram_payment_icon_choices(), 'show_if' => [ 'cart_drawer', '==', true ] ],
				'cart_drawer_view_cart' => [ 'label' => __( '"View cart" link', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'cart_drawer', '==', true ] ],
			],
		],
		'cart' => [
			'label'  => __( 'Cart page', 'webgram' ),
			'fields' => [
				'cart_sticky_summary' => [ 'label' => __( 'Sticky summary card', 'webgram' ), 'type' => 'switch' ],
				'cart_cross_sells'    => [ 'label' => __( 'Cross-sells shown', 'webgram' ), 'type' => 'range', 'min' => 0, 'max' => 8 ],
				'cart_empty_products' => [ 'label' => __( 'Suggested products on empty cart', 'webgram' ), 'type' => 'range', 'min' => 0, 'max' => 8 ],
			],
		],
		'checkout' => [
			'label'  => __( 'Checkout', 'webgram' ),
			'fields' => [
				'checkout_steps'        => [ 'label' => __( 'Progress steps header (Cart, Details, Payment)', 'webgram' ), 'type' => 'switch' ],
				'checkout_sticky'       => [ 'label' => __( 'Sticky order review', 'webgram' ), 'type' => 'switch' ],
				'checkout_coupon_place' => [ 'label' => __( 'Coupon form', 'webgram' ), 'type' => 'radio', 'choices' => [ 'summary' => __( 'Inside the order summary', 'webgram' ), 'top' => __( 'Above the form (WooCommerce default)', 'webgram' ) ] ],
				'checkout_trust_text'   => [ 'label' => __( 'Trust text under Place order', 'webgram' ), 'type' => 'text' ],
			],
		],
		'thankyou' => [
			'label'  => __( 'Thank you page', 'webgram' ),
			'fields' => [
				'thankyou_timeline' => [ 'label' => __( 'Status timeline', 'webgram' ), 'type' => 'switch' ],
				'thankyou_continue' => [ 'label' => __( '"Continue shopping" button', 'webgram' ), 'type' => 'switch' ],
			],
		],
	],
];
