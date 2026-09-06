<?php
namespace Webgram\Core\Modules\Reviews;

defined( 'ABSPATH' ) || exit;

/** Comments screen: a "Review extras" column with title, recommendation and media thumbnails. */
final class AdminColumn {

	public function register(): void {
		add_filter( 'manage_edit-comments_columns', [ $this, 'columns' ] );
		add_action( 'manage_comments_custom_column', [ $this, 'render' ], 10, 2 );
	}

	public function columns( array $columns ): array {
		$columns['wg_review'] = __( 'Review extras', 'webgram-core' );
		return $columns;
	}

	public function render( string $column, int $comment_id ): void {
		if ( 'wg_review' !== $column ) {
			return;
		}
		$title     = (string) get_comment_meta( $comment_id, '_wg_title', true );
		$recommend = get_comment_meta( $comment_id, '_wg_recommend', true );
		$helpful   = (int) get_comment_meta( $comment_id, '_wg_helpful', true );
		if ( '' !== $title ) {
			echo '<strong>' . esc_html( $title ) . '</strong><br>';
		}
		if ( '' !== $recommend ) {
			echo esc_html( $recommend ? __( 'Recommends', 'webgram-core' ) : __( 'Does not recommend', 'webgram-core' ) ) . '<br>';
		}
		if ( $helpful ) {
			echo esc_html( sprintf( /* translators: %d: votes */ _n( '%d helpful vote', '%d helpful votes', $helpful, 'webgram-core' ), $helpful ) ) . '<br>';
		}
		foreach ( Media::items( $comment_id, true ) as $item ) {
			printf(
				'<a href="%s" target="_blank" rel="noopener" style="display:inline-block;margin:2px">%s</a>',
				esc_url( $item['url'] ),
				'video' === $item['type'] ? '<span class="dashicons dashicons-video-alt3"></span>' : '<img src="' . esc_url( $item['thumb'] ) . '" alt="" width="40" height="40" style="object-fit:cover;border-radius:4px">'
			);
		}
	}
}
