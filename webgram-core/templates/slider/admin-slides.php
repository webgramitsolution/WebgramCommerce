<?php
/**
 * Admin: slides repeater. $args: slides.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Modules\Slider\Slides;

$wgc_row = static function ( int $i, array $s ): void {
	$img = static function ( string $key, string $label ) use ( $i, $s ): void {
		$id  = (int) ( $s[ $key ] ?? 0 );
		$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		printf(
			'<div class="wgc-slide-field"><label>%1$s</label><div class="wgc-image-field"><input type="hidden" name="wg_slides[%2$d][%3$s]" value="%4$d"><img src="%5$s" alt="" %6$s><button type="button" class="button wgc-image-select">%7$s</button> <button type="button" class="button-link wgc-image-remove" %6$s>%8$s</button></div></div>',
			esc_html( $label ),
			$i,
			esc_attr( $key ),
			$id,
			esc_url( (string) $src ),
			$src ? '' : 'hidden',
			esc_html__( 'Choose', 'webgram-core' ),
			esc_html__( 'Remove', 'webgram-core' )
		);
	};
	$text = static function ( string $key, string $label, string $type = 'text' ) use ( $i, $s ): void {
		printf( '<div class="wgc-slide-field"><label>%1$s</label><input type="%2$s" name="wg_slides[%3$d][%4$s]" value="%5$s" class="widefat"></div>', esc_html( $label ), esc_attr( $type ), $i, esc_attr( $key ), esc_attr( (string) ( $s[ $key ] ?? '' ) ) );
	};
	$select = static function ( string $key, string $label, array $choices ) use ( $i, $s ): void {
		printf( '<div class="wgc-slide-field"><label>%1$s</label><select name="wg_slides[%2$d][%3$s]">', esc_html( $label ), $i, esc_attr( $key ) );
		foreach ( $choices as $val => $lab ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( (string) ( $s[ $key ] ?? '' ), $val, false ), esc_html( $lab ) );
		}
		echo '</select></div>';
	};
	?>
	<div class="wgc-slide" data-wgc-slide>
		<div class="wgc-slide__bar">
			<strong><?php esc_html_e( 'Slide', 'webgram-core' ); ?> <span data-wgc-slide-index><?php echo (int) ( $i + 1 ); ?></span></strong>
			<span>
				<button type="button" class="button-link" data-wgc-slide-move="up" aria-label="<?php esc_attr_e( 'Move up', 'webgram-core' ); ?>">&#9650;</button>
				<button type="button" class="button-link" data-wgc-slide-move="down" aria-label="<?php esc_attr_e( 'Move down', 'webgram-core' ); ?>">&#9660;</button>
				<button type="button" class="button-link" data-wgc-slide-toggle aria-label="<?php esc_attr_e( 'Collapse', 'webgram-core' ); ?>">&#8722;</button>
				<button type="button" class="button-link wgc-slide__remove" data-wgc-slide-remove aria-label="<?php esc_attr_e( 'Remove slide', 'webgram-core' ); ?>">&times;</button>
			</span>
		</div>
		<div class="wgc-slide__body">
			<div class="wgc-slide__images">
				<?php $img( 'image', __( 'Desktop image', 'webgram-core' ) ); ?>
				<?php $img( 'image_tablet', __( 'Tablet image (optional)', 'webgram-core' ) ); ?>
				<?php $img( 'image_mobile', __( 'Mobile image (optional)', 'webgram-core' ) ); ?>
			</div>
			<div class="wgc-slide__grid">
				<?php $text( 'subheading', __( 'Small text above heading', 'webgram-core' ) ); ?>
				<?php $text( 'heading', __( 'Heading', 'webgram-core' ) ); ?>
				<div class="wgc-slide-field wgc-slide-field--full"><label><?php esc_html_e( 'Description', 'webgram-core' ); ?></label><textarea name="wg_slides[<?php echo (int) $i; ?>][description]" rows="2" class="widefat"><?php echo esc_textarea( (string) ( $s['description'] ?? '' ) ); ?></textarea></div>
				<?php $text( 'cta_text', __( 'Button text', 'webgram-core' ) ); ?>
				<?php $text( 'cta_url', __( 'Button link', 'webgram-core' ), 'url' ); ?>
				<?php $text( 'cta2_text', __( 'Second button text', 'webgram-core' ) ); ?>
				<?php $text( 'cta2_url', __( 'Second button link', 'webgram-core' ), 'url' ); ?>
				<div class="wgc-slide-field wgc-slide-field--full"><label><?php esc_html_e( 'Benefit icons row (one per line: icon|Text, up to 4)', 'webgram-core' ); ?></label><textarea name="wg_slides[<?php echo (int) $i; ?>][benefits]" rows="2" class="widefat" placeholder="truck|Free shipping&#10;shield|Secure payment"><?php echo esc_textarea( implode( "\n", array_map( static fn( $b ) => $b['icon'] . '|' . $b['text'], (array) ( $s['benefits'] ?? [] ) ) ) ); ?></textarea></div>
				<?php $select( 'align', __( 'Text align', 'webgram-core' ), [ 'left' => __( 'Left', 'webgram-core' ), 'center' => __( 'Center', 'webgram-core' ), 'right' => __( 'Right', 'webgram-core' ) ] ); ?>
				<?php $select( 'valign', __( 'Vertical position', 'webgram-core' ), [ 'top' => __( 'Top', 'webgram-core' ), 'middle' => __( 'Middle', 'webgram-core' ), 'bottom' => __( 'Bottom', 'webgram-core' ) ] ); ?>
				<?php $select( 'animation', __( 'Content animation', 'webgram-core' ), [ 'fade' => __( 'Fade', 'webgram-core' ), 'slide' => __( 'Slide up', 'webgram-core' ), 'zoom' => __( 'Zoom', 'webgram-core' ) ] ); ?>
				<div class="wgc-slide-field"><label><?php esc_html_e( 'Overlay color and opacity', 'webgram-core' ); ?></label><input type="text" name="wg_slides[<?php echo (int) $i; ?>][overlay_color]" value="<?php echo esc_attr( (string) ( $s['overlay_color'] ?? '' ) ); ?>" class="wgc-color-field" data-default-color=""> <input type="number" name="wg_slides[<?php echo (int) $i; ?>][overlay_opacity]" value="<?php echo (int) ( $s['overlay_opacity'] ?? 0 ); ?>" min="0" max="100" class="small-text"> %</div>
				<div class="wgc-slide-field"><label><?php esc_html_e( 'Text color', 'webgram-core' ); ?></label><input type="text" name="wg_slides[<?php echo (int) $i; ?>][text_color]" value="<?php echo esc_attr( (string) ( $s['text_color'] ?? '' ) ); ?>" class="wgc-color-field" data-default-color=""></div>
			</div>
		</div>
	</div>
	<?php
};
?>
<div class="wgc-slides" data-wgc-slides data-max="<?php echo (int) Slides::MAX_SLIDES; ?>">
	<div data-wgc-slides-list>
		<?php foreach ( $args['slides'] as $wgc_i => $wgc_slide ) : ?>
			<?php $wgc_row( (int) $wgc_i, $wgc_slide ); ?>
		<?php endforeach; ?>
	</div>
	<template data-wgc-slide-template><?php $wgc_row( 999, [] ); ?></template>
	<p><button type="button" class="button button-secondary" data-wgc-slide-add><?php esc_html_e( 'Add slide', 'webgram-core' ); ?></button></p>
</div>
