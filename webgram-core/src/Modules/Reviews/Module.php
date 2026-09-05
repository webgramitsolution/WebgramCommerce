<?php
namespace Webgram\Core\Modules\Reviews;

use Webgram\Core\Abstracts\AjaxHandler;
use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Advanced Reviews on top of WooCommerce review comments: summary with distribution bars, sortable and filterable
 * list with load more, photo and video uploads, helpful votes, admin column, schema, shortcode. The Webgram theme
 * hands over its reviews section through webgram/product/reviews; any other theme keeps WooCommerce's default
 * template, which Core enhances through WooCommerce's own review hooks.
 */
final class Module extends BaseModule {

	private bool $third_party = false;

	public function id(): string {
		return 'reviews';
	}

	public function name(): string {
		return __( 'Advanced Reviews', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Rating summary, photo and video reviews, sorting, filtering, helpful votes and load more, built on WooCommerce reviews.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function phase(): int {
		return 4;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_shortcode( 'webgram_reviews', [ $this, 'shortcode' ] );
		add_filter( 'webgram_core/elementor/widgets', [ $this, 'widget_definition' ] );
		Schema::register();

		$plugin = Compat::third_party();
		if ( '' !== $plugin && Helpers::bool( $this->settings()->get( 'disable_with_third_party', true ) ) ) {
			$this->third_party = true;
			add_action( 'admin_notices', function () use ( $plugin ): void {
				if ( current_user_can( 'manage_options' ) ) {
					printf( '<div class="notice notice-info"><p>%s</p></div>', esc_html( sprintf( /* translators: %s: plugin name */ __( 'Webgram Reviews is standing down because %s is active. Turn off "Stand down when a review plugin is active" in Webgram > Settings > Reviews to use both.', 'webgram-core' ), $plugin ) ) );
				}
			} );
			return;
		}

		( new Submission( $this ) )->register();
		if ( is_admin() ) {
			( new AdminColumn() )->register();
		}
		add_action( 'webgram/product/reviews', [ $this, 'render_block' ] );
		// Default WooCommerce template (other themes): enhance in place.
		add_filter( 'woocommerce_reviews_title', [ $this, 'default_summary' ], 10, 3 );
		add_action( 'woocommerce_review_before_comment_text', [ $this, 'default_before_text' ] );
		add_action( 'woocommerce_review_after_comment_text', [ $this, 'default_after_text' ] );

		( new class( $this ) extends AjaxHandler {
			public function __construct( private Module $module ) {}
			protected function action(): string {
				return 'reviews_load';
			}
			protected function fields(): array {
				return [ 'product_id' => 'int', 'page' => 'int', 'sort' => 'key', 'stars' => 'int', 'media' => 'bool', 'per_page' => 'int' ];
			}
			protected function handle( array $input ): void {
				$product = wc_get_product( (int) $input['product_id'] );
				if ( ! $product ) {
					$this->error( __( 'Product not found.', 'webgram-core' ), 404 );
				}
				$this->success( $this->module->page_data( $product, $input ) );
			}
		} )->register();

		( new class( $this ) extends AjaxHandler {
			public function __construct( private Module $module ) {}
			protected function action(): string {
				return 'reviews_helpful';
			}
			protected function fields(): array {
				return [ 'comment_id' => 'int' ];
			}
			protected function handle( array $input ): void {
				$result = $this->module->vote_helpful( (int) $input['comment_id'] );
				if ( isset( $result['error'] ) ) {
					$this->error( $result['error'], 403 );
				}
				$this->success( $result );
			}
		} )->register();
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-reviews', 'css/reviews.css' );
		$assets->script( 'webgram-core-reviews', 'js/reviews.js', [ 'webgram-core-base' ] );
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'use_form', 'label' => __( 'Use Webgram review form', 'webgram-core' ), 'type' => 'checkbox', 'default' => true, 'description' => __( 'Adds title, star input, photo and video upload, recommendation and consent to the review form.', 'webgram-core' ) ],
			[ 'id' => 'per_page', 'label' => __( 'Reviews per page', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 50, 'default' => 4 ],
			[ 'id' => 'default_sort', 'label' => __( 'Default sort', 'webgram-core' ), 'type' => 'select', 'options' => [ 'newest' => __( 'Newest', 'webgram-core' ), 'helpful' => __( 'Most helpful', 'webgram-core' ), 'highest' => __( 'Highest rating', 'webgram-core' ), 'media' => __( 'With photos first', 'webgram-core' ) ], 'default' => 'newest' ],
			[ 'id' => 'show_summary', 'label' => __( 'Show rating summary with bars', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_filters', 'label' => __( 'Show star filter chips', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_sort', 'label' => __( 'Show sort dropdown', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_helpful', 'label' => __( 'Show "Was this helpful?" votes', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'guest_helpful', 'label' => __( 'Guests can vote helpful', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_recommend', 'label' => __( 'Ask "Would you recommend this product?"', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_media', 'label' => __( 'Show review photos and videos', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'max_files', 'label' => __( 'Maximum files per review', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 5, 'default' => 5, 'description' => __( '0 disables uploads.', 'webgram-core' ) ],
			[ 'id' => 'max_mb', 'label' => __( 'Maximum file size (MB)', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 64, 'default' => 8 ],
			[ 'id' => 'allow_video', 'label' => __( 'Allow MP4 video', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'require_consent', 'label' => __( 'Require publication consent checkbox', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'rate_limit', 'label' => __( 'Reviews per hour per visitor', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 50, 'default' => 3 ],
			[ 'id' => 'disable_with_third_party', 'label' => __( 'Stand down when a review plugin is active', 'webgram-core' ), 'type' => 'checkbox', 'default' => true, 'description' => __( 'Judge.me, YITH Advanced Reviews or Customer Reviews for WooCommerce.', 'webgram-core' ) ],
		];
	}

	// Setting accessors used by the helper classes.
	public function use_form(): bool {
		return Helpers::bool( $this->settings()->get( 'use_form', true ) );
	}
	public function max_files(): int {
		return max( 0, min( 5, (int) $this->settings()->get( 'max_files', 5 ) ) );
	}
	public function max_mb(): int {
		return max( 1, (int) $this->settings()->get( 'max_mb', 8 ) );
	}
	public function allow_video(): bool {
		return Helpers::bool( $this->settings()->get( 'allow_video', true ) );
	}
	public function show_recommend(): bool {
		return Helpers::bool( $this->settings()->get( 'show_recommend', true ) );
	}
	public function require_consent(): bool {
		return Helpers::bool( $this->settings()->get( 'require_consent', true ) );
	}
	public function rate_limit(): int {
		return max( 1, (int) $this->settings()->get( 'rate_limit', 3 ) );
	}
	public function per_page(): int {
		return max( 1, (int) $this->settings()->get( 'per_page', 4 ) );
	}
	private function flag( string $key, bool $default = true ): bool {
		return Helpers::bool( $this->settings()->get( $key, $default ) );
	}

	public function render( string $template, array $args = [] ): string {
		return $this->view( $template, $args, false );
	}

	public function widget_definition( array $widgets ): array {
		$widgets['reviews'] = [ 'title' => __( 'Webgram Reviews', 'webgram-core' ), 'icon' => 'eicon-review', 'shortcode' => 'webgram_reviews', 'controls' => [ 'product_id' => [ 'label' => __( 'Product', 'webgram-core' ), 'type' => 'product' ] ] ];
		return $widgets;
	}

	/** Item data for templates. */
	public function item_data( \WP_Comment $comment ): array {
		$rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
		$id     = (int) $comment->comment_ID;
		return (array) apply_filters(
			'webgram_core/reviews/item',
			[
				'id'        => $id,
				'author'    => get_comment_author( $comment ),
				'avatar'    => get_avatar( $comment, 48, '', '', [ 'class' => 'wgc-review__avatar' ] ),
				'verified'  => function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $comment ),
				'rating'    => $rating,
				'title'     => (string) get_comment_meta( $id, '_wg_title', true ),
				'body'      => (string) apply_filters( 'comment_text', get_comment_text( $comment ), $comment, [] ),
				'recommend' => get_comment_meta( $id, '_wg_recommend', true ),
				'media'     => $this->flag( 'show_media' ) ? Media::items( $id ) : [],
				'helpful'   => (int) get_comment_meta( $id, '_wg_helpful', true ),
				'voted'     => in_array( $this->voter_hash(), (array) get_comment_meta( $id, '_wg_helpful_voters', true ), true ),
				'date'      => (string) get_comment_date( '', $comment ),
				'datetime'  => (string) get_comment_date( 'c', $comment ),
				'relative'  => sprintf( /* translators: %s: human time */ __( '%s ago', 'webgram-core' ), human_time_diff( (int) get_comment_time( 'U', true, $comment ), time() ) ),
				'show_helpful' => $this->flag( 'show_helpful' ),
			],
			$comment
		);
	}

	private function voter_hash(): string {
		$id = is_user_logged_in() ? 'u' . get_current_user_id() : 'ip' . Helpers::ip_hash();
		return substr( hash( 'sha256', 'wg-helpful|' . $id ), 0, 20 );
	}

	/** @return array{count?: int, voted?: bool, error?: string} */
	public function vote_helpful( int $comment_id ): array {
		if ( ! $this->flag( 'show_helpful' ) ) {
			return [ 'error' => __( 'Voting is disabled.', 'webgram-core' ) ];
		}
		if ( ! is_user_logged_in() && ! $this->flag( 'guest_helpful' ) ) {
			return [ 'error' => __( 'Please log in to vote.', 'webgram-core' ) ];
		}
		$comment = get_comment( $comment_id );
		if ( ! $comment || '1' !== (string) $comment->comment_approved || 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
			return [ 'error' => __( 'Review not found.', 'webgram-core' ) ];
		}
		if ( ! Helpers::rate_limit( 'reviews_helpful', 60, HOUR_IN_SECONDS ) ) {
			return [ 'error' => __( 'Too many votes. Please try again later.', 'webgram-core' ) ];
		}
		$voters = array_values( array_filter( array_map( 'strval', (array) get_comment_meta( $comment_id, '_wg_helpful_voters', true ) ) ) );
		$hash   = $this->voter_hash();
		if ( in_array( $hash, $voters, true ) ) {
			return [ 'count' => (int) get_comment_meta( $comment_id, '_wg_helpful', true ), 'voted' => true, 'message' => __( 'You already voted.', 'webgram-core' ) ];
		}
		$voters[] = $hash;
		$count    = (int) get_comment_meta( $comment_id, '_wg_helpful', true ) + 1;
		update_comment_meta( $comment_id, '_wg_helpful_voters', $voters );
		update_comment_meta( $comment_id, '_wg_helpful', $count );
		do_action( 'webgram_core/analytics/event', 'review_helpful', [ 'comment_id' => $comment_id ], 'reviews' );
		return [ 'count' => $count, 'voted' => true, 'message' => __( 'Thanks for your feedback.', 'webgram-core' ) ];
	}

	/** List page for the AJAX endpoint and the initial render. */
	public function page_data( \WC_Product $product, array $raw ): array {
		$params = Query::params( $raw + [ 'sort' => $this->settings()->get( 'default_sort', 'newest' ) ], $this->per_page() );
		$result = Query::fetch( $product->get_id(), $params );
		$html   = '';
		foreach ( $result['items'] as $comment ) {
			$html .= $this->render( 'item', [ 'review' => $this->item_data( $comment ) ] );
		}
		$showing = Summary::showing( $result['page'], $result['per_page'], $result['total'] );
		return [
			'html'     => $html,
			'total'    => $result['total'],
			'page'     => $result['page'],
			'pages'    => $result['pages'],
			'has_more' => $result['page'] < $result['pages'],
			'showing'  => $result['total'] ? sprintf( /* translators: 1: first index, 2: last index, 3: total */ __( 'Showing %1$d-%2$d of %3$d reviews', 'webgram-core' ), $showing['from'], $showing['to'], $showing['total'] ) : __( 'No reviews yet', 'webgram-core' ),
			'params'   => $params,
		];
	}

	public function can_review( \WC_Product $product ): bool {
		if ( ! comments_open( $product->get_id() ) ) {
			return false;
		}
		if ( 'no' === get_option( 'woocommerce_review_rating_verification_required', 'no' ) ) {
			return true;
		}
		return is_user_logged_in() && wc_customer_bought_product( '', get_current_user_id(), $product->get_id() );
	}

	/** Comment form arguments for our own block (the theme path). Mirrors WooCommerce's argument set. */
	public function form_args( \WC_Product $product ): array {
		$commenter = wp_get_current_commenter();
		$args      = [
			'title_reply'          => $product->get_review_count() ? __( 'Add a review', 'webgram-core' ) : __( 'Be the first to review', 'webgram-core' ),
			'title_reply_to'       => __( 'Leave a reply to %s', 'webgram-core' ),
			'title_reply_before'   => '<span id="reply-title" class="comment-reply-title ' . esc_attr( Helpers::css_class( 'review-form__title' ) ) . '">',
			'title_reply_after'    => '</span>',
			'comment_notes_after'  => '',
			'label_submit'         => __( 'Submit review', 'webgram-core' ),
			'class_submit'         => 'submit wg-btn wg-btn--primary',
			'class_form'           => 'comment-form ' . Helpers::css_class( 'review-form' ),
			'logged_in_as'         => '',
			'comment_field'        => $this->use_form() ? ( new Submission( $this ) )->fields_html( true ) : $this->default_comment_field(),
			'fields'               => [
				'author' => '<p class="comment-form-author"><label for="author">' . esc_html__( 'Name', 'webgram-core' ) . '&nbsp;<span class="required">*</span></label><input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" required></p>',
				'email'  => '<p class="comment-form-email"><label for="email">' . esc_html__( 'Email', 'webgram-core' ) . '&nbsp;<span class="required">*</span></label><input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30" required></p>',
			],
		];
		return (array) apply_filters( 'woocommerce_product_review_comment_form_args', $args );
	}

	private function default_comment_field(): string {
		$html = '';
		if ( wc_review_ratings_enabled() ) {
			$html .= '<div class="comment-form-rating"><label for="rating">' . esc_html__( 'Your rating', 'webgram-core' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label><select name="rating" id="rating" required>'
				. '<option value="">' . esc_html__( 'Rate', 'webgram-core' ) . '</option>';
			for ( $i = 5; $i >= 1; $i-- ) {
				$html .= '<option value="' . $i . '">' . esc_html( sprintf( /* translators: %d: stars */ _n( '%d star', '%d stars', $i, 'webgram-core' ), $i ) ) . '</option>';
			}
			$html .= '</select></div>';
		}
		return $html . '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your review', 'webgram-core' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';
	}

	/** Full block for the Webgram theme (webgram/product/reviews) and the shortcode. */
	public function block_html( \WC_Product $product ): string {
		\webgram_core()->assets()->enqueue_module( 'reviews' );
		$page = $this->page_data( $product, [] );
		return $this->render(
			'block',
			[
				'product'      => $product,
				'summary'      => Summary::for_product( $product ),
				'page'         => $page,
				'per_page'     => $this->per_page(),
				'show_summary' => $this->flag( 'show_summary' ),
				'show_filters' => $this->flag( 'show_filters' ),
				'show_sort'    => $this->flag( 'show_sort' ),
				'sorts'        => [ 'newest' => __( 'Newest', 'webgram-core' ), 'oldest' => __( 'Oldest', 'webgram-core' ), 'highest' => __( 'Highest', 'webgram-core' ), 'lowest' => __( 'Lowest', 'webgram-core' ), 'helpful' => __( 'Most helpful', 'webgram-core' ), 'media' => __( 'With photos', 'webgram-core' ) ],
				'can_review'   => $this->can_review( $product ),
				'form_args'    => $this->form_args( $product ),
				'ratings_on'   => wc_review_ratings_enabled(),
			]
		);
	}

	public function render_block( $product = null ): void {
		if ( ! $product instanceof \WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( $product ) {
			echo $this->block_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output.
		}
	}

	public function shortcode( $atts = [] ): string {
		$atts    = shortcode_atts( [ 'product_id' => 0 ], (array) $atts, 'webgram_reviews' );
		$product = wc_get_product( (int) $atts['product_id'] ?: get_the_ID() );
		if ( ! $product ) {
			return '';
		}
		return $this->block_html( $product );
	}

	// Default template enhancements (any theme without the Webgram reviews hook).

	public function default_summary( string $title, int $count, $product ): string {
		if ( ! $product instanceof \WC_Product || ! $this->flag( 'show_summary' ) || did_action( 'webgram/product/reviews' ) ) {
			return $title;
		}
		\webgram_core()->assets()->enqueue_module( 'reviews' );
		return $title . $this->render( 'summary', [ 'summary' => Summary::for_product( $product ), 'product' => $product, 'show_button' => false ] );
	}

	public function default_before_text( $comment ): void {
		if ( ! $comment instanceof \WP_Comment || did_action( 'webgram/product/reviews' ) ) {
			return;
		}
		$title = (string) get_comment_meta( $comment->comment_ID, '_wg_title', true );
		if ( '' !== $title ) {
			echo '<strong class="' . esc_attr( Helpers::css_class( 'review__title' ) ) . '">' . esc_html( $title ) . '</strong>';
		}
	}

	public function default_after_text( $comment ): void {
		if ( ! $comment instanceof \WP_Comment || did_action( 'webgram/product/reviews' ) ) {
			return;
		}
		\webgram_core()->assets()->enqueue_module( 'reviews' );
		$review = $this->item_data( $comment );
		echo $this->render( 'item-extras', [ 'review' => $review ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output.
	}
}
