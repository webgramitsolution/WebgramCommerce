<?php
namespace Webgram\Core\Modules\SiteTools\Layouts;

use Webgram\Core\Admin\ModulesPage;
use Webgram\Core\Modules\SiteTools\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Layouts CPT wg_layout with metabox: type, priority, devices, login state and assignment conditions.
 */
final class PostType {

	public const POST_TYPE   = 'wg_layout';
	public const META_TYPE   = '_wg_layout_type';
	public const META_RULES  = '_wg_conditions';
	public const META_PRIO   = '_wg_priority';
	public const META_DEVICE = '_wg_devices';
	public const META_LOGIN  = '_wg_login_state';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'register_types' ] );
		add_action( 'add_meta_boxes', [ $this, 'metabox' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save' ], 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'column' ], 10, 2 );
	}

	/** @return array<string, string> */
	public static function types(): array {
		return (array) apply_filters(
			'webgram_core/layouts/types',
			[
				'shop'           => __( 'Shop archive', 'webgram-core' ),
				'single_product' => __( 'Single product', 'webgram-core' ),
				'cart'           => __( 'Cart', 'webgram-core' ),
				'checkout'       => __( 'Checkout', 'webgram-core' ),
				'thankyou'       => __( 'Thank you', 'webgram-core' ),
				'myaccount'      => __( 'My account', 'webgram-core' ),
				'404'            => __( '404 page', 'webgram-core' ),
				'blog_archive'   => __( 'Blog archive', 'webgram-core' ),
				'single_post'    => __( 'Single post', 'webgram-core' ),
				'header'         => __( 'Header', 'webgram-core' ),
				'footer'         => __( 'Footer', 'webgram-core' ),
			]
		);
	}

	public function register_types(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'          => __( 'Layouts', 'webgram-core' ),
					'singular_name' => __( 'Layout', 'webgram-core' ),
					'add_new_item'  => __( 'Add Layout', 'webgram-core' ),
					'edit_item'     => __( 'Edit Layout', 'webgram-core' ),
					'menu_name'     => __( 'Layouts', 'webgram-core' ),
				],
				'public'              => false,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => ModulesPage::parent_slug(),
				'show_in_rest'        => true,
				'supports'            => [ 'title', 'editor', 'revisions' ],
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
				'rewrite'             => false,
				'has_archive'         => false,
			]
		);
	}

	public function metabox(): void {
		add_meta_box( 'wg_layout_settings', __( 'Layout settings', 'webgram-core' ), [ $this, 'render_metabox' ], self::POST_TYPE, 'side', 'high' );
		add_meta_box( 'wg_layout_conditions', __( 'Display conditions', 'webgram-core' ), [ $this, 'render_conditions' ], self::POST_TYPE, 'normal', 'high' );
	}

	public function render_metabox( \WP_Post $post ): void {
		wp_nonce_field( 'wg_layout_save', 'wg_layout_nonce' );
		$type    = (string) get_post_meta( $post->ID, self::META_TYPE, true );
		$prio    = (int) get_post_meta( $post->ID, self::META_PRIO, true );
		$devices = (array) get_post_meta( $post->ID, self::META_DEVICE, true ) ?: [ 'desktop', 'tablet', 'mobile' ];
		$login   = (string) get_post_meta( $post->ID, self::META_LOGIN, true ) ?: 'any';
		?>
		<p><label for="wg_layout_type"><strong><?php esc_html_e( 'Replaces', 'webgram-core' ); ?></strong></label>
			<select id="wg_layout_type" name="wg_layout[type]" class="widefat">
				<?php foreach ( self::types() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select></p>
		<p><label for="wg_layout_prio"><strong><?php esc_html_e( 'Priority', 'webgram-core' ); ?></strong></label>
			<input type="number" id="wg_layout_prio" name="wg_layout[priority]" value="<?php echo esc_attr( (string) $prio ); ?>" class="widefat" min="0" max="999">
			<span class="description"><?php esc_html_e( 'Higher wins when several layouts match.', 'webgram-core' ); ?></span></p>
		<p><strong><?php esc_html_e( 'Devices', 'webgram-core' ); ?></strong><br>
			<?php foreach ( [ 'desktop' => __( 'Desktop', 'webgram-core' ), 'tablet' => __( 'Tablet', 'webgram-core' ), 'mobile' => __( 'Mobile', 'webgram-core' ) ] as $d => $label ) : ?>
				<label><input type="checkbox" name="wg_layout[devices][]" value="<?php echo esc_attr( $d ); ?>" <?php checked( in_array( $d, $devices, true ) ); ?>> <?php echo esc_html( $label ); ?></label><br>
			<?php endforeach; ?></p>
		<p><label for="wg_layout_login"><strong><?php esc_html_e( 'Visitors', 'webgram-core' ); ?></strong></label>
			<select id="wg_layout_login" name="wg_layout[login]" class="widefat">
				<option value="any" <?php selected( $login, 'any' ); ?>><?php esc_html_e( 'Everyone', 'webgram-core' ); ?></option>
				<option value="in" <?php selected( $login, 'in' ); ?>><?php esc_html_e( 'Logged in only', 'webgram-core' ); ?></option>
				<option value="out" <?php selected( $login, 'out' ); ?>><?php esc_html_e( 'Guests only', 'webgram-core' ); ?></option>
			</select></p>
		<?php
	}

	public function render_conditions( \WP_Post $post ): void {
		$rules = (array) get_post_meta( $post->ID, self::META_RULES, true );
		$rules = array_values( array_filter( $rules, 'is_array' ) );
		$rows  = max( 4, count( $rules ) + 2 );
		?>
		<p class="description"><?php esc_html_e( 'The layout applies when at least one "Include" rule matches and no "Exclude" rule matches. Values are comma separated ids or slugs; leave empty for "all".', 'webgram-core' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Rule', 'webgram-core' ); ?></th><th><?php esc_html_e( 'Target', 'webgram-core' ); ?></th><th><?php esc_html_e( 'Values', 'webgram-core' ); ?></th></tr></thead>
			<tbody>
			<?php for ( $i = 0; $i < $rows; $i++ ) : ?>
				<?php $rule = $rules[ $i ] ?? [ 'op' => 'include', 'type' => '', 'value' => '' ]; ?>
				<tr>
					<td><select name="wg_layout[rules][<?php echo (int) $i; ?>][op]">
						<option value="include" <?php selected( $rule['op'] ?? 'include', 'include' ); ?>><?php esc_html_e( 'Include', 'webgram-core' ); ?></option>
						<option value="exclude" <?php selected( $rule['op'] ?? '', 'exclude' ); ?>><?php esc_html_e( 'Exclude', 'webgram-core' ); ?></option>
					</select></td>
					<td><select name="wg_layout[rules][<?php echo (int) $i; ?>][type]">
						<option value=""><?php esc_html_e( 'Unused', 'webgram-core' ); ?></option>
						<?php foreach ( Conditions::types() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $rule['type'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></td>
					<td><input type="text" class="widefat" name="wg_layout[rules][<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( is_array( $rule['value'] ?? '' ) ? implode( ',', $rule['value'] ) : (string) ( $rule['value'] ?? '' ) ); ?>" placeholder="12, 34"></td>
				</tr>
			<?php endfor; ?>
			</tbody>
		</table>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['wg_layout_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wg_layout_nonce'] ) ), 'wg_layout_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		$raw = isset( $_POST['wg_layout'] ) && is_array( $_POST['wg_layout'] ) ? wp_unslash( $_POST['wg_layout'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$type = sanitize_key( (string) ( $raw['type'] ?? '' ) );
		update_post_meta( $post_id, self::META_TYPE, array_key_exists( $type, self::types() ) ? $type : '' );
		update_post_meta( $post_id, self::META_PRIO, max( 0, min( 999, (int) ( $raw['priority'] ?? 0 ) ) ) );
		update_post_meta( $post_id, self::META_DEVICE, array_values( array_intersect( array_map( 'sanitize_key', (array) ( $raw['devices'] ?? [] ) ), [ 'desktop', 'tablet', 'mobile' ] ) ) );
		update_post_meta( $post_id, self::META_LOGIN, in_array( $raw['login'] ?? 'any', [ 'any', 'in', 'out' ], true ) ? $raw['login'] : 'any' );
		update_post_meta( $post_id, self::META_RULES, Conditions::sanitize( (array) ( $raw['rules'] ?? [] ) ) );

		\webgram_core()->cache()->flush_group( 'layouts' );
	}

	public function columns( array $columns ): array {
		$columns['wg_type']  = __( 'Replaces', 'webgram-core' );
		$columns['wg_rules'] = __( 'Conditions', 'webgram-core' );
		return $columns;
	}

	public function column( string $column, int $post_id ): void {
		if ( 'wg_type' === $column ) {
			$type = (string) get_post_meta( $post_id, self::META_TYPE, true );
			echo esc_html( self::types()[ $type ] ?? '' );
		}
		if ( 'wg_rules' === $column ) {
			$rules = (array) get_post_meta( $post_id, self::META_RULES, true );
			$parts = [];
			foreach ( $rules as $rule ) {
				if ( ! is_array( $rule ) || empty( $rule['type'] ) ) {
					continue;
				}
				$parts[] = ( 'exclude' === ( $rule['op'] ?? '' ) ? '- ' : '+ ' ) . ( Conditions::types()[ $rule['type'] ] ?? $rule['type'] ) . ( ! empty( $rule['value'] ) ? ' (' . implode( ',', (array) $rule['value'] ) . ')' : '' );
			}
			echo esc_html( $parts ? implode( '; ', $parts ) : __( 'Not assigned', 'webgram-core' ) );
		}
	}
}
