<?php
namespace Webgram\Core\Modules\SiteTools;

use Webgram\Core\Admin\ModulesPage;

defined( 'ABSPATH' ) || exit;

/** Optional portfolio post type (off by default). The theme renders it with its generic archive and single templates. */
final class Portfolio {

	public const POST_TYPE = 'wg_portfolio';
	public const TAXONOMY  = 'wg_portfolio_cat';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'register_types' ] );
		add_action( 'pre_get_posts', [ $this, 'per_page' ] );
	}

	public function register_types(): void {
		if ( ! $this->module->settings()->get( 'portfolio_enabled', false ) ) {
			return;
		}
		$slug = sanitize_title( (string) $this->module->settings()->get( 'portfolio_slug', 'portfolio' ) ) ?: 'portfolio';
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			[
				'labels'       => [ 'name' => __( 'Portfolio categories', 'webgram-core' ), 'singular_name' => __( 'Portfolio category', 'webgram-core' ) ],
				'hierarchical' => true,
				'public'       => true,
				'show_in_rest' => true,
				'rewrite'      => [ 'slug' => $slug . '-category' ],
			]
		);
		register_post_type(
			self::POST_TYPE,
			[
				'labels'       => [ 'name' => __( 'Portfolio', 'webgram-core' ), 'singular_name' => __( 'Portfolio item', 'webgram-core' ), 'add_new_item' => __( 'Add portfolio item', 'webgram-core' ) ],
				'public'       => true,
				'show_in_rest' => true,
				'show_in_menu' => ModulesPage::parent_slug(),
				'menu_icon'    => 'dashicons-portfolio',
				'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
				'has_archive'  => true,
				'rewrite'      => [ 'slug' => $slug ],
				'taxonomies'   => [ self::TAXONOMY ],
			]
		);
	}

	public function per_page( \WP_Query $query ): void {
		if ( ! is_admin() && $query->is_main_query() && ( $query->is_post_type_archive( self::POST_TYPE ) || $query->is_tax( self::TAXONOMY ) ) ) {
			$query->set( 'posts_per_page', (int) $this->module->settings()->get( 'portfolio_per_page', 12 ) );
		}
	}
}
