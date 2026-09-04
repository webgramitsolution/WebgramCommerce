<?php
/**
 * Maintenance / coming soon page (full document). $args: mode, block, title, text, countdown, bg.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_bg = $args['bg'] ? (string) wp_get_attachment_image_url( (int) $args['bg'], 'full' ) : '';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $args['title'] ?: get_bloginfo( 'name' ) ); ?></title>
	<?php wp_head(); ?>
</head>
<body class="wgc-maintenance-body">
<div class="<?php echo esc_attr( Helpers::css_class( 'maintenance' ) ); ?>" <?php echo $wgc_bg ? 'style="background-image:url(' . esc_url( $wgc_bg ) . ')"' : ''; ?>>
	<div class="<?php echo esc_attr( Helpers::css_class( 'maintenance__inner' ) ); ?>">
		<?php if ( $args['block'] ) : ?>
			<?php echo $args['block']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output. ?>
		<?php else : ?>
			<?php if ( has_custom_logo() ) : ?><div class="<?php echo esc_attr( Helpers::css_class( 'maintenance__logo' ) ); ?>"><?php the_custom_logo(); ?></div><?php endif; ?>
			<h1><?php echo esc_html( $args['title'] ); ?></h1>
			<p><?php echo esc_html( $args['text'] ); ?></p>
			<?php if ( $args['countdown'] > time() ) : ?>
				<div class="<?php echo esc_attr( Helpers::css_class( 'countdown' ) ); ?>" data-wgc-countdown="<?php echo (int) $args['countdown']; ?>">
					<?php foreach ( [ 'days' => __( 'Days', 'webgram-core' ), 'hours' => __( 'Hours', 'webgram-core' ), 'minutes' => __( 'Minutes', 'webgram-core' ), 'seconds' => __( 'Seconds', 'webgram-core' ) ] as $wgc_unit => $wgc_label ) : ?>
						<div class="<?php echo esc_attr( Helpers::css_class( 'countdown__unit' ) ); ?>"><strong data-unit="<?php echo esc_attr( $wgc_unit ); ?>">0</strong><span><?php echo esc_html( $wgc_label ); ?></span></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
