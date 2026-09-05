<?php
/**
 * Review form fields: star input, title, body, media upload, recommend, consent. $args: rating (bool), max_files,
 * max_mb, allow_video, show_recommend, accept.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<input type="hidden" name="wg_review" value="1">
<?php if ( ! empty( $args['rating'] ) ) : ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'review-form__rating', 'comment-form-rating' ) ); ?>">
		<span class="<?php echo esc_attr( Helpers::css_class( 'review-form__label' ) ); ?>"><?php esc_html_e( 'Your rating', 'webgram-core' ); ?> <span class="required">*</span></span>
		<fieldset class="<?php echo esc_attr( Helpers::css_class( 'star-input' ) ); ?>" data-wgc-star-input>
			<legend class="wg-sr-only"><?php esc_html_e( 'Rating', 'webgram-core' ); ?></legend>
			<?php for ( $wgc_i = 1; $wgc_i <= 5; $wgc_i++ ) : ?>
				<label class="<?php echo esc_attr( Helpers::css_class( 'star-input__star' ) ); ?>">
					<input type="radio" name="rating" value="<?php echo (int) $wgc_i; ?>" required>
					<svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.95 6.1 6.7.9-4.9 4.7 1.2 6.7L12 17.7l-5.95 3.2 1.2-6.7-4.9-4.7 6.7-.9z"/></svg>
					<span class="wg-sr-only"><?php echo esc_html( sprintf( /* translators: %d: stars */ _n( '%d star', '%d stars', $wgc_i, 'webgram-core' ), $wgc_i ) ); ?></span>
				</label>
			<?php endfor; ?>
		</fieldset>
	</div>
<?php endif; ?>
<p class="<?php echo esc_attr( Helpers::css_class( 'review-form__field', 'comment-form-title' ) ); ?>">
	<label for="wg_title"><?php esc_html_e( 'Review title', 'webgram-core' ); ?></label>
	<input type="text" id="wg_title" name="wg_title" maxlength="120" placeholder="<?php esc_attr_e( 'Sum it up in a few words', 'webgram-core' ); ?>">
</p>
<p class="<?php echo esc_attr( Helpers::css_class( 'review-form__field', 'comment-form-comment' ) ); ?>">
	<label for="comment"><?php esc_html_e( 'Your review', 'webgram-core' ); ?> <span class="required">*</span></label>
	<textarea id="comment" name="comment" rows="6" required placeholder="<?php esc_attr_e( 'What did you like or dislike? How did you use it?', 'webgram-core' ); ?>"></textarea>
</p>
<?php if ( (int) $args['max_files'] > 0 ) : ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'review-form__media' ) ); ?>" data-wgc-review-media data-max="<?php echo (int) $args['max_files']; ?>" data-max-mb="<?php echo (int) $args['max_mb']; ?>" data-error-count="<?php echo esc_attr( sprintf( /* translators: %d: files */ __( 'You can attach up to %d files.', 'webgram-core' ), (int) $args['max_files'] ) ); ?>" data-error-size="<?php echo esc_attr( sprintf( /* translators: %d: MB */ __( 'Each file must be smaller than %d MB.', 'webgram-core' ), (int) $args['max_mb'] ) ); ?>">
		<label class="<?php echo esc_attr( Helpers::css_class( 'review-form__upload' ) ); ?>">
			<input type="file" name="wg_review_media[]" multiple accept="<?php echo esc_attr( $args['accept'] ); ?>" aria-describedby="wgc-review-media-error">
			<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
			<span><?php echo esc_html( $args['allow_video'] ? __( 'Add photos or a video', 'webgram-core' ) : __( 'Add photos', 'webgram-core' ) ); ?></span>
			<small><?php echo esc_html( sprintf( /* translators: 1: files, 2: MB */ __( 'Up to %1$d files, %2$d MB each', 'webgram-core' ), (int) $args['max_files'], (int) $args['max_mb'] ) ); ?></small>
		</label>
		<div class="<?php echo esc_attr( Helpers::css_class( 'review-form__previews' ) ); ?>" data-wgc-review-previews></div>
		<p class="<?php echo esc_attr( Helpers::css_class( 'review-form__error' ) ); ?>" id="wgc-review-media-error" data-wgc-review-media-error role="alert" hidden></p>
	</div>
<?php endif; ?>
<?php if ( ! empty( $args['show_recommend'] ) ) : ?>
	<fieldset class="<?php echo esc_attr( Helpers::css_class( 'review-form__recommend' ) ); ?>">
		<legend><?php esc_html_e( 'Would you recommend this product?', 'webgram-core' ); ?></legend>
		<label><input type="radio" name="wg_recommend" value="yes"> <?php esc_html_e( 'Yes', 'webgram-core' ); ?></label>
		<label><input type="radio" name="wg_recommend" value="no"> <?php esc_html_e( 'No', 'webgram-core' ); ?></label>
	</fieldset>
<?php endif; ?>
<?php if ( ! empty( $args['consent'] ) || ! isset( $args['consent'] ) ) : ?>
	<p class="<?php echo esc_attr( Helpers::css_class( 'review-form__consent' ) ); ?>">
		<label><input type="checkbox" name="wg_consent" value="1"> <?php esc_html_e( 'I confirm this review is my own experience and may be published.', 'webgram-core' ); ?></label>
	</p>
<?php endif; ?>
