<?php
/**
 * Portfolio card (Webgram Core portfolio post type).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_terms = get_the_terms( get_the_ID(), 'wg_portfolio_cat' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'wg-portfolio-card' ); ?>>
	<a class="wg-portfolio-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'webgram-card', [ 'loading' => 'lazy' ] ); ?>
		<?php else : ?>
			<span class="wg-portfolio-card__placeholder"><?php webgram_icon( 'image' ); ?></span>
		<?php endif; ?>
	</a>
	<div class="wg-portfolio-card__body">
		<?php if ( is_array( $webgram_terms ) && $webgram_terms ) : ?>
			<span class="wg-portfolio-card__cat"><?php echo esc_html( $webgram_terms[0]->name ); ?></span>
		<?php endif; ?>
		<h2 class="wg-portfolio-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<?php if ( has_excerpt() ) : ?>
			<div class="wg-portfolio-card__excerpt"><?php the_excerpt(); ?></div>
		<?php endif; ?>
	</div>
</article>
