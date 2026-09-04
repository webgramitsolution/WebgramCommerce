<?php
/**
 * Header search form with mic slot (Core voice search) and live results dropdown.
 * $args: id, placeholder, style, min_width, button.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_id   = (string) ( $args['id'] ?? 'wg-search' );
$webgram_live = (bool) webgram_option( 'search_live' );
$webgram_pop  = array_filter( array_map( 'trim', explode( "\n", (string) webgram_option( 'search_popular' ) ) ) );
?>
<form role="search" method="get" class="wg-search wg-search--<?php echo esc_attr( (string) ( $args['style'] ?? 'pill' ) ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>" style="--wg-search-min:<?php echo (int) ( $args['min_width'] ?? 420 ); ?>px" data-wg-component="search" data-live="<?php echo $webgram_live ? '1' : '0'; ?>">
	<label class="wg-sr-only" for="<?php echo esc_attr( $webgram_id ); ?>"><?php esc_html_e( 'Search products', 'webgram' ); ?></label>
	<?php webgram_icon( 'search', 'wg-search__icon' ); ?>
	<input id="<?php echo esc_attr( $webgram_id ); ?>" class="wg-search__input" type="search" name="s" placeholder="<?php echo esc_attr( (string) ( $args['placeholder'] ?? __( 'Search products...', 'webgram' ) ) ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" autocomplete="off" aria-autocomplete="list" aria-controls="<?php echo esc_attr( $webgram_id ); ?>-results" aria-expanded="false">
	<?php if ( class_exists( 'WooCommerce' ) && 'product' === webgram_option( 'search_scope' ) ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>
	<div class="wg-search__tools">
		<button class="wg-search__clear" type="button" hidden aria-label="<?php esc_attr_e( 'Clear search', 'webgram' ); ?>"><?php webgram_icon( 'close' ); ?></button>
		<?php do_action( 'webgram/search/input', $webgram_id ); ?>
		<button class="wg-search__submit<?php echo empty( $args['button'] ) ? ' wg-sr-only' : ' wg-btn wg-btn--sm'; ?>" type="submit"><?php esc_html_e( 'Search', 'webgram' ); ?></button>
	</div>
	<?php if ( $webgram_live ) : ?>
		<div class="wg-search__results" id="<?php echo esc_attr( $webgram_id ); ?>-results" role="listbox" hidden>
			<?php if ( $webgram_pop ) : ?>
				<div class="wg-search__popular" data-wg-popular>
					<span class="wg-search__group-title"><?php esc_html_e( 'Popular searches', 'webgram' ); ?></span>
					<ul>
						<?php foreach ( $webgram_pop as $webgram_term ) : ?>
							<li><a href="<?php echo esc_url( add_query_arg( [ 's' => $webgram_term, 'post_type' => class_exists( 'WooCommerce' ) ? 'product' : '' ], home_url( '/' ) ) ); ?>"><?php webgram_icon( 'trending' ); ?><?php echo esc_html( $webgram_term ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<div class="wg-search__live" data-wg-live></div>
		</div>
	<?php endif; ?>
</form>
