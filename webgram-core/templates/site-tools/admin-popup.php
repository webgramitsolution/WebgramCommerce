<?php
/**
 * Popup settings metabox. $args: settings, blocks (id => title).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

$wgc_s      = $args['settings'];
$wgc_field  = static fn( string $k ): string => 'wg_popup[' . $k . ']';
$wgc_devs   = [ 'desktop' => __( 'Desktop', 'webgram-core' ), 'tablet' => __( 'Tablet', 'webgram-core' ), 'mobile' => __( 'Mobile', 'webgram-core' ) ];
$wgc_pages  = [ 'home' => __( 'Homepage', 'webgram-core' ), 'shop' => __( 'Shop and categories', 'webgram-core' ), 'product' => __( 'Product pages', 'webgram-core' ), 'cart' => __( 'Cart', 'webgram-core' ), 'checkout' => __( 'Checkout', 'webgram-core' ), 'account' => __( 'My account', 'webgram-core' ), 'blog' => __( 'Blog', 'webgram-core' ), 'page' => __( 'Pages', 'webgram-core' ), 'other' => __( 'Everything else', 'webgram-core' ) ];
$wgc_src    = $wgc_s['image'] ? wp_get_attachment_image_url( (int) $wgc_s['image'], 'medium' ) : '';
?>
<table class="form-table wgc-popup-settings" role="presentation">
	<tr><th scope="row"><?php esc_html_e( 'Status', 'webgram-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $wgc_field( 'enabled' ) ); ?>" value="1" <?php checked( $wgc_s['enabled'] ); ?>> <?php esc_html_e( 'Active', 'webgram-core' ); ?></label></td></tr>
	<tr><th scope="row"><?php esc_html_e( 'Content', 'webgram-core' ); ?></th><td>
		<label><input type="radio" name="<?php echo esc_attr( $wgc_field( 'source' ) ); ?>" value="content" <?php checked( $wgc_s['source'], 'content' ); ?>> <?php esc_html_e( 'This post (editor or Elementor)', 'webgram-core' ); ?></label><br>
		<label><input type="radio" name="<?php echo esc_attr( $wgc_field( 'source' ) ); ?>" value="block" <?php checked( $wgc_s['source'], 'block' ); ?>> <?php esc_html_e( 'HTML Block', 'webgram-core' ); ?></label>
		<select name="<?php echo esc_attr( $wgc_field( 'block' ) ); ?>"><option value="0"><?php esc_html_e( 'Select a block', 'webgram-core' ); ?></option><?php foreach ( (array) $args['blocks'] as $wgc_id => $wgc_title ) : ?><option value="<?php echo (int) $wgc_id; ?>" <?php selected( (int) $wgc_s['block'], (int) $wgc_id ); ?>><?php echo esc_html( (string) $wgc_title ); ?></option><?php endforeach; ?></select>
	</td></tr>
	<tr><th scope="row"><?php esc_html_e( 'Side image', 'webgram-core' ); ?></th><td><div class="wgc-image-field"><input type="hidden" name="<?php echo esc_attr( $wgc_field( 'image' ) ); ?>" value="<?php echo (int) $wgc_s['image']; ?>"><img src="<?php echo esc_url( (string) $wgc_src ); ?>" alt="" <?php echo $wgc_src ? '' : 'hidden'; ?>><button type="button" class="button wgc-image-select"><?php esc_html_e( 'Choose image', 'webgram-core' ); ?></button> <button type="button" class="button-link wgc-image-remove" <?php echo $wgc_src ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'webgram-core' ); ?></button></div></td></tr>
	<tr><th scope="row"><?php esc_html_e( 'Width', 'webgram-core' ); ?></th><td><input type="number" name="<?php echo esc_attr( $wgc_field( 'width' ) ); ?>" value="<?php echo (int) $wgc_s['width']; ?>" min="320" max="1200" step="10" class="small-text"> px</td></tr>
	<tr><th scope="row"><?php esc_html_e( 'Accessible label', 'webgram-core' ); ?></th><td><input type="text" name="<?php echo esc_attr( $wgc_field( 'label' ) ); ?>" value="<?php echo esc_attr( $wgc_s['label'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Special offer', 'webgram-core' ); ?>"></td></tr>
	<tr><th scope="row"><?php esc_html_e( 'Trigger', 'webgram-core' ); ?></th><td>
		<select name="<?php echo esc_attr( $wgc_field( 'trigger' ) ); ?>">
			<?php foreach ( [ 'delay' => __( 'After a delay', 'webgram-core' ), 'load' => __( 'Immediately', 'webgram-core' ), 'scroll' => __( 'After scrolling', 'webgram-core' ), 'exit' => __( 'Exit intent (desktop)', 'webgram-core' ), 'click' => __( 'Click on an element', 'webgram-core' ) ] as $wgc_k => $wgc_l ) : ?>
				<option value="<?php echo esc_attr( $wgc_k ); ?>" <?php selected( $wgc_s['trigger'], $wgc_k ); ?>><?php echo esc_html( $wgc_l ); ?></option>
			<?php endforeach; ?>
		</select>
		<p><label><?php esc_html_e( 'Delay (seconds)', 'webgram-core' ); ?> <input type="number" name="<?php echo esc_attr( $wgc_field( 'delay' ) ); ?>" value="<?php echo (int) $wgc_s['delay']; ?>" min="0" max="300" class="small-text"></label>
		<label><?php esc_html_e( 'Scroll depth (%)', 'webgram-core' ); ?> <input type="number" name="<?php echo esc_attr( $wgc_field( 'scroll' ) ); ?>" value="<?php echo (int) $wgc_s['scroll']; ?>" min="1" max="100" class="small-text"></label></p>
		<p><label><?php esc_html_e( 'Click selector', 'webgram-core' ); ?> <input type="text" name="<?php echo esc_attr( $wgc_field( 'selector' ) ); ?>" value="<?php echo esc_attr( $wgc_s['selector'] ); ?>" class="regular-text" placeholder=".open-offer, #newsletter-link"></label><br><span class="description"><?php esc_html_e( 'CSS selector of the buttons or links that open this popup.', 'webgram-core' ); ?></span></p>
	</td></tr>
	<tr><th scope="row"><?php esc_html_e( 'Show again', 'webgram-core' ); ?></th><td>
		<select name="<?php echo esc_attr( $wgc_field( 'frequency' ) ); ?>">
			<option value="days" <?php selected( $wgc_s['frequency'], 'days' ); ?>><?php esc_html_e( 'After a number of days', 'webgram-core' ); ?></option>
			<option value="session" <?php selected( $wgc_s['frequency'], 'session' ); ?>><?php esc_html_e( 'Once per browser session', 'webgram-core' ); ?></option>
			<option value="always" <?php selected( $wgc_s['frequency'], 'always' ); ?>><?php esc_html_e( 'Every page load', 'webgram-core' ); ?></option>
		</select>
		<label><?php esc_html_e( 'Days', 'webgram-core' ); ?> <input type="number" name="<?php echo esc_attr( $wgc_field( 'days' ) ); ?>" value="<?php echo (int) $wgc_s['days']; ?>" min="1" max="365" class="small-text"></label>
	</td></tr>
	<tr><th scope="row"><?php esc_html_e( 'Devices', 'webgram-core' ); ?></th><td><?php foreach ( $wgc_devs as $wgc_k => $wgc_l ) : ?><label class="wgc-inline"><input type="checkbox" name="<?php echo esc_attr( $wgc_field( 'devices' ) ); ?>[]" value="<?php echo esc_attr( $wgc_k ); ?>" <?php checked( in_array( $wgc_k, $wgc_s['devices'], true ) ); ?>> <?php echo esc_html( $wgc_l ); ?></label> <?php endforeach; ?></td></tr>
	<tr><th scope="row"><?php esc_html_e( 'Show on', 'webgram-core' ); ?></th><td><?php foreach ( $wgc_pages as $wgc_k => $wgc_l ) : ?><label class="wgc-inline"><input type="checkbox" name="<?php echo esc_attr( $wgc_field( 'pages' ) ); ?>[]" value="<?php echo esc_attr( $wgc_k ); ?>" <?php checked( in_array( $wgc_k, $wgc_s['pages'], true ) ); ?>> <?php echo esc_html( $wgc_l ); ?></label><br><?php endforeach; ?>
		<p><label><?php esc_html_e( 'Also on these post or product ids', 'webgram-core' ); ?> <input type="text" name="<?php echo esc_attr( $wgc_field( 'include' ) ); ?>" value="<?php echo esc_attr( $wgc_s['include'] ); ?>" class="regular-text" placeholder="12, 48"></label></p>
		<p><label><?php esc_html_e( 'Never on these ids', 'webgram-core' ); ?> <input type="text" name="<?php echo esc_attr( $wgc_field( 'exclude' ) ); ?>" value="<?php echo esc_attr( $wgc_s['exclude'] ); ?>" class="regular-text"></label></p>
	</td></tr>
</table>
