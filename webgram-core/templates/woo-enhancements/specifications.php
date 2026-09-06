<?php
/**
 * Specifications zebra table. $args: rows (label, value), title.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<section class="<?php echo esc_attr( Helpers::css_class( 'specs', 'wg-product__section' ) ); ?>" data-wg-accordion>
	<h2 class="<?php echo esc_attr( Helpers::css_class( 'specs__title', 'wg-product__section-title' ) ); ?>"><?php echo esc_html( $args['title'] ); ?></h2>
	<div class="<?php echo esc_attr( Helpers::css_class( 'specs__body', 'wg-product__section-body' ) ); ?>">
		<table class="<?php echo esc_attr( Helpers::css_class( 'specs__table' ) ); ?>">
			<tbody>
			<?php foreach ( $args['rows'] as $wgc_row ) : ?>
				<tr><th scope="row"><?php echo esc_html( $wgc_row['label'] ); ?></th><td><?php echo esc_html( $wgc_row['value'] ); ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</section>
