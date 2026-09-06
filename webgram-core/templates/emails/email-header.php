<?php
/**
 * Branded WooCommerce email header. Replaces woocommerce/templates/emails/email-header.php while the Webgram
 * email templates are on. Keeps the same structure and ids so WooCommerce's inline CSS and plugin styles apply.
 *
 * @package Webgram\Core
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Modules\Emails\Branding;

$wgc_module = webgram_core()->modules()->get( 'emails' );
$wgc_s      = $wgc_module ? $wgc_module->settings()->all() : [];
$wgc_t      = Branding::tokens( $wgc_s );
$wgc_logo   = ! empty( $wgc_s['logo'] ) ? wp_get_attachment_image_url( (int) $wgc_s['logo'], 'medium' ) : '';
$email_heading = $email_heading ?? '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="color-scheme" content="light">
	<title><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin:0;padding:0;background-color:<?php echo esc_attr( $wgc_t['body_bg'] ); ?>">
	<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" style="background-color:<?php echo esc_attr( $wgc_t['body_bg'] ); ?>;margin:0;padding:40px 0;width:100%;font-family:<?php echo esc_attr( $wgc_t['font'] ); ?>">
		<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
			<tr>
				<td align="center" valign="top">
					<div id="template_header_image" style="margin-bottom:16px">
						<?php if ( $wgc_logo ) : ?>
							<p style="margin:0;text-align:center"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( $wgc_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" width="<?php echo (int) $wgc_t['logo_width']; ?>" style="max-width:<?php echo (int) $wgc_t['logo_width']; ?>px;height:auto;border:0"></a></p>
						<?php else : ?>
							<p style="margin:0;text-align:center;font-size:22px;font-weight:bold;color:<?php echo esc_attr( $wgc_t['text_color'] ); ?>"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:<?php echo esc_attr( $wgc_t['text_color'] ); ?>;text-decoration:none"><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></a></p>
						<?php endif; ?>
					</div>
					<table border="0" cellpadding="0" cellspacing="0" width="<?php echo (int) $wgc_t['width']; ?>" id="template_container" style="background-color:<?php echo esc_attr( $wgc_t['content_bg'] ); ?>;border-radius:12px;max-width:100%">
						<tr>
							<td align="center" valign="top">
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" style="background-color:<?php echo esc_attr( $wgc_t['header_bg'] ); ?>;color:<?php echo esc_attr( $wgc_t['header_text'] ); ?>;border-radius:12px 12px 0 0">
									<tr>
										<td id="header_wrapper" style="padding:32px 40px;display:block">
											<h1 style="color:<?php echo esc_attr( $wgc_t['header_text'] ); ?>;font-family:<?php echo esc_attr( $wgc_t['font'] ); ?>;font-size:26px;font-weight:bold;line-height:1.3;margin:0;text-align:<?php echo is_rtl() ? 'right' : 'left'; ?>"><?php echo esc_html( $email_heading ); ?></h1>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align="center" valign="top">
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
									<tr>
										<td valign="top" id="body_content" style="background-color:<?php echo esc_attr( $wgc_t['content_bg'] ); ?>">
											<table border="0" cellpadding="20" cellspacing="0" width="100%">
												<tr>
													<td valign="top" id="body_content_inner_cell">
														<div id="body_content_inner" style="color:<?php echo esc_attr( $wgc_t['text_color'] ); ?>;font-family:<?php echo esc_attr( $wgc_t['font'] ); ?>;font-size:14px;line-height:1.6;text-align:<?php echo is_rtl() ? 'right' : 'left'; ?>">
