<?php
namespace Webgram\Core\Modules\Reviews;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Review form fields (title, star input, media, recommend, consent) and their server side handling. Submissions
 * still go through wp-comments-post.php and WooCommerce's own rating validation; this class validates and stores
 * the extra data and rate limits by IP.
 */
final class Submission {

	public const FLAG  = 'wg_review';
	public const FILES = 'wg_review_media';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_filter( 'woocommerce_product_review_comment_form_args', [ $this, 'form_args' ] );
		add_filter( 'preprocess_comment', [ $this, 'validate' ], 20 );
		add_action( 'comment_post', [ $this, 'save' ], 10, 2 );
		add_action( 'transition_comment_status', [ $this, 'status_changed' ], 10, 3 );
		add_action( 'deleted_comment', [ $this, 'deleted' ] );
	}

	/** Fields appended to WooCommerce's default review form on any theme when "Use Webgram review form" is on. */
	public function form_args( array $args ): array {
		if ( ! $this->module->use_form() ) {
			return $args;
		}
		$args['comment_field'] = $this->fields_html( true );
		$args['class_form']    = trim( ( $args['class_form'] ?? 'comment-form' ) . ' ' . Helpers::css_class( 'review-form' ) );
		return $args;
	}

	/** Full field set for the review form: rating stars, title, body, media, recommend, consent. */
	public function fields_html( bool $include_rating ): string {
		return $this->module->render(
			'form-fields',
			[
				'rating'        => $include_rating && function_exists( 'wc_review_ratings_enabled' ) && wc_review_ratings_enabled(),
				'max_files'     => $this->module->max_files(),
				'max_mb'        => $this->module->max_mb(),
				'allow_video'   => $this->module->allow_video(),
				'show_recommend' => $this->module->show_recommend(),
				'accept'        => implode( ',', array_merge( array_values( Media::IMAGE_MIMES ), $this->module->allow_video() ? array_values( Media::VIDEO_MIMES ) : [] ) ),
			]
		);
	}

	private function is_webgram_review( array $data ): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WordPress validates comment submissions itself.
		return isset( $_POST[ self::FLAG ] ) && 'product' === get_post_type( (int) ( $data['comment_post_ID'] ?? 0 ) );
	}

	/** preprocess_comment: reject before anything is stored. */
	public function validate( array $data ): array {
		if ( ! $this->is_webgram_review( $data ) ) {
			return $data;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! Helpers::rate_limit( 'reviews_submit', $this->module->rate_limit(), HOUR_IN_SECONDS ) ) {
			$this->reject( __( 'You have submitted several reviews recently. Please try again in an hour.', 'webgram-core' ), 429 );
		}
		$title = isset( $_POST['wg_title'] ) ? sanitize_text_field( wp_unslash( $_POST['wg_title'] ) ) : '';
		if ( mb_strlen( $title ) > 120 ) {
			$this->reject( __( 'The review title is too long (120 characters maximum).', 'webgram-core' ) );
		}
		if ( $this->module->require_consent() && empty( $_POST['wg_consent'] ) ) {
			$this->reject( __( 'Please confirm that your review can be published.', 'webgram-core' ) );
		}
		$error = Media::validate_all( Media::files_from_request( self::FILES ), $this->module->max_files(), $this->module->max_mb() * MB_IN_BYTES, $this->module->allow_video() );
		if ( '' !== $error ) {
			$this->reject( $error );
		}
		// phpcs:enable
		return $data;
	}

	private function reject( string $message, int $status = 400 ): void {
		wp_die( esc_html( $message ), esc_html__( 'Review not submitted', 'webgram-core' ), [ 'response' => (int) $status, 'back_link' => true ] );
	}

	/** comment_post: store meta and upload media once the comment exists. */
	public function save( int $comment_id, $approved ): void {
		$comment = get_comment( $comment_id );
		if ( ! $comment || ! $this->is_webgram_review( [ 'comment_post_ID' => (int) $comment->comment_post_ID ] ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$title = isset( $_POST['wg_title'] ) ? sanitize_text_field( wp_unslash( $_POST['wg_title'] ) ) : '';
		if ( '' !== $title ) {
			update_comment_meta( $comment_id, '_wg_title', mb_substr( $title, 0, 120 ) );
		}
		if ( $this->module->show_recommend() && isset( $_POST['wg_recommend'] ) ) {
			update_comment_meta( $comment_id, '_wg_recommend', 'yes' === sanitize_key( wp_unslash( $_POST['wg_recommend'] ) ) ? 1 : 0 );
		}
		// phpcs:enable
		update_comment_meta( $comment_id, '_wg_helpful', 0 );
		$ids = Media::upload( Media::files_from_request( self::FILES ), $comment_id, (int) $comment->comment_post_ID, 1 === (int) $approved, $this->module->allow_video() );
		if ( $ids ) {
			update_comment_meta( $comment_id, '_wg_media', $ids );
		}
		do_action( 'webgram_core/reviews/submitted', $comment_id, $ids );
	}

	public function status_changed( string $new, string $old, $comment ): void {
		if ( $comment instanceof \WP_Comment && Media::ids( (int) $comment->comment_ID ) ) {
			Media::sync_status( (int) $comment->comment_ID, 'approved' === $new );
		}
	}

	public function deleted( $comment_id ): void {
		Media::delete_for_comment( (int) $comment_id );
	}
}
