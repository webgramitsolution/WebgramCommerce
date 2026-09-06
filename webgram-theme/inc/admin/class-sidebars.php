<?php
/**
 * Webgram > Sidebars: custom widget areas, per context assignment (blog, pages, shop, product) and mobile behaviour.
 * Custom areas live in the option webgram_custom_sidebars; assignments are theme settings (sidebar_* keys).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Sidebars {

	public const SLUG   = 'webgram-sidebars';
	public const OPTION = 'webgram_custom_sidebars';
	public const MAX    = 20;

	public static function init(): void {
		add_action( 'widgets_init', [ self::class, 'register' ], 20 );
		add_action( 'admin_menu', [ self::class, 'menu' ], 100 );
		add_action( 'admin_post_webgram_sidebar_add', [ self::class, 'add' ] );
		add_action( 'admin_post_webgram_sidebar_delete', [ self::class, 'delete' ] );
	}

	/** @return array<int, array{id: string, name: string}> */
	public static function custom(): array {
		$out = [];
		foreach ( (array) get_option( self::OPTION, [] ) as $row ) {
			if ( is_array( $row ) && ! empty( $row['id'] ) && ! empty( $row['name'] ) ) {
				$out[] = [ 'id' => sanitize_key( (string) $row['id'] ), 'name' => sanitize_text_field( (string) $row['name'] ) ];
			}
		}
		return $out;
	}

	/** Built in areas that can be assigned to a context. */
	public static function builtin(): array {
		return [
			'sidebar-blog'    => __( 'Blog sidebar', 'webgram' ),
			'sidebar-shop'    => __( 'Shop sidebar', 'webgram' ),
			'sidebar-product' => __( 'Product sidebar', 'webgram' ),
			'sidebar-page'    => __( 'Page sidebar', 'webgram' ),
		];
	}

	/** @return array<string, string> id => name (built in plus custom). */
	public static function choices(): array {
		$choices = self::builtin();
		foreach ( self::custom() as $row ) {
			$choices[ $row['id'] ] = $row['name'];
		}
		return $choices;
	}

	/** Turns a name into a unique sidebar id. Pure, covered by the harness. */
	public static function make_id( string $name, array $taken = [] ): string {
		$base = 'sidebar-' . trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( remove_accents( $name ) ) ), '-' );
		$base = '' === $base || 'sidebar-' === $base ? 'sidebar-custom' : substr( $base, 0, 40 );
		$id   = $base;
		$i    = 2;
		while ( in_array( $id, $taken, true ) ) {
			$id = $base . '-' . $i;
			++$i;
		}
		return $id;
	}

	public static function register(): void {
		$args = [
			'before_widget' => '<div id="%1$s" class="wg-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="wg-widget__title">',
			'after_title'   => '</h3>',
		];
		register_sidebar( [ 'name' => __( 'Product sidebar', 'webgram' ), 'id' => 'sidebar-product', 'description' => __( 'Shown next to the product page when the Single product tab uses a sidebar layout.', 'webgram' ) ] + $args );
		register_sidebar( [ 'name' => __( 'Page sidebar', 'webgram' ), 'id' => 'sidebar-page', 'description' => __( 'Default sidebar for pages that use a sidebar layout.', 'webgram' ) ] + $args );
		foreach ( self::custom() as $row ) {
			register_sidebar( [ 'name' => $row['name'], 'id' => $row['id'], 'description' => __( 'Custom sidebar created under Webgram > Sidebars.', 'webgram' ) ] + $args );
		}
	}

	/**
	 * Sidebar id for a context (blog, page, shop, product), honouring the per post override from the Webgram options box.
	 */
	public static function for_context( string $context ): string {
		$default = [ 'blog' => 'sidebar-blog', 'page' => 'sidebar-page', 'shop' => 'sidebar-shop', 'product' => 'sidebar-product' ][ $context ] ?? 'sidebar-blog';
		$id      = '';
		if ( is_singular() ) {
			$id = (string) get_post_meta( get_the_ID(), '_webgram_sidebar', true );
		}
		if ( '' === $id ) {
			$id = (string) webgram_option( 'sidebar_' . $context );
		}
		$choices = self::choices();
		if ( ! isset( $choices[ $id ] ) ) {
			$id = $default;
		}
		return (string) apply_filters( 'webgram/sidebar/id', $id, $context );
	}

	public static function menu(): void {
		add_submenu_page( Webgram_Settings_Page::MENU, __( 'Sidebars', 'webgram' ), __( 'Sidebars', 'webgram' ), Webgram_Settings_Page::CAP, self::SLUG, [ self::class, 'render' ] );
	}

	public static function render(): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			return;
		}
		$notice = isset( $_GET['wg_notice'] ) ? sanitize_key( wp_unslash( $_GET['wg_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap wg-admin">
			<div class="wg-admin__bar"><h1><?php echo esc_html( Webgram_Settings_Page::brand() ); ?> <span><?php esc_html_e( 'Sidebars', 'webgram' ); ?></span></h1></div>
			<?php if ( 'added' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sidebar created. Add widgets to it under Appearance > Widgets.', 'webgram' ); ?></p></div>
			<?php elseif ( 'deleted' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sidebar removed.', 'webgram' ); ?></p></div>
			<?php elseif ( 'limit' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sprintf( /* translators: %d: maximum. */ __( 'You can create up to %d custom sidebars.', 'webgram' ), self::MAX ) ); ?></p></div>
			<?php endif; ?>
			<div class="wg-admin__cards">
				<div class="wg-admin__card">
					<h2><?php esc_html_e( 'Custom sidebars', 'webgram' ); ?></h2>
					<p><?php esc_html_e( 'Create widget areas, fill them under Appearance > Widgets, then assign them below or per page in the Webgram options box.', 'webgram' ); ?></p>
					<table class="widefat striped" style="max-width:640px;margin-bottom:16px">
						<thead><tr><th><?php esc_html_e( 'Name', 'webgram' ); ?></th><th><?php esc_html_e( 'ID', 'webgram' ); ?></th><th></th></tr></thead>
						<tbody>
						<?php foreach ( self::builtin() as $id => $name ) : ?>
							<tr><td><?php echo esc_html( $name ); ?></td><td><code><?php echo esc_html( $id ); ?></code></td><td><em><?php esc_html_e( 'built in', 'webgram' ); ?></em></td></tr>
						<?php endforeach; ?>
						<?php foreach ( self::custom() as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['name'] ); ?></td>
								<td><code><?php echo esc_html( $row['id'] ); ?></code></td>
								<td><a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'webgram_sidebar_delete', 'id' => $row['id'] ], admin_url( 'admin-post.php' ) ), 'webgram_sidebar_delete_' . $row['id'] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Remove this sidebar? Its widgets become inactive.', 'webgram' ) ); ?>');"><?php esc_html_e( 'Remove', 'webgram' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;align-items:center">
						<input type="hidden" name="action" value="webgram_sidebar_add">
						<?php wp_nonce_field( 'webgram_sidebar_add' ); ?>
						<label class="screen-reader-text" for="wg-sidebar-name"><?php esc_html_e( 'Sidebar name', 'webgram' ); ?></label>
						<input type="text" id="wg-sidebar-name" name="name" class="regular-text" placeholder="<?php esc_attr_e( 'Sidebar name', 'webgram' ); ?>" required>
						<?php submit_button( __( 'Create sidebar', 'webgram' ), 'primary', 'submit', false ); ?>
					</form>
				</div>
				<div class="wg-admin__card">
					<h2><?php esc_html_e( 'Assignments and behaviour', 'webgram' ); ?></h2>
					<p><?php esc_html_e( 'Which area shows in each context, its position and how it behaves on mobile.', 'webgram' ); ?></p>
					<p><a class="button" href="<?php echo esc_url( add_query_arg( [ 'page' => Webgram_Settings_Page::MENU, 'tab' => 'layout' ], admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Open Layout settings', 'webgram' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><?php esc_html_e( 'Manage widgets', 'webgram' ); ?></a></p>
				</div>
			</div>
		</div>
		<?php
	}

	public static function add(): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		check_admin_referer( 'webgram_sidebar_add' );
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$rows = self::custom();
		if ( count( $rows ) >= self::MAX ) {
			self::back( 'limit' );
		}
		if ( '' !== $name ) {
			$rows[] = [ 'id' => self::make_id( $name, array_merge( array_keys( self::builtin() ), array_column( $rows, 'id' ) ) ), 'name' => $name ];
			update_option( self::OPTION, $rows, false );
		}
		self::back( 'added' );
	}

	public static function delete(): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		$id = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'webgram_sidebar_delete_' . $id );
		update_option( self::OPTION, array_values( array_filter( self::custom(), static fn( array $row ): bool => $row['id'] !== $id ) ), false );
		self::back( 'deleted' );
	}

	private static function back( string $notice ): void {
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'wg_notice' => $notice ], admin_url( 'admin.php' ) ) );
		exit;
	}
}

Webgram_Sidebars::init();
