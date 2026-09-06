<?php
/**
 * Shop toolbar: filters button (off-canvas), result count, sort, grid/list toggle. $args: view.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_offcanvas = 'offcanvas' === webgram_option( 'shop_filters' ) || 'full-width' === webgram_layout();
?>
<div class="wg-shop-toolbar" data-wg-toolbar>
	<div class="wg-shop-toolbar__left">
		<?php if ( $webgram_offcanvas && ( is_active_sidebar( 'sidebar-shop' ) || has_action( 'webgram/shop/filters' ) ) ) : ?>
			<button type="button" class="wg-btn wg-btn--outline wg-btn--sm wg-shop-toolbar__filters" data-wg-toggle="filters" aria-controls="wg-filters" aria-expanded="false"><?php webgram_icon( 'filter' ); ?><span><?php esc_html_e( 'Filters', 'webgram' ); ?></span></button>
		<?php elseif ( is_active_sidebar( 'sidebar-shop' ) ) : ?>
			<button type="button" class="wg-btn wg-btn--outline wg-btn--sm wg-shop-toolbar__filters wg-shop-toolbar__filters--mobile" data-wg-toggle="filters-mobile" aria-controls="wg-filters-mobile" aria-expanded="false"><?php webgram_icon( 'filter' ); ?><span><?php esc_html_e( 'Filters', 'webgram' ); ?></span></button>
		<?php endif; ?>
		<?php woocommerce_result_count(); ?>
	</div>
	<div class="wg-shop-toolbar__right">
		<?php woocommerce_catalog_ordering(); ?>
		<?php if ( webgram_option( 'shop_grid_list_toggle' ) ) : ?>
			<div class="wg-view-toggle" role="group" aria-label="<?php esc_attr_e( 'View', 'webgram' ); ?>">
				<button type="button" class="wg-view-toggle__btn<?php echo 'grid' === $args['view'] ? ' is-active' : ''; ?>" data-wg-view="grid" aria-pressed="<?php echo 'grid' === $args['view'] ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Grid view', 'webgram' ); ?>"><?php webgram_icon( 'grid' ); ?></button>
				<button type="button" class="wg-view-toggle__btn<?php echo 'list' === $args['view'] ? ' is-active' : ''; ?>" data-wg-view="list" aria-pressed="<?php echo 'list' === $args['view'] ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'List view', 'webgram' ); ?>"><?php webgram_icon( 'list' ); ?></button>
			</div>
		<?php endif; ?>
		<?php do_action( 'webgram/shop/toolbar_end' ); ?>
	</div>
</div>
