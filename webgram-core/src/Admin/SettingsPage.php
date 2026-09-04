<?php
namespace Webgram\Core\Admin;

use Webgram\Core\Plugin;
use Webgram\Core\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Webgram > Settings. One tab per module that declares settings_fields(), plus a General tab.
 * Fields are rendered from the module's declarative schema; secrets are encrypted before storage.
 */
final class SettingsPage {

	public const SLUG = 'webgram-core-settings';

	public function __construct( private Plugin $plugin ) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_post_webgram_core_save_settings', [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
	}

	public function menu(): void {
		add_submenu_page( ModulesPage::SLUG, __( 'Settings', 'webgram-core' ), __( 'Settings', 'webgram-core' ), 'manage_options', self::SLUG, [ $this, 'render' ] );
	}

	public function assets( string $hook ): void {
		if ( str_contains( $hook, self::SLUG ) ) {
			wp_enqueue_style( 'webgram-core-admin' );
			wp_enqueue_script( 'webgram-core-admin' );
			wp_enqueue_media();
		}
	}

	/** @return array<string, array{label: string, fields: array}> */
	private function tabs(): array {
		$tabs = [
			'general' => [
				'label'  => __( 'General', 'webgram-core' ),
				'fields' => $this->general_fields(),
			],
		];

		foreach ( $this->plugin->modules()->all() as $id => $module ) {
			if ( $module->is_implemented() && $module->settings_fields() ) {
				$tabs[ $id ] = [
					'label'  => $module->name(),
					'fields' => $module->settings_fields(),
				];
			}
		}

		return (array) apply_filters( 'webgram_core/settings_tabs', $tabs );
	}

	private function general_fields(): array {
		return [
			[
				'id'          => 'default_country',
				'label'       => __( 'Default phone country', 'webgram-core' ),
				'type'        => 'select',
				'options'     => function_exists( 'WC' ) && WC()->countries ? WC()->countries->get_countries() : [ 'IN' => 'India' ],
				'default'     => function_exists( 'WC' ) ? WC()->countries->get_base_country() : 'IN',
				'description' => __( 'Used to normalize phone numbers that are entered without a country code.', 'webgram-core' ),
			],
			[
				'id'          => 'remove_data_on_uninstall',
				'label'       => __( 'Remove all data on uninstall', 'webgram-core' ),
				'type'        => 'checkbox',
				'default'     => false,
				'description' => __( 'Deletes Webgram Core tables, options and generated files when the plugin is deleted. Cannot be undone.', 'webgram-core' ),
			],
		];
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs    = $this->tabs();
		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $tabs[ $current ] ) ) {
			$current = 'general';
		}
		$settings = $this->plugin->settings( $current );
		?>
		<div class="wrap wgc-admin">
			<h1><?php esc_html_e( 'Webgram Core Settings', 'webgram-core' ); ?></h1>

			<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'webgram-core' ); ?></p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $id => $tab ) : ?>
					<a class="nav-tab <?php echo $id === $current ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'tab' => $id ], admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $tab['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wgc-settings-form">
				<input type="hidden" name="action" value="webgram_core_save_settings">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $current ); ?>">
				<?php wp_nonce_field( 'webgram_core_save_settings_' . $current ); ?>

				<?php do_action( 'webgram_core/settings_tab_before', $current ); ?>

				<table class="form-table" role="presentation">
					<?php foreach ( $tabs[ $current ]['fields'] as $field ) : ?>
						<?php $this->render_field( $field, $settings->get( $field['id'], $field['default'] ?? '' ) ); ?>
					<?php endforeach; ?>
				</table>

				<?php do_action( 'webgram_core/settings_tab_after', $current ); ?>

				<?php submit_button( __( 'Save changes', 'webgram-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	private function render_field( array $field, mixed $value ): void {
		$id   = 'wgc_' . $field['id'];
		$name = 'settings[' . $field['id'] . ']';
		$type = $field['type'] ?? 'text';
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
			<td>
				<?php
				switch ( $type ) {
					case 'checkbox':
						printf( '<label><input type="checkbox" id="%s" name="%s" value="1" %s> %s</label>', esc_attr( $id ), esc_attr( $name ), checked( (bool) $value, true, false ), esc_html( $field['inline_label'] ?? '' ) );
						break;
					case 'select':
						printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
						foreach ( (array) ( $field['options'] ?? [] ) as $k => $label ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( (string) $k ), selected( (string) $value, (string) $k, false ), esc_html( (string) $label ) );
						}
						echo '</select>';
						break;
					case 'textarea':
						printf( '<textarea id="%s" name="%s" rows="4" class="large-text">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
						break;
					case 'number':
						printf( '<input type="number" id="%s" name="%s" value="%s" class="small-text" min="%s" max="%s" step="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), esc_attr( (string) ( $field['min'] ?? '' ) ), esc_attr( (string) ( $field['max'] ?? '' ) ), esc_attr( (string) ( $field['step'] ?? '1' ) ) );
						break;
					case 'secret':
						$has = '' !== (string) $value;
						printf(
							'<input type="password" id="%s" name="%s" value="" class="regular-text" autocomplete="new-password" placeholder="%s">%s',
							esc_attr( $id ),
							esc_attr( $name ),
							$has ? esc_attr__( 'Saved. Enter a new value to replace it.', 'webgram-core' ) : '',
							$has ? ' <label class="wgc-inline"><input type="checkbox" name="' . esc_attr( 'clear[' . $field['id'] . ']' ) . '" value="1"> ' . esc_html__( 'Clear', 'webgram-core' ) . '</label>' : ''
						);
						break;
					case 'color':
						printf( '<input type="text" id="%s" name="%s" value="%s" class="wgc-color-field" data-default-color="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), esc_attr( (string) ( $field['default'] ?? '' ) ) );
						break;
					case 'image':
						$src = $value ? wp_get_attachment_image_url( (int) $value, 'medium' ) : '';
						printf(
							'<div class="wgc-image-field"><input type="hidden" id="%1$s" name="%2$s" value="%3$s"><img src="%4$s" alt="" %5$s><button type="button" class="button wgc-image-select">%6$s</button> <button type="button" class="button-link wgc-image-remove" %5$s>%7$s</button></div>',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( (string) $value ),
							esc_url( (string) $src ),
							$src ? '' : 'hidden',
							esc_html__( 'Choose image', 'webgram-core' ),
							esc_html__( 'Remove', 'webgram-core' )
						);
						break;
					default:
						printf( '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" placeholder="%s">', esc_attr( $type ), esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), esc_attr( (string) ( $field['placeholder'] ?? '' ) ) );
				}
				if ( ! empty( $field['description'] ) ) {
					printf( '<p class="description">%s</p>', wp_kses_post( $field['description'] ) );
				}
				?>
			</td>
		</tr>
		<?php
	}

	public function save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		check_admin_referer( 'webgram_core_save_settings_' . $tab );

		$tabs = $this->tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			wp_die( esc_html__( 'Unknown settings tab.', 'webgram-core' ) );
		}

		$raw   = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$clear = isset( $_POST['clear'] ) && is_array( $_POST['clear'] ) ? wp_unslash( $_POST['clear'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$settings = $this->plugin->settings( $tab );
		$values   = $settings->all();

		foreach ( $tabs[ $tab ]['fields'] as $field ) {
			$id   = $field['id'];
			$type = $field['type'] ?? 'text';

			if ( 'secret' === $type ) {
				if ( ! empty( $clear[ $id ] ) ) {
					$values[ $id ] = '';
				} elseif ( isset( $raw[ $id ] ) && '' !== trim( (string) $raw[ $id ] ) ) {
					$values[ $id ] = $this->plugin->crypto()->encrypt( sanitize_text_field( (string) $raw[ $id ] ) );
				}
				continue;
			}

			$sanitize = $field['sanitize'] ?? match ( $type ) {
				'checkbox' => 'bool',
				'number'   => 'int',
				'textarea' => 'textarea',
				'color'    => 'hex_color',
				'image'    => 'int',
				'select'   => 'text',
				'email'    => 'email',
				'url'      => 'url',
				default    => 'text',
			};

			$value = Sanitizer::value( $raw[ $id ] ?? ( 'checkbox' === $type ? false : '' ), is_string( $sanitize ) ? $sanitize : 'text' );
			if ( is_callable( $sanitize ) ) {
				$value = $sanitize( $raw[ $id ] ?? '' );
			}
			if ( 'select' === $type && ! isset( $field['options'][ $value ] ) ) {
				$value = $field['default'] ?? '';
			}
			$values[ $id ] = $value;
		}

		$settings->save( (array) apply_filters( 'webgram_core/settings_save', $values, $tab ) );
		do_action( 'webgram_core/settings_saved', $tab, $values );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'tab' => $tab, 'saved' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}
}
