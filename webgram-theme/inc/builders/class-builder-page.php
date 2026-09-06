<?php
/**
 * Webgram > Header Builder and Webgram > Footer Builder. One page class, two configurations. Drag-and-drop UI in
 * assets/admin/builder.js; element and row settings are server-rendered forms saved with the layout JSON.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Builder_Page {

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ], 10 );
		add_action( 'admin_post_webgram_save_header', [ self::class, 'save_header' ] );
		add_action( 'admin_post_webgram_save_footer', [ self::class, 'save_footer' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'assets' ] );
	}

	public static function menu(): void {
		add_submenu_page( Webgram_Settings_Page::MENU, __( 'Header Builder', 'webgram' ), __( 'Header Builder', 'webgram' ), Webgram_Settings_Page::CAP, 'webgram-header', [ self::class, 'render_header' ] );
		add_submenu_page( Webgram_Settings_Page::MENU, __( 'Footer Builder', 'webgram' ), __( 'Footer Builder', 'webgram' ), Webgram_Settings_Page::CAP, 'webgram-footer', [ self::class, 'render_footer' ] );
	}

	public static function assets( string $hook ): void {
		if ( str_contains( $hook, 'webgram-header' ) || str_contains( $hook, 'webgram-footer' ) ) {
			wp_enqueue_script( 'webgram-builder', WEBGRAM_URI . '/assets/admin/builder.js', [ 'webgram-admin' ], webgram_asset_version( 'admin/builder.js' ), true );
		}
	}

	public static function render_header(): void {
		$builder = Webgram_Header_Builder::instance();
		$layout  = $builder->layout();
		$presets = [];
		foreach ( webgram_header_presets() as $key => $preset ) {
			$presets[ $key ] = [ 'label' => $preset['label'], 'layout' => $preset['layout'] ];
		}
		$structure = [];
		foreach ( Webgram_Header_Builder::DEVICES as $device ) {
			foreach ( Webgram_Header_Builder::ROWS as $row ) {
				$structure[ $device ][ $row ] = Webgram_Header_Builder::AREAS;
			}
		}
		self::page(
			[
				'context'   => 'header',
				'title'     => __( 'Header Builder', 'webgram' ),
				'action'    => 'webgram_save_header',
				'builder'   => $builder,
				'layout'    => $layout,
				'presets'   => $presets,
				'structure' => $structure,
				'devices'   => [ 'desktop' => __( 'Desktop', 'webgram' ), 'mobile' => __( 'Mobile', 'webgram' ) ],
				'row_labels' => [ 'top' => __( 'Top bar', 'webgram' ), 'main' => __( 'Main row', 'webgram' ), 'bottom' => __( 'Bottom row', 'webgram' ) ],
				'area_labels' => [ 'left' => __( 'Left', 'webgram' ), 'center' => __( 'Center', 'webgram' ), 'right' => __( 'Right', 'webgram' ) ],
			]
		);
	}

	public static function render_footer(): void {
		$builder = Webgram_Footer_Builder::instance();
		$layout  = $builder->layout();
		$presets = [];
		foreach ( webgram_footer_presets() as $key => $preset ) {
			$presets[ $key ] = [ 'label' => $preset['label'], 'layout' => $preset['layout'] ];
		}
		$cols = [];
		for ( $i = 1; $i <= Webgram_Footer_Builder::MAX_COLUMNS; $i++ ) {
			$cols[] = 'col_' . $i;
		}
		$area_labels = [ 'left' => __( 'Left', 'webgram' ), 'center' => __( 'Center', 'webgram' ), 'right' => __( 'Right', 'webgram' ) ];
		for ( $i = 1; $i <= Webgram_Footer_Builder::MAX_COLUMNS; $i++ ) {
			/* translators: %d: column number */
			$area_labels[ 'col_' . $i ] = sprintf( __( 'Column %d', 'webgram' ), $i );
		}
		self::page(
			[
				'context'    => 'footer',
				'title'      => __( 'Footer Builder', 'webgram' ),
				'action'     => 'webgram_save_footer',
				'builder'    => $builder,
				'layout'     => $layout,
				'presets'    => $presets,
				'structure'  => [ 'desktop' => [ 'widgets' => $cols, 'bottom' => Webgram_Footer_Builder::BOTTOM_AREAS ] ],
				'devices'    => [ 'desktop' => __( 'All devices', 'webgram' ) ],
				'row_labels' => [ 'widgets' => __( 'Columns row', 'webgram' ), 'bottom' => __( 'Bottom row', 'webgram' ) ],
				'area_labels' => $area_labels,
			]
		);
	}

	/** Layout normalized into device => row => area => ids, plus row settings, for the JS. */
	private static function areas_for_js( array $cfg ): array {
		$layout = $cfg['layout'];
		$out    = [];
		if ( 'header' === $cfg['context'] ) {
			foreach ( $cfg['structure'] as $device => $rows ) {
				foreach ( $rows as $row => $areas ) {
					foreach ( $areas as $area ) {
						$out[ $device ][ $row ][ $area ] = $layout[ $device ][ $row ][ $area ] ?? [];
					}
				}
			}
		} else {
			foreach ( $cfg['structure']['desktop']['widgets'] as $col ) {
				$out['desktop']['widgets'][ $col ] = $layout['widgets']['areas'][ $col ] ?? [];
			}
			foreach ( $cfg['structure']['desktop']['bottom'] as $area ) {
				$out['desktop']['bottom'][ $area ] = $layout['bottom'][ $area ] ?? [];
			}
		}
		return $out;
	}

	private static function page( array $cfg ): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			return;
		}
		$builder  = $cfg['builder'];
		$layout   = $cfg['layout'];
		$elements = $builder->elements();
		$notice   = isset( $_GET['wg_notice'] ) ? sanitize_key( wp_unslash( $_GET['wg_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$palette  = [];
		foreach ( $elements as $id => $el ) {
			$palette[ $id ] = [ 'label' => $el->label(), 'icon' => $el->icon(), 'group' => $el->group(), 'available' => $el->is_available() ];
		}
		$js = [
			'context'   => $cfg['context'],
			'areas'     => self::areas_for_js( $cfg ),
			'elements'  => $palette,
			'presets'   => $cfg['presets'],
			'structure' => $cfg['structure'],
			'columns'   => 'footer' === $cfg['context'] ? (int) $layout['widgets']['columns'] : 0,
			'i18n'      => [ 'remove' => __( 'Remove', 'webgram' ), 'settings' => __( 'Settings', 'webgram' ), 'applyPreset' => __( 'Replace the current layout with this preset? Element settings are kept.', 'webgram' ) ],
		];
		?>
		<div class="wrap wg-admin wg-builder" data-wg-builder="<?php echo esc_attr( wp_json_encode( $js ) ); ?>">
			<div class="wg-admin__bar">
				<h1><?php echo esc_html( Webgram_Settings_Page::brand() ); ?> <span><?php echo esc_html( $cfg['title'] ); ?></span></h1>
				<div class="wg-admin__bar-actions">
					<label class="wg-builder__preset"><?php esc_html_e( 'Preset', 'webgram' ); ?>
						<select data-wg-preset>
							<option value=""><?php esc_html_e( 'Choose...', 'webgram' ); ?></option>
							<?php foreach ( $cfg['presets'] as $key => $preset ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $preset['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<button type="button" class="button" data-wg-apply-preset><?php esc_html_e( 'Apply preset', 'webgram' ); ?></button>
					<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site', 'webgram' ); ?></a>
				</div>
			</div>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Layout saved.', 'webgram' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-wg-form data-wg-builder-form>
				<input type="hidden" name="action" value="<?php echo esc_attr( $cfg['action'] ); ?>">
				<input type="hidden" name="layout_json" value="" data-wg-layout-json>
				<?php wp_nonce_field( $cfg['action'] ); ?>

				<div class="wg-builder__layout">
					<div class="wg-builder__canvas">
						<?php if ( count( $cfg['devices'] ) > 1 ) : ?>
							<div class="wg-builder__devices" role="tablist">
								<?php foreach ( $cfg['devices'] as $device => $label ) : ?>
									<button type="button" class="wg-builder__device<?php echo 'desktop' === $device ? ' is-active' : ''; ?>" data-device="<?php echo esc_attr( $device ); ?>"><?php echo webgram_icon( 'device-' . $device, '', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $label ); ?></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php foreach ( $cfg['structure'] as $device => $rows ) : ?>
							<div class="wg-builder__device-pane<?php echo 'desktop' === $device ? ' is-active' : ''; ?>" data-device-pane="<?php echo esc_attr( $device ); ?>">
								<?php foreach ( $rows as $row => $areas ) : ?>
									<?php
									$row_settings = 'header' === $cfg['context'] ? $layout[ $device ][ $row ]['settings'] : $layout[ $row ]['settings'];
									$row_enabled  = 'header' === $cfg['context'] ? ! empty( $row_settings['enabled'] ) : ! empty( $layout[ $row ]['enabled'] );
									$enabled_name = 'header' === $cfg['context'] ? "rows[$device][$row][enabled]" : "rows[$row][enabled]";
									?>
									<div class="wg-builder__row<?php echo $row_enabled ? '' : ' is-disabled'; ?>" data-row="<?php echo esc_attr( $row ); ?>">
										<div class="wg-builder__row-head">
											<label class="wg-switch wg-switch--sm"><input type="hidden" name="<?php echo esc_attr( $enabled_name ); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr( $enabled_name ); ?>" value="1" <?php checked( $row_enabled ); ?> data-wg-row-toggle><span class="wg-switch__track"></span></label>
											<strong><?php echo esc_html( $cfg['row_labels'][ $row ] ); ?></strong>
											<?php if ( 'widgets' === $row ) : ?>
												<label class="wg-builder__columns"><?php esc_html_e( 'Columns', 'webgram' ); ?>
													<select name="rows[widgets][columns]" data-wg-columns>
														<?php for ( $i = 1; $i <= Webgram_Footer_Builder::MAX_COLUMNS; $i++ ) : ?>
															<option value="<?php echo (int) $i; ?>" <?php selected( (int) $layout['widgets']['columns'], $i ); ?>><?php echo (int) $i; ?></option>
														<?php endfor; ?>
													</select>
												</label>
											<?php endif; ?>
											<button type="button" class="button-link wg-builder__row-settings" data-wg-open-panel="row-<?php echo esc_attr( $device . '-' . $row ); ?>"><?php echo webgram_icon( 'settings', '', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Row settings', 'webgram' ); ?></button>
										</div>
										<div class="wg-builder__areas wg-builder__areas--<?php echo esc_attr( (string) count( $areas ) ); ?>">
											<?php foreach ( $areas as $area ) : ?>
												<div class="wg-builder__area" data-area="<?php echo esc_attr( $area ); ?>" data-device="<?php echo esc_attr( $device ); ?>" data-row="<?php echo esc_attr( $row ); ?>">
													<span class="wg-builder__area-label"><?php echo esc_html( $cfg['area_labels'][ $area ] ?? $area ); ?></span>
												</div>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>

						<?php if ( 'header' === $cfg['context'] ) : ?>
							<p class="wg-builder__hint"><?php printf( /* translators: %s: placeholder value. */ esc_html__( 'Sticky rows, shrink and hide-on-scroll are configured under %s.', 'webgram' ), '<a href="' . esc_url( admin_url( 'admin.php?page=webgram&tab=sticky' ) ) . '">' . esc_html__( 'Theme Settings > Sticky navigation', 'webgram' ) . '</a>' ); ?> <?php printf( /* translators: %s: placeholder value. */ esc_html__( 'Mobile drawer and bottom navbar: %s.', 'webgram' ), '<a href="' . esc_url( admin_url( 'admin.php?page=webgram&tab=mobile_menu' ) ) . '">' . esc_html__( 'Mobile menu', 'webgram' ) . '</a>, <a href="' . esc_url( admin_url( 'admin.php?page=webgram&tab=mobile_navbar' ) ) . '">' . esc_html__( 'Mobile bottom navbar', 'webgram' ) . '</a>' ); ?></p>
						<?php endif; ?>
					</div>

					<aside class="wg-builder__side">
						<div class="wg-builder__palette" data-wg-palette>
							<h3><?php esc_html_e( 'Elements', 'webgram' ); ?></h3>
							<p class="description"><?php esc_html_e( 'Drag into a row. Click an element to edit its settings.', 'webgram' ); ?></p>
							<?php
							$groups = [ 'brand' => __( 'Brand', 'webgram' ), 'navigation' => __( 'Navigation', 'webgram' ), 'search' => __( 'Search', 'webgram' ), 'actions' => __( 'Actions', 'webgram' ), 'content' => __( 'Content', 'webgram' ) ];
							foreach ( $groups as $group => $group_label ) :
								$items = array_filter( $elements, static fn( Webgram_Element $e ) => $e->group() === $group );
								if ( ! $items ) {
									continue;
								}
								?>
								<div class="wg-builder__group"><h4><?php echo esc_html( $group_label ); ?></h4>
									<?php foreach ( $items as $id => $el ) : ?>
										<div class="wg-builder__chip<?php echo $el->is_available() ? '' : ' is-unavailable'; ?>" draggable="<?php echo $el->is_available() ? 'true' : 'false'; ?>" data-element="<?php echo esc_attr( $id ); ?>" title="<?php echo $el->is_available() ? '' : esc_attr__( 'Requires WooCommerce or a Webgram Core module', 'webgram' ); ?>">
											<?php echo webgram_icon( $el->icon(), '', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $el->label() ); ?></span>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endforeach; ?>
						</div>

						<div class="wg-builder__panels">
							<?php foreach ( $elements as $id => $el ) : ?>
								<div class="wg-builder__panel" data-panel="el-<?php echo esc_attr( $id ); ?>" hidden>
									<div class="wg-builder__panel-head"><h3><?php echo esc_html( $el->label() ); ?></h3><button type="button" class="button-link" data-wg-close-panel>&times;</button></div>
									<?php
									$fields = $el->settings_fields();
									if ( ! $fields ) {
										echo '<p class="description">' . esc_html__( 'This element has no settings.', 'webgram' ) . '</p>';
									}
									$values = $builder->element_settings( $id );
									foreach ( $fields as $fid => $field ) {
										$field['id'] = $fid;
										Webgram_Settings_Fields::render( $field, $values[ $fid ] ?? ( $field['default'] ?? '' ), 'elements[' . $id . ']' );
									}
									?>
								</div>
							<?php endforeach; ?>

							<?php foreach ( $cfg['structure'] as $device => $rows ) : ?>
								<?php foreach ( array_keys( $rows ) as $row ) : ?>
									<div class="wg-builder__panel" data-panel="row-<?php echo esc_attr( $device . '-' . $row ); ?>" hidden>
										<div class="wg-builder__panel-head"><h3><?php echo esc_html( $cfg['row_labels'][ $row ] ); ?> <?php echo count( $cfg['devices'] ) > 1 ? '(' . esc_html( $cfg['devices'][ $device ] ) . ')' : ''; ?></h3><button type="button" class="button-link" data-wg-close-panel>&times;</button></div>
										<?php
										$row_settings = 'header' === $cfg['context'] ? $layout[ $device ][ $row ]['settings'] : $layout[ $row ]['settings'];
										$prefix       = 'header' === $cfg['context'] ? "rows[$device][$row]" : "rows[$row][settings]";
										foreach ( $builder->row_fields( $row ) as $fid => $field ) {
											if ( 'enabled' === $fid ) {
												continue;
											}
											$field['id'] = $fid;
											Webgram_Settings_Fields::render( $field, $row_settings[ $fid ] ?? ( $field['default'] ?? '' ), $prefix );
										}
										?>
									</div>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</div>
					</aside>
				</div>

				<div class="wg-settings__savebar">
					<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save layout', 'webgram' ); ?></button>
					<span class="wg-settings__unsaved" hidden><?php esc_html_e( 'Unsaved changes', 'webgram' ); ?></span>
				</div>
			</form>
		</div>
		<?php
	}

	private static function posted_layout(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked by the caller (save()); JSON is sanitized by the builder sanitizer.
		$json   = isset( $_POST['layout_json'] ) ? wp_unslash( $_POST['layout_json'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$layout = json_decode( (string) $json, true );
		return is_array( $layout ) ? $layout : [];
	}

	private static function posted( string $key ): array {
		$value = isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked by the caller (save()); values are sanitized against the field schema.
		return Webgram_Settings_Page::strip_none_markers( $value );
	}

	public static function save_header(): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		check_admin_referer( 'webgram_save_header' );

		$areas    = self::posted_layout();
		$rows     = self::posted( 'rows' );
		$elements = self::posted( 'elements' );
		$current  = Webgram_Header_Builder::instance()->layout();
		$layout   = [ 'sticky' => $current['sticky'], 'elements' => array_merge( $current['elements'], $elements ) ];

		foreach ( Webgram_Header_Builder::DEVICES as $device ) {
			foreach ( Webgram_Header_Builder::ROWS as $row ) {
				foreach ( Webgram_Header_Builder::AREAS as $area ) {
					$layout[ $device ][ $row ][ $area ] = (array) ( $areas[ $device ][ $row ][ $area ] ?? [] );
				}
				$layout[ $device ][ $row ]['settings'] = array_merge( $current[ $device ][ $row ]['settings'], (array) ( $rows[ $device ][ $row ] ?? [] ) );
			}
		}

		Webgram_Header_Builder::instance()->save( $layout );
		wp_safe_redirect( add_query_arg( [ 'page' => 'webgram-header', 'wg_notice' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function save_footer(): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		check_admin_referer( 'webgram_save_footer' );

		$areas    = self::posted_layout();
		$rows     = self::posted( 'rows' );
		$elements = self::posted( 'elements' );
		$current  = Webgram_Footer_Builder::instance()->layout();

		$layout = [
			'widgets'  => [
				'enabled'  => ! empty( $rows['widgets']['enabled'] ),
				'columns'  => (int) ( $rows['widgets']['columns'] ?? $current['widgets']['columns'] ),
				'areas'    => (array) ( $areas['desktop']['widgets'] ?? [] ),
				'settings' => array_merge( $current['widgets']['settings'], (array) ( $rows['widgets']['settings'] ?? [] ) ),
			],
			'bottom'   => [
				'enabled'  => ! empty( $rows['bottom']['enabled'] ),
				'settings' => array_merge( $current['bottom']['settings'], (array) ( $rows['bottom']['settings'] ?? [] ) ),
			],
			'elements' => array_merge( $current['elements'], $elements ),
		];
		foreach ( Webgram_Footer_Builder::BOTTOM_AREAS as $area ) {
			$layout['bottom'][ $area ] = (array) ( $areas['desktop']['bottom'][ $area ] ?? [] );
		}

		Webgram_Footer_Builder::instance()->save( $layout );
		wp_safe_redirect( add_query_arg( [ 'page' => 'webgram-footer', 'wg_notice' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}
}

Webgram_Builder_Page::init();
