<?php
/**
 * Branded WooCommerce email styles. Replaces woocommerce/templates/emails/email-styles.php while the Webgram
 * email templates are on. Output passes through woocommerce_email_styles so other plugins can append rules.
 *
 * @package Webgram\Core
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Modules\Emails\Branding;

$wgc_module = webgram_core()->modules()->get( 'emails' );
$wgc_t      = Branding::tokens( $wgc_module ? $wgc_module->settings()->all() : [] );
$wgc_align  = is_rtl() ? 'right' : 'left';
?>
#wrapper { background-color: <?php echo esc_attr( $wgc_t['body_bg'] ); ?>; margin: 0; padding: 40px 0; width: 100%; font-family: <?php echo esc_attr( $wgc_t['font'] ); ?>; }
#template_container { background-color: <?php echo esc_attr( $wgc_t['content_bg'] ); ?>; border-radius: 12px; }
#template_header { background-color: <?php echo esc_attr( $wgc_t['header_bg'] ); ?>; color: <?php echo esc_attr( $wgc_t['header_text'] ); ?>; border-radius: 12px 12px 0 0; }
#template_header h1, #template_header h1 a { color: <?php echo esc_attr( $wgc_t['header_text'] ); ?>; font-family: <?php echo esc_attr( $wgc_t['font'] ); ?>; }
#body_content, #body_content_inner, #body_content table td, #body_content p, #body_content li { color: <?php echo esc_attr( $wgc_t['text_color'] ); ?>; font-family: <?php echo esc_attr( $wgc_t['font'] ); ?>; font-size: 14px; line-height: 1.6; text-align: <?php echo esc_attr( $wgc_align ); ?>; }
#body_content_inner h2, #body_content_inner h3 { color: <?php echo esc_attr( $wgc_t['text_color'] ); ?>; font-family: <?php echo esc_attr( $wgc_t['font'] ); ?>; font-weight: bold; line-height: 1.3; margin: 0 0 16px; text-align: <?php echo esc_attr( $wgc_align ); ?>; }
#body_content_inner h2 { font-size: 18px; }
#body_content_inner h3 { font-size: 16px; }
#body_content_inner a, #template_footer a { color: <?php echo esc_attr( $wgc_t['link_color'] ); ?>; font-weight: normal; text-decoration: underline; }
#body_content_inner .td, .td { border: 1px solid #e5e7eb; color: <?php echo esc_attr( $wgc_t['text_color'] ); ?>; padding: 12px; vertical-align: middle; }
#body_content_inner .order_item td img { border-radius: 8px; }
#body_content_inner .address { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; color: <?php echo esc_attr( $wgc_t['text_color'] ); ?>; }
#body_content_inner .button, #body_content_inner a.button, #body_content_inner .wc-button, #body_content_inner .wgc-email-button { display: inline-block; padding: 12px 24px; background-color: <?php echo esc_attr( $wgc_t['button_color'] ); ?>; color: <?php echo esc_attr( $wgc_t['button_text'] ); ?>; border-radius: <?php echo (int) $wgc_t['button_radius']; ?>px; text-decoration: none; font-weight: bold; }
#template_footer #credit { color: <?php echo esc_attr( $wgc_t['text_color'] ); ?>; opacity: .7; font-family: <?php echo esc_attr( $wgc_t['font'] ); ?>; font-size: 12px; line-height: 1.6; text-align: center; padding: 12px 24px; }
.text { color: <?php echo esc_attr( $wgc_t['text_color'] ); ?>; font-family: <?php echo esc_attr( $wgc_t['font'] ); ?>; }
img { border: none; display: inline-block; font-size: 14px; font-weight: bold; height: auto; outline: none; text-decoration: none; text-transform: capitalize; vertical-align: middle; max-width: 100%; }
@media screen and (max-width: 640px) { #template_container, #template_footer { width: 100% !important; } #header_wrapper { padding: 24px !important; } }
