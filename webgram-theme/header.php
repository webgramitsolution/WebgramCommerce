<?php
/**
 * Document head and site header. Header parts hook into webgram/header (see inc/template-hooks.php).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="wg-skip-link wg-sr-only" href="#primary"><?php esc_html_e( 'Skip to content', 'webgram' ); ?></a>
<div id="page" class="wg-site">
	<?php do_action( 'webgram/before_header' ); ?>
	<header id="masthead" class="wg-header" data-wg-component="header">
		<?php do_action( 'webgram/header' ); ?>
	</header>
	<?php do_action( 'webgram/after_header' ); ?>
	<div id="content" class="wg-site-content">
