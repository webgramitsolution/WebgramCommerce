<?php
/**
 * Webgram > Demo import: one click import of the demo store.
 *
 * Imports theme settings and builder presets, placeholder images (original, CC0), sample products through
 * WooCommerce's own CSV importer, blog posts, pages (front page built from demo/homepage-gutenberg.html),
 * menus and footer widgets. Webgram Core listens to `webgram/demo/import` to add its own demo content
 * (slider, testimonials, coupons, wishlist and compare pages) and fills `slider_id` through `webgram/demo/context`.
 *
 * Each step is idempotent: existing items are matched by slug, SKU or title and reused.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Demo_Importer {

	public const SLUG   = 'webgram-demo';
	public const ACTION = 'webgram_demo_import';
	public const OPTION = 'webgram_demo_import';
	public const STEPS  = [ 'settings', 'images', 'products', 'posts', 'core', 'pages', 'menus', 'widgets' ];

	/** @var array<string, int> image file name (without extension) to attachment id */
	private static array $images = [];

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ], 100 );
		add_action( 'admin_post_' . self::ACTION, [ self::class, 'handle' ] );
	}

	public static function menu(): void {
		add_submenu_page( Webgram_Settings_Page::MENU, __( 'Demo import', 'webgram' ), __( 'Demo import', 'webgram' ), 'manage_options', self::SLUG, [ self::class, 'render' ] );
	}

	public static function dir(): string {
		return WEBGRAM_DIR . '/demo';
	}

	public static function step_labels(): array {
		return [
			'settings' => __( 'Theme settings, header and footer presets', 'webgram' ),
			'images'   => __( 'Placeholder images', 'webgram' ),
			'products' => __( 'Sample products and categories (needs WooCommerce)', 'webgram' ),
			'posts'    => __( 'Blog posts', 'webgram' ),
			'core'     => __( 'Webgram Core content: slider, testimonials, coupons, wishlist and compare pages (needs Webgram Core)', 'webgram' ),
			'pages'    => __( 'Pages: Home, Blog, Help, Track Order, Bulk Order, About, Contact', 'webgram' ),
			'menus'    => __( 'Menus assigned to all locations', 'webgram' ),
			'widgets'  => __( 'Footer widgets', 'webgram' ),
		];
	}

	/**
	 * Filters requested steps to known ones and keeps the canonical order. Pure, covered by the harness.
	 *
	 * @param string[] $requested
	 * @return string[]
	 */
	public static function normalize_steps( array $requested ): array {
		$requested = array_map( 'sanitize_key', array_map( 'strval', $requested ) );
		return array_values( array_filter( self::STEPS, static fn( string $s ) => in_array( $s, $requested, true ) ) );
	}

	/**
	 * Injects the demo slider id into the homepage block markup. Pure, covered by the harness.
	 */
	public static function inject_slider_id( string $content, int $slider_id ): string {
		if ( $slider_id <= 0 ) {
			return $content;
		}
		return (string) preg_replace( '/"slider_id":\d+/', '"slider_id":' . $slider_id, $content, 1 );
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$done   = (array) get_option( self::OPTION, [] );
		$report = get_transient( 'webgram_demo_report' );
		delete_transient( 'webgram_demo_report' );
		?>
		<div class="wrap wg-admin">
			<div class="wg-admin__bar"><h1><?php echo esc_html( Webgram_Settings_Page::brand() ); ?> <span><?php esc_html_e( 'Demo import', 'webgram' ); ?></span></h1></div>
			<?php if ( is_array( $report ) ) : ?>
				<div class="notice notice-<?php echo empty( $report['errors'] ) ? 'success' : 'warning'; ?> is-dismissible">
					<p><?php esc_html_e( 'Import finished.', 'webgram' ); ?></p>
					<ul style="margin-left:1.5em;list-style:disc">
						<?php foreach ( (array) $report['lines'] as $line ) : ?>
							<li><?php echo esc_html( (string) $line ); ?></li>
						<?php endforeach; ?>
						<?php foreach ( (array) $report['errors'] as $line ) : ?>
							<li style="color:#b32d2e"><?php echo esc_html( (string) $line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<div class="wg-admin__card" style="max-width:760px">
				<p><?php esc_html_e( 'Imports the demo store shown in the theme preview. Existing content is never deleted. Run it on a fresh site; on a live store import only the parts you need.', 'webgram' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
					<?php wp_nonce_field( self::ACTION ); ?>
					<?php foreach ( self::step_labels() as $step => $label ) : ?>
						<?php
						$available = ! ( 'products' === $step && ! class_exists( 'WooCommerce' ) ) && ! ( 'core' === $step && ! webgram_has_core() );
						?>
						<p>
							<label>
								<input type="checkbox" name="steps[]" value="<?php echo esc_attr( $step ); ?>" <?php checked( $available ); ?> <?php disabled( ! $available ); ?>>
								<?php echo esc_html( $label ); ?>
								<?php if ( ! empty( $done[ $step ] ) ) : ?>
									<em style="color:#1e7e34">(<?php esc_html_e( 'imported', 'webgram' ); ?>)</em>
								<?php endif; ?>
							</label>
						</p>
					<?php endforeach; ?>
					<?php submit_button( __( 'Import demo content', 'webgram' ) ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	public static function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		check_admin_referer( self::ACTION );
		$steps = self::normalize_steps( isset( $_POST['steps'] ) ? (array) wp_unslash( $_POST['steps'] ) : [] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		set_time_limit( 300 );
		$report = self::run( $steps );
		set_transient( 'webgram_demo_report', $report, MINUTE_IN_SECONDS * 5 );
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * @param string[] $steps
	 * @return array{lines: string[], errors: string[]}
	 */
	public static function run( array $steps ): array {
		$report  = [ 'lines' => [], 'errors' => [] ];
		$context = [ 'images' => [], 'products' => [], 'slider_id' => 0, 'pages' => [] ];
		$done    = (array) get_option( self::OPTION, [] );

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		foreach ( $steps as $step ) {
			try {
				$method  = 'import_' . $step;
				$message = self::$method( $context );
				if ( $message ) {
					$report['lines'][] = $message;
				}
				$done[ $step ] = time();
			} catch ( \Throwable $e ) {
				$report['errors'][] = sprintf( '%s: %s', $step, $e->getMessage() );
			}
		}
		update_option( self::OPTION, $done, false );
		flush_rewrite_rules();
		return $report;
	}

	private static function import_settings( array &$context ): string {
		$file = self::dir() . '/theme-settings.json';
		$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $data ) ) {
			throw new \RuntimeException( esc_html__( 'theme-settings.json is missing or invalid.', 'webgram' ) );
		}
		$applied = Webgram_Import_Export::apply_import( array_intersect_key( $data, [ 'theme_settings' => 1 ] ) );

		$header = webgram_header_presets()[ $data['header_preset'] ?? '' ]['layout'] ?? null;
		if ( is_array( $header ) ) {
			Webgram_Header_Builder::instance()->save( Webgram_Header_Builder::instance()->sanitize( $header ) );
			$applied[] = 'header';
		}
		$footer = webgram_footer_presets()[ $data['footer_preset'] ?? '' ]['layout'] ?? null;
		if ( is_array( $footer ) ) {
			Webgram_Footer_Builder::instance()->save( Webgram_Footer_Builder::instance()->sanitize( $footer ) );
			$applied[] = 'footer';
		}
		return sprintf( /* translators: %s: comma separated list. */ __( 'Settings applied: %s', 'webgram' ), implode( ', ', $applied ) );
	}

	private static function import_images( array &$context ): string {
		$count = 0;
		foreach ( glob( self::dir() . '/images/*.png' ) ?: [] as $path ) {
			$name = basename( $path, '.png' );
			$id   = self::image( $name );
			if ( $id ) {
				++$count;
			}
		}
		$context['images'] = self::$images;
		return sprintf( /* translators: %d: number of images. */ __( '%d placeholder images available in the media library.', 'webgram' ), $count );
	}

	/**
	 * Returns the attachment id for a demo image, sideloading it once. Matched by the `_webgram_demo` meta.
	 */
	public static function image( string $name ): int {
		if ( isset( self::$images[ $name ] ) ) {
			return self::$images[ $name ];
		}
		$existing = get_posts( [ 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_webgram_demo', 'meta_value' => $name ] ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $existing ) {
			self::$images[ $name ] = (int) $existing[0];
			return self::$images[ $name ];
		}
		$path = self::dir() . '/images/' . sanitize_file_name( $name ) . '.png';
		if ( ! is_readable( $path ) ) {
			return 0;
		}
		$tmp = wp_tempnam( $name . '.png' );
		copy( $path, $tmp );
		$id = media_handle_sideload( [ 'name' => $name . '.png', 'tmp_name' => $tmp ], 0, ucwords( str_replace( '-', ' ', $name ) ) );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			return 0;
		}
		update_post_meta( (int) $id, '_webgram_demo', $name );
		update_post_meta( (int) $id, '_wp_attachment_image_alt', ucwords( str_replace( '-', ' ', $name ) ) );
		self::$images[ $name ] = (int) $id;
		return (int) $id;
	}

	private static function import_products( array &$context ): string {
		if ( ! class_exists( 'WooCommerce' ) ) {
			throw new \RuntimeException( esc_html__( 'WooCommerce is not active.', 'webgram' ) );
		}
		$file = self::dir() . '/products.csv';
		if ( ! is_readable( $file ) ) {
			throw new \RuntimeException( esc_html__( 'products.csv is missing.', 'webgram' ) );
		}
		// Product images are referenced by file name; sideload them first so the importer finds them in the library.
		foreach ( glob( self::dir() . '/images/product-*.png' ) ?: [] as $path ) {
			self::image( basename( $path, '.png' ) );
		}
		if ( ! class_exists( 'WC_Product_CSV_Importer' ) ) {
			require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
		}
		$handle  = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$headers = $handle ? (array) fgetcsv( $handle ) : [];
		if ( $handle ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
		$headers  = array_map( 'strval', $headers );
		$importer = new WC_Product_CSV_Importer( $file, [ 'mapping' => array_combine( $headers, $headers ), 'parse' => true, 'update_existing' => true, 'prevent_timeouts' => false ] );
		$result   = $importer->import();

		$context['products'] = array_map( 'intval', array_merge( array_column( (array) $result['imported'], null ), array_column( (array) $result['updated'], null ) ) );
		foreach ( (array) $result['failed'] as $error ) {
			if ( is_wp_error( $error ) ) {
				throw new \RuntimeException( esc_html( $error->get_error_message() ) );
			}
		}
		self::category_images();
		return sprintf( /* translators: 1: imported count, 2: updated count. */ __( 'Products imported: %1$d new, %2$d updated.', 'webgram' ), count( (array) $result['imported'] ), count( (array) $result['updated'] ) );
	}

	private static function category_images(): void {
		$map = [ 'pooja-essentials' => 'cat-pooja', 'home-decor' => 'cat-decor', 'kitchen' => 'cat-kitchen', 'gifting' => 'cat-gifting' ];
		foreach ( $map as $slug => $image ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			$id   = self::image( $image );
			if ( $term && $id && ! get_term_meta( $term->term_id, 'thumbnail_id', true ) ) {
				update_term_meta( $term->term_id, 'thumbnail_id', $id );
			}
		}
	}

	private static function import_posts( array &$context ): string {
		$file  = self::dir() . '/posts.json';
		$posts = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $posts ) ) {
			throw new \RuntimeException( esc_html__( 'posts.json is missing or invalid.', 'webgram' ) );
		}
		$count = 0;
		foreach ( $posts as $post ) {
			$title = sanitize_text_field( (string) ( $post['title'] ?? '' ) );
			if ( '' === $title || self::find_by_title( $title, 'post' ) ) {
				continue;
			}
			$term = term_exists( (string) ( $post['category'] ?? '' ), 'category' ) ?: wp_insert_term( sanitize_text_field( (string) ( $post['category'] ?? '' ) ), 'category' );
			$id   = wp_insert_post(
				[
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_title'    => $title,
					'post_excerpt'  => sanitize_text_field( (string) ( $post['excerpt'] ?? '' ) ),
					'post_content'  => wp_kses_post( (string) ( $post['content'] ?? '' ) ),
					'post_author'   => get_current_user_id(),
					'post_category' => is_array( $term ) && ! is_wp_error( $term ) ? [ (int) $term['term_id'] ] : [],
				],
				true
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			$image = self::image( basename( (string) ( $post['image'] ?? '' ), '.png' ) );
			if ( $image ) {
				set_post_thumbnail( $id, $image );
			}
			++$count;
		}
		return sprintf( /* translators: %d: number of posts. */ __( '%d blog posts created.', 'webgram' ), $count );
	}

	private static function import_core( array &$context ): string {
		if ( ! webgram_has_core() ) {
			throw new \RuntimeException( esc_html__( 'Webgram Core is not active.', 'webgram' ) );
		}
		$context['images'] = self::$images;
		if ( ! $context['products'] && class_exists( 'WooCommerce' ) ) {
			$context['products'] = wc_get_products( [ 'limit' => 12, 'return' => 'ids', 'status' => 'publish' ] );
		}
		do_action( 'webgram/demo/import', $context, [ self::class, 'image' ] );
		$context = (array) apply_filters( 'webgram/demo/context', $context );
		return (string) ( $context['core_message'] ?? __( 'Webgram Core demo content processed.', 'webgram' ) );
	}

	private static function import_pages( array &$context ): string {
		$home_content = (string) file_get_contents( self::dir() . '/homepage-gutenberg.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$home_content = self::inject_slider_id( $home_content, (int) ( $context['slider_id'] ?? 0 ) );

		$pages = [
			'home'        => [ __( 'Home', 'webgram' ), $home_content, '' ],
			'blog'        => [ __( 'Blog', 'webgram' ), '', '' ],
			'help'        => [ __( 'Help', 'webgram' ), '', 'page-templates/template-help.php' ],
			'track-order' => [ __( 'Track Order', 'webgram' ), '', 'page-templates/template-track-order.php' ],
			'bulk-order'  => [ __( 'Bulk Order', 'webgram' ), '', 'page-templates/template-bulk-order.php' ],
			'about'       => [ __( 'About', 'webgram' ), '<!-- wp:webgram/about {"title":"About Us","subtitle":"Crafted with care, delivered with love","text":"We started with a simple idea: everyday essentials should feel special."} /-->', '' ],
			'contact'     => [ __( 'Contact', 'webgram' ), '<!-- wp:paragraph --><p>Write to support@example.com or message us on WhatsApp at +91 98765 43210. We answer within one working day.</p><!-- /wp:paragraph -->', '' ],
		];
		foreach ( $pages as $slug => [ $title, $content, $template ] ) {
			$page = get_page_by_path( $slug );
			$id   = $page ? (int) $page->ID : (int) wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content, 'post_author' => get_current_user_id() ] );
			if ( $id && $template ) {
				update_post_meta( $id, '_wp_page_template', $template );
			}
			$context['pages'][ $slug ] = $id;
		}
		if ( ! empty( $context['pages']['about'] ) ) {
			$about = self::image( 'about' );
			if ( $about ) {
				set_post_thumbnail( $context['pages']['about'], $about );
			}
		}
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $context['pages']['home'] );
		update_option( 'page_for_posts', (int) $context['pages']['blog'] );
		return sprintf( /* translators: %d: number of pages. */ __( '%d pages ready. Home set as the front page.', 'webgram' ), count( $pages ) );
	}

	private static function import_menus( array &$context ): string {
		$pages = $context['pages'] ?? [];
		$shop  = class_exists( 'WooCommerce' ) ? (int) wc_get_page_id( 'shop' ) : 0;

		$primary = self::nav_menu( __( 'Primary', 'webgram' ), 'webgram-primary' );
		if ( $primary && ! wp_get_nav_menu_items( $primary ) ) {
			$shop_item = $shop > 0 ? self::menu_item( $primary, [ 'menu-item-object-id' => $shop, 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-title' => __( 'Shop', 'webgram' ) ] ) : 0;
			if ( $shop_item ) {
				foreach ( [ 'pooja-essentials', 'home-decor', 'kitchen', 'gifting' ] as $slug ) {
					$term = get_term_by( 'slug', $slug, 'product_cat' );
					if ( $term ) {
						self::menu_item( $primary, [ 'menu-item-object-id' => $term->term_id, 'menu-item-object' => 'product_cat', 'menu-item-type' => 'taxonomy', 'menu-item-parent-id' => $shop_item ] );
					}
				}
			}
			foreach ( [ 'about', 'blog', 'help', 'contact' ] as $slug ) {
				if ( ! empty( $pages[ $slug ] ) ) {
					self::menu_item( $primary, [ 'menu-item-object-id' => (int) $pages[ $slug ], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type' ] );
				}
			}
		}
		$footer = self::nav_menu( __( 'Footer', 'webgram' ), 'webgram-footer' );
		if ( $footer && ! wp_get_nav_menu_items( $footer ) ) {
			foreach ( [ 'about', 'help', 'track-order', 'bulk-order', 'contact' ] as $slug ) {
				if ( ! empty( $pages[ $slug ] ) ) {
					self::menu_item( $footer, [ 'menu-item-object-id' => (int) $pages[ $slug ], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type' ] );
				}
			}
		}
		$locations = (array) get_theme_mod( 'nav_menu_locations', [] );
		$locations = array_merge( $locations, [ 'primary' => $primary, 'secondary' => $primary, 'mobile' => $primary, 'footer' => $footer ] );
		set_theme_mod( 'nav_menu_locations', $locations );
		return __( 'Primary and footer menus assigned.', 'webgram' );
	}

	private static function nav_menu( string $name, string $slug ): int {
		$existing = wp_get_nav_menu_object( $slug ) ?: wp_get_nav_menu_object( $name );
		if ( $existing ) {
			return (int) $existing->term_id;
		}
		$id = wp_create_nav_menu( $name );
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	private static function menu_item( int $menu, array $args ): int {
		$id = wp_update_nav_menu_item( $menu, 0, $args + [ 'menu-item-status' => 'publish' ] );
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	private static function import_widgets( array &$context ): string {
		$file = self::dir() . '/widgets.json';
		$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $data ) ) {
			throw new \RuntimeException( esc_html__( 'widgets.json is missing or invalid.', 'webgram' ) );
		}
		$sidebars = (array) get_option( 'sidebars_widgets', [] );
		$blocks   = (array) get_option( 'widget_block', [] );
		$next     = max( 1, ...array_map( 'intval', array_filter( array_keys( $blocks ), 'is_int' ) ) ) + 1;
		$count    = 0;
		foreach ( $data as $area => $widgets ) {
			$area = sanitize_key( (string) $area );
			if ( ! empty( $sidebars[ $area ] ) ) {
				continue;
			}
			$sidebars[ $area ] = [];
			foreach ( (array) $widgets as $widget ) {
				if ( 'block' !== ( $widget['type'] ?? '' ) ) {
					continue;
				}
				$blocks[ $next ]     = [ 'content' => wp_kses_post( (string) ( $widget['content'] ?? '' ) ) ];
				$sidebars[ $area ][] = 'block-' . $next;
				++$next;
				++$count;
			}
		}
		$blocks['_multiwidget'] = 1;
		update_option( 'widget_block', $blocks );
		update_option( 'sidebars_widgets', $sidebars );
		return sprintf( /* translators: %d: number of widgets. */ __( '%d footer widgets added.', 'webgram' ), $count );
	}

	private static function find_by_title( string $title, string $type ): int {
		$found = get_posts( [ 'post_type' => $type, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'title' => $title ] );
		return $found ? (int) $found[0] : 0;
	}
}

Webgram_Demo_Importer::init();
