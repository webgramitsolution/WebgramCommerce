<?php
namespace Webgram\Core\Modules\Reviews;

defined( 'ABSPATH' ) || exit;

/**
 * Review media: validation, upload as attachments (private until the review is approved), status sync,
 * EXIF stripping and URL resolution. Attachments carry _wg_review_comment_id and post_parent = product.
 */
final class Media {

	public const META_COMMENT = '_wg_review_comment_id';

	public const IMAGE_MIMES = [ 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ];
	public const VIDEO_MIMES = [ 'mp4' => 'video/mp4' ];

	/** Pure: '' when the file is acceptable, otherwise a user-facing reason. */
	public static function validate( string $name, string $type, int $size, int $error, array $allowed_mimes, int $max_bytes ): string {
		if ( UPLOAD_ERR_NO_FILE === $error ) {
			return '';
		}
		if ( UPLOAD_ERR_OK !== $error ) {
			return __( 'One of the files could not be uploaded.', 'webgram-core' );
		}
		if ( $size <= 0 || $size > $max_bytes ) {
			return sprintf( /* translators: %s: size in MB */ __( 'Each file must be smaller than %s MB.', 'webgram-core' ), number_format_i18n( $max_bytes / MB_IN_BYTES, 0 ) );
		}
		$ext = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
		$ok  = false;
		foreach ( $allowed_mimes as $exts => $mime ) {
			if ( $mime === strtolower( $type ) && in_array( $ext, explode( '|', $exts ), true ) ) {
				$ok = true;
				break;
			}
		}
		return $ok ? '' : __( 'Only JPG, PNG, WEBP images and MP4 videos are allowed.', 'webgram-core' );
	}

	/** Normalize a multi-file $_FILES entry to a list of single-file arrays. */
	public static function files_from_request( string $field ): array {
		if ( empty( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ]['name'] ?? null ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return [];
		}
		$out = [];
		foreach ( array_keys( (array) $_FILES[ $field ]['name'] ) as $i ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- keys only; each file is validated by validate_all() and wp_handle_upload().
			$file = [];
			foreach ( [ 'name', 'type', 'tmp_name', 'error', 'size' ] as $k ) {
				$file[ $k ] = $_FILES[ $field ][ $k ][ $i ] ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
			}
			if ( UPLOAD_ERR_NO_FILE !== (int) $file['error'] ) {
				$out[] = $file;
			}
		}
		return $out;
	}

	/**
	 * Validate a list of files against module settings.
	 *
	 * @return string '' when all valid
	 */
	public static function validate_all( array $files, int $max_files, int $max_bytes, bool $allow_video ): string {
		if ( count( $files ) > $max_files ) {
			return sprintf( /* translators: %d: maximum files */ __( 'You can attach up to %d files.', 'webgram-core' ), $max_files );
		}
		$allowed = $allow_video ? self::IMAGE_MIMES + self::VIDEO_MIMES : self::IMAGE_MIMES;
		foreach ( $files as $file ) {
			$real = function_exists( 'wp_check_filetype_and_ext' ) && ! empty( $file['tmp_name'] ) ? wp_check_filetype_and_ext( (string) $file['tmp_name'], (string) $file['name'], $allowed ) : [ 'type' => $file['type'] ?? '' ];
			$err  = self::validate( (string) $file['name'], (string) ( $real['type'] ?: '' ), (int) $file['size'], (int) $file['error'], $allowed, $max_bytes );
			if ( '' !== $err ) {
				return $err;
			}
		}
		return '';
	}

	/** @return int[] attachment ids */
	public static function upload( array $files, int $comment_id, int $product_id, bool $approved, bool $allow_video ): array {
		if ( ! $files ) {
			return [];
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$allowed = $allow_video ? self::IMAGE_MIMES + self::VIDEO_MIMES : self::IMAGE_MIMES;
		$ids     = [];
		foreach ( $files as $file ) {
			$moved = wp_handle_upload( $file, [ 'test_form' => false, 'mimes' => $allowed ] );
			if ( ! is_array( $moved ) || ! empty( $moved['error'] ) || empty( $moved['file'] ) ) {
				continue;
			}
			$id = wp_insert_attachment(
				[
					'post_mime_type' => (string) $moved['type'],
					'post_title'     => sanitize_file_name( pathinfo( (string) $moved['file'], PATHINFO_FILENAME ) ),
					'post_content'   => '',
					'post_status'    => $approved ? 'inherit' : 'private',
					'post_parent'    => $product_id,
				],
				(string) $moved['file'],
				$product_id
			);
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( (int) $id, self::META_COMMENT, $comment_id );
			if ( str_starts_with( (string) $moved['type'], 'image/' ) ) {
				self::strip_exif( (string) $moved['file'], (string) $moved['type'] );
				wp_update_attachment_metadata( (int) $id, wp_generate_attachment_metadata( (int) $id, (string) $moved['file'] ) );
			}
			$ids[] = (int) $id;
		}
		return $ids;
	}

	/** Re-save the original through the image editor, which drops EXIF and other metadata (including GPS). */
	public static function strip_exif( string $path, string $mime ): void {
		if ( 'image/jpeg' !== $mime && 'image/webp' !== $mime ) {
			return;
		}
		$editor = wp_get_image_editor( $path );
		if ( is_wp_error( $editor ) ) {
			return;
		}
		$editor->save( $path, $mime );
	}

	/** Keep attachment visibility in step with the review status. */
	public static function sync_status( int $comment_id, bool $approved ): void {
		foreach ( self::ids( $comment_id ) as $id ) {
			if ( (int) get_post_meta( $id, self::META_COMMENT, true ) !== $comment_id ) {
				continue;
			}
			wp_update_post( [ 'ID' => $id, 'post_status' => $approved ? 'inherit' : 'private' ] );
		}
	}

	public static function delete_for_comment( int $comment_id ): void {
		foreach ( self::ids( $comment_id ) as $id ) {
			if ( (int) get_post_meta( $id, self::META_COMMENT, true ) === $comment_id ) {
				wp_delete_attachment( $id, true );
			}
		}
	}

	/** @return int[] */
	public static function ids( int $comment_id ): array {
		$ids = get_comment_meta( $comment_id, '_wg_media', true );
		return array_values( array_filter( array_map( 'intval', is_array( $ids ) ? $ids : [] ) ) );
	}

	/**
	 * Public items for a review, only for attachments that belong to it and are visible.
	 *
	 * @return array<int, array{id: int, type: string, url: string, thumb: string, mime: string}>
	 */
	public static function items( int $comment_id, bool $include_private = false ): array {
		$out = [];
		foreach ( self::ids( $comment_id ) as $id ) {
			$post = get_post( $id );
			if ( ! $post || 'attachment' !== $post->post_type || (int) get_post_meta( $id, self::META_COMMENT, true ) !== $comment_id ) {
				continue;
			}
			if ( 'private' === $post->post_status && ! $include_private ) {
				continue;
			}
			$mime  = (string) $post->post_mime_type;
			$video = str_starts_with( $mime, 'video/' );
			$url   = (string) wp_get_attachment_url( $id );
			$thumb = $video ? '' : (string) wp_get_attachment_image_url( $id, 'thumbnail' );
			$out[] = [ 'id' => $id, 'type' => $video ? 'video' : 'image', 'url' => $url, 'thumb' => $thumb ?: $url, 'mime' => $mime ];
		}
		return $out;
	}
}
