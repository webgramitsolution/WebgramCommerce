<?php
/**
 * Template Name: Help
 * Description: FAQ accordion (Webgram Core settings) and contact cards.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();
webgram_part( 'misc/page-title', [ 'title' => get_the_title() ] );

$webgram_faqs     = array_filter( (array) apply_filters( 'webgram/help/faqs', [] ), static fn( $f ) => ! empty( $f['q'] ) );
$webgram_contacts = array_filter( (array) apply_filters( 'webgram/help/contacts', [] ), static fn( $c ) => ! empty( $c['title'] ) );
?>
<div class="wg-container">
	<main id="primary" class="wg-main wg-help">
		<?php
		while ( have_posts() ) :
			the_post();
			if ( '' !== trim( get_the_content() ) ) {
				echo '<div class="wg-prose wg-help__intro">';
				the_content();
				echo '</div>';
			}
		endwhile;
		?>
		<?php if ( $webgram_contacts ) : ?>
			<div class="wg-help__contacts wg-contact-cards">
				<?php foreach ( $webgram_contacts as $webgram_c ) : ?>
					<a class="wg-contact-card" href="<?php echo esc_url( (string) ( $webgram_c['url'] ?? '#' ) ); ?>"<?php echo str_starts_with( (string) ( $webgram_c['url'] ?? '' ), 'http' ) ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php webgram_icon( (string) ( $webgram_c['icon'] ?? 'help-circle' ) ); ?>
						<span><small><?php echo esc_html( (string) ( $webgram_c['text'] ?? '' ) ); ?></small><strong><?php echo esc_html( (string) $webgram_c['title'] ); ?></strong></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( $webgram_faqs ) : ?>
			<div class="wg-faq" data-wg-component="faq">
				<h2 class="wg-faq__title"><?php esc_html_e( 'Frequently asked questions', 'webgram' ); ?></h2>
				<?php foreach ( $webgram_faqs as $webgram_i => $webgram_f ) : ?>
					<details class="wg-faq__item"<?php echo 0 === $webgram_i ? ' open' : ''; ?>>
						<summary class="wg-faq__q"><?php echo esc_html( (string) $webgram_f['q'] ); ?><?php webgram_icon( 'chevron-down' ); ?></summary>
						<div class="wg-faq__a wg-prose"><?php echo wp_kses_post( wpautop( (string) ( $webgram_f['a'] ?? '' ) ) ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		<?php elseif ( ! webgram_has_core( 'site_tools' ) ) : ?>
			<p class="wg-notice wg-notice--info"><?php esc_html_e( 'FAQ entries are managed in the Webgram Core plugin (Site Tools, Help page tab).', 'webgram' ); ?></p>
		<?php endif; ?>
		<?php do_action( 'webgram/help/after' ); ?>
	</main>
</div>
<?php
get_footer();
