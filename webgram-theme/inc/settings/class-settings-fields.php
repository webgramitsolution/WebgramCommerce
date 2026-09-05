<?php
/**
 * Renders Theme Settings fields. One renderer shared by the settings panel, the header builder element settings
 * and the footer builder. Vanilla JS in assets/admin/settings.js handles repeaters, sortables, media, color pickers,
 * device tabs, dependencies and the unsaved changes guard.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Settings_Fields {

	/**
	 * @param array  $field  definition (needs id, type, label)
	 * @param mixed  $value  current value
	 * @param string $prefix input name prefix, e.g. "settings" or "elements[logo]"
	 */
	public static function render( array $field, mixed $value, string $prefix = 'settings' ): void {
		$type = $field['type'] ?? 'text';
		$id   = (string) $field['id'];
		$name = $prefix . '[' . $id . ']';
		$dom  = 'wg-field-' . sanitize_html_class( str_replace( [ '[', ']' ], [ '-', '' ], $prefix . '-' . $id ) );

		if ( 'heading' === $type ) {
			printf( '<h3 class="wg-settings__heading">%s</h3>', esc_html( $field['label'] ?? '' ) );
			if ( ! empty( $field['description'] ) ) {
				printf( '<p class="wg-settings__intro">%s</p>', wp_kses_post( $field['description'] ) );
			}
			return;
		}

		$show_if = '';
		if ( ! empty( $field['show_if'] ) && is_array( $field['show_if'] ) ) {
			$show_if = wp_json_encode( $field['show_if'] );
		}
		?>
		<div class="wg-field wg-field--<?php echo esc_attr( $type ); ?><?php echo ! empty( $field['full'] ) ? ' wg-field--full' : ''; ?>" data-field="<?php echo esc_attr( $id ); ?>" <?php echo $show_if ? 'data-show-if="' . esc_attr( $show_if ) . '"' : ''; ?>>
			<div class="wg-field__label">
				<label for="<?php echo esc_attr( $dom ); ?>"><?php echo esc_html( $field['label'] ?? $id ); ?></label>
				<?php if ( ! empty( $field['description'] ) ) : ?>
					<p class="wg-field__desc"><?php echo wp_kses_post( $field['description'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="wg-field__control">
				<?php self::control( $type, $field, $value, $name, $dom, $prefix ); ?>
			</div>
		</div>
		<?php
	}

	private static function control( string $type, array $field, mixed $value, string $name, string $dom, string $prefix ): void {
		switch ( $type ) {
			case 'switch':
				printf(
					'<label class="wg-switch"><input type="hidden" name="%1$s" value="0"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s><span class="wg-switch__track"></span><span class="wg-switch__text">%4$s</span></label>',
					esc_attr( $name ),
					esc_attr( $dom ),
					checked( (bool) $value, true, false ),
					esc_html( $field['inline_label'] ?? '' )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" min="%s" max="%s" step="%s" class="wg-input wg-input--number">%s',
					esc_attr( $dom ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( (string) ( $field['min'] ?? '' ) ),
					esc_attr( (string) ( $field['max'] ?? '' ) ),
					esc_attr( (string) ( $field['step'] ?? '1' ) ),
					! empty( $field['unit'] ) ? '<span class="wg-field__unit">' . esc_html( $field['unit'] ) . '</span>' : ''
				);
				break;

			case 'range':
				printf(
					'<div class="wg-range"><input type="range" id="%s" name="%s" value="%s" min="%s" max="%s" step="%s"><output>%s</output>%s</div>',
					esc_attr( $dom ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( (string) ( $field['min'] ?? 0 ) ),
					esc_attr( (string) ( $field['max'] ?? 100 ) ),
					esc_attr( (string) ( $field['step'] ?? '1' ) ),
					esc_html( (string) $value ),
					! empty( $field['unit'] ) ? '<span class="wg-field__unit">' . esc_html( $field['unit'] ) . '</span>' : ''
				);
				break;

			case 'select':
			case 'icon':
				$choices = 'icon' === $type ? webgram_icon_choices() : (array) ( $field['choices'] ?? [] );
				printf( '<select id="%s" name="%s" class="wg-input%s">', esc_attr( $dom ), esc_attr( $name ), 'icon' === $type ? ' wg-icon-select' : '' );
				foreach ( $choices as $key => $label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( (string) $key ), selected( (string) $value, (string) $key, false ), esc_html( (string) $label ) );
				}
				echo '</select>';
				if ( 'icon' === $type ) {
					echo '<span class="wg-icon-preview">' . webgram_icon( (string) $value, '', false ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-escaped SVG.
				}
				break;

			case 'radio':
			case 'radio_image':
				echo '<div class="wg-radio-group wg-radio-group--' . esc_attr( $type ) . '">';
				foreach ( (array) ( $field['choices'] ?? [] ) as $key => $label ) {
					$img = is_array( $label ) ? ( $label['image'] ?? '' ) : '';
					$txt = is_array( $label ) ? ( $label['label'] ?? $key ) : $label;
					printf(
						'<label class="wg-radio"><input type="radio" name="%s" value="%s" %s>%s<span>%s</span></label>',
						esc_attr( $name ),
						esc_attr( (string) $key ),
						checked( (string) $value, (string) $key, false ),
						$img ? '<img src="' . esc_url( $img ) . '" alt="">' : '',
						esc_html( (string) $txt )
					);
				}
				echo '</div>';
				break;

			case 'multicheck':
				$value = (array) $value;
				printf( '<input type="hidden" name="%s" value="">', esc_attr( $name . '[__none]' ) );
				echo '<div class="wg-check-group">';
				foreach ( (array) ( $field['choices'] ?? [] ) as $key => $label ) {
					printf(
						'<label class="wg-check"><input type="checkbox" name="%s[]" value="%s" %s><span>%s</span></label>',
						esc_attr( $name ),
						esc_attr( (string) $key ),
						checked( in_array( (string) $key, array_map( 'strval', $value ), true ), true, false ),
						esc_html( (string) $label )
					);
				}
				echo '</div>';
				break;

			case 'sortable':
				$items   = (array) ( $field['items'] ?? $field['choices'] ?? [] );
				$enabled = array_map( 'strval', (array) $value );
				$ordered = array_merge( array_intersect( $enabled, array_keys( $items ) ), array_diff( array_keys( $items ), $enabled ) );
				printf( '<input type="hidden" name="%s" value="">', esc_attr( $name . '[__none]' ) );
				echo '<ul class="wg-sortable" data-wg-sortable>';
				foreach ( $ordered as $key ) {
					$on = in_array( (string) $key, $enabled, true );
					printf(
						'<li class="wg-sortable__item%s" draggable="true"><span class="wg-sortable__handle" aria-hidden="true">&#8942;</span><label><input type="checkbox" name="%s[]" value="%s" %s><span>%s</span></label></li>',
						$on ? ' is-on' : '',
						esc_attr( $name ),
						esc_attr( (string) $key ),
						checked( $on, true, false ),
						esc_html( (string) $items[ $key ] )
					);
				}
				echo '</ul>';
				break;

			case 'color':
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="wg-color" data-alpha-enabled="true" data-default-color="%s">',
					esc_attr( $dom ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( (string) ( $field['default'] ?? '' ) )
				);
				break;

			case 'image':
				$src = $value ? wp_get_attachment_image_url( (int) $value, 'medium' ) : '';
				printf(
					'<div class="wg-media" data-wg-media><input type="hidden" id="%1$s" name="%2$s" value="%3$s"><div class="wg-media__preview"%5$s><img src="%4$s" alt=""></div><button type="button" class="button wg-media__select">%6$s</button> <button type="button" class="button-link-delete wg-media__remove"%5$s>%7$s</button></div>',
					esc_attr( $dom ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_url( (string) $src ),
					$src ? '' : ' hidden',
					esc_html__( 'Choose image', 'webgram' ),
					esc_html__( 'Remove', 'webgram' )
				);
				break;

			case 'page':
				wp_dropdown_pages(
					[
						'name'              => esc_attr( $name ),
						'id'                => esc_attr( $dom ),
						'selected'          => (int) $value,
						'show_option_none'  => esc_html__( 'Select a page', 'webgram' ),
						'option_none_value' => 0,
						'class'             => 'wg-input',
					]
				);
				break;

			case 'menu':
				printf( '<select id="%s" name="%s" class="wg-input"><option value="0">%s</option>', esc_attr( $dom ), esc_attr( $name ), esc_html__( 'Select a menu', 'webgram' ) );
				foreach ( wp_get_nav_menus() as $menu ) {
					printf( '<option value="%d" %s>%s</option>', (int) $menu->term_id, selected( (int) $value, (int) $menu->term_id, false ), esc_html( $menu->name ) );
				}
				echo '</select>';
				break;

			case 'html_block':
				$blocks = (array) apply_filters( 'webgram/html_blocks', [] );
				printf( '<select id="%s" name="%s" class="wg-input"><option value="0">%s</option>', esc_attr( $dom ), esc_attr( $name ), esc_html__( 'None', 'webgram' ) );
				foreach ( $blocks as $block_id => $title ) {
					printf( '<option value="%d" %s>%s</option>', (int) $block_id, selected( (int) $value, (int) $block_id, false ), esc_html( (string) $title ) );
				}
				echo '</select>';
				if ( ! webgram_has_core( 'site_tools' ) ) {
					printf( '<p class="wg-field__hint">%s</p>', esc_html__( 'HTML Blocks are provided by the Webgram Core plugin (Site Tools module).', 'webgram' ) );
				}
				break;

			case 'textarea':
			case 'html':
				printf( '<textarea id="%s" name="%s" rows="%d" class="wg-input wg-input--textarea" placeholder="%s">%s</textarea>', esc_attr( $dom ), esc_attr( $name ), (int) ( $field['rows'] ?? 4 ), esc_attr( (string) ( $field['placeholder'] ?? '' ) ), esc_textarea( (string) $value ) );
				break;

			case 'code':
				printf( '<textarea id="%s" name="%s" rows="%d" class="wg-input wg-code" data-wg-code="%s">%s</textarea>', esc_attr( $dom ), esc_attr( $name ), (int) ( $field['rows'] ?? 12 ), esc_attr( (string) ( $field['language'] ?? 'css' ) ), esc_textarea( (string) $value ) );
				break;

			case 'dimensions':
				$value = is_array( $value ) ? $value : [];
				echo '<div class="wg-devices" data-wg-devices>';
				self::device_tabs();
				foreach ( Webgram_Settings_Sanitizer::DEVICES as $device ) {
					printf(
						'<div class="wg-devices__pane" data-device="%s"><input type="number" name="%s[%s]" value="%s" min="%s" max="%s" step="%s" class="wg-input wg-input--number">%s</div>',
						esc_attr( $device ),
						esc_attr( $name ),
						esc_attr( $device ),
						esc_attr( (string) ( $value[ $device ] ?? '' ) ),
						esc_attr( (string) ( $field['min'] ?? '' ) ),
						esc_attr( (string) ( $field['max'] ?? '' ) ),
						esc_attr( (string) ( $field['step'] ?? '1' ) ),
						! empty( $field['unit'] ) ? '<span class="wg-field__unit">' . esc_html( $field['unit'] ) . '</span>' : ''
					);
				}
				echo '</div>';
				break;

			case 'typography':
				$value = is_array( $value ) ? $value : [];
				$size  = (array) ( $value['size'] ?? [] );
				echo '<div class="wg-typography">';
				printf( '<label>%s<select name="%s[family]" class="wg-input">', esc_html__( 'Family', 'webgram' ), esc_attr( $name ) );
				foreach ( webgram_font_choices( true ) as $key => $label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( (string) ( $value['family'] ?? 'inherit' ), $key, false ), esc_html( $label ) );
				}
				echo '</select></label>';
				printf( '<label>%s<select name="%s[weight]" class="wg-input">', esc_html__( 'Weight', 'webgram' ), esc_attr( $name ) );
				foreach ( [ 'inherit' => __( 'Inherit', 'webgram' ), '300' => '300', '400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800' ] as $key => $label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( (string) ( $value['weight'] ?? 'inherit' ), $key, false ), esc_html( $label ) );
				}
				echo '</select></label>';
				echo '<div class="wg-typography__size"><span class="wg-typography__label">' . esc_html__( 'Size (px)', 'webgram' ) . '</span><div class="wg-devices" data-wg-devices>';
				self::device_tabs();
				foreach ( Webgram_Settings_Sanitizer::DEVICES as $device ) {
					printf( '<div class="wg-devices__pane" data-device="%s"><input type="number" name="%s[size][%s]" value="%s" min="8" max="120" class="wg-input wg-input--number"></div>', esc_attr( $device ), esc_attr( $name ), esc_attr( $device ), esc_attr( (string) ( $size[ $device ] ?? '' ) ) );
				}
				echo '</div></div>';
				printf( '<label>%s<input type="number" name="%s[line_height]" value="%s" step="0.05" min="0" max="3" class="wg-input wg-input--number"></label>', esc_html__( 'Line height', 'webgram' ), esc_attr( $name ), esc_attr( (string) ( $value['line_height'] ?? '' ) ) );
				printf( '<label>%s<input type="number" name="%s[letter_spacing]" value="%s" step="0.01" min="-0.2" max="0.5" class="wg-input wg-input--number"></label>', esc_html__( 'Letter spacing (em)', 'webgram' ), esc_attr( $name ), esc_attr( (string) ( $value['letter_spacing'] ?? '' ) ) );
				printf( '<label>%s<select name="%s[transform]" class="wg-input">', esc_html__( 'Transform', 'webgram' ), esc_attr( $name ) );
				foreach ( [ 'none' => __( 'None', 'webgram' ), 'uppercase' => __( 'Uppercase', 'webgram' ), 'capitalize' => __( 'Capitalize', 'webgram' ) ] as $key => $label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( (string) ( $value['transform'] ?? 'none' ), $key, false ), esc_html( $label ) );
				}
				echo '</select></label></div>';
				break;

			case 'repeater':
				self::repeater( $field, (array) $value, $name );
				break;

			case 'secret':
				$has = '' !== (string) $value;
				printf(
					'<input type="password" id="%s" name="%s" value="" class="wg-input" autocomplete="new-password" placeholder="%s">%s',
					esc_attr( $dom ),
					esc_attr( $name ),
					$has ? esc_attr__( 'Saved. Enter a new value to replace it.', 'webgram' ) : '',
					$has ? ' <label class="wg-check wg-check--inline"><input type="checkbox" name="' . esc_attr( $prefix . '__clear[' . $field['id'] . ']' ) . '" value="1"><span>' . esc_html__( 'Clear', 'webgram' ) . '</span></label>' : ''
				);
				break;

			case 'link':
				printf( '<a class="button" href="%s">%s</a>', esc_url( (string) ( $field['url'] ?? '#' ) ), esc_html( (string) ( $field['button'] ?? __( 'Open', 'webgram' ) ) ) );
				break;

			case 'info':
				printf( '<div class="wg-field__info">%s</div>', wp_kses_post( (string) ( $field['content'] ?? '' ) ) );
				break;

			case 'url':
			case 'email':
			case 'text':
			default:
				printf(
					'<input type="%s" id="%s" name="%s" value="%s" class="wg-input" placeholder="%s">',
					esc_attr( in_array( $type, [ 'url', 'email' ], true ) ? $type : 'text' ),
					esc_attr( $dom ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? (string) $value : '' ),
					esc_attr( (string) ( $field['placeholder'] ?? '' ) )
				);
		}
	}

	private static function device_tabs(): void {
		echo '<div class="wg-devices__tabs" role="tablist">';
		foreach ( [ 'desktop' => __( 'Desktop', 'webgram' ), 'tablet' => __( 'Tablet', 'webgram' ), 'mobile' => __( 'Mobile', 'webgram' ) ] as $device => $label ) {
			printf( '<button type="button" class="wg-devices__tab%s" data-device="%s" title="%s">%s</button>', 'desktop' === $device ? ' is-active' : '', esc_attr( $device ), esc_attr( $label ), webgram_icon( 'device-' . $device, '', false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
	}

	private static function repeater( array $field, array $rows, string $name ): void {
		$subfields = (array) ( $field['fields'] ?? [] );
		$rows      = array_values( $rows );
		?>
		<div class="wg-repeater" data-wg-repeater data-max="<?php echo esc_attr( (string) ( $field['max'] ?? 50 ) ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[__none]" value="">
			<div class="wg-repeater__rows">
				<?php foreach ( $rows as $i => $row ) : ?>
					<?php self::repeater_row( $subfields, $row, $name, (string) $i, $field ); ?>
				<?php endforeach; ?>
			</div>
			<template class="wg-repeater__template">
				<?php self::repeater_row( $subfields, [], $name, '__INDEX__', $field ); ?>
			</template>
			<button type="button" class="button wg-repeater__add"><?php echo esc_html( $field['add_label'] ?? __( 'Add item', 'webgram' ) ); ?></button>
		</div>
		<?php
	}

	private static function repeater_row( array $subfields, array $row, string $name, string $index, array $field ): void {
		$title_key = (string) ( $field['title_field'] ?? array_key_first( $subfields ) );
		?>
		<div class="wg-repeater__row" draggable="true">
			<div class="wg-repeater__head">
				<span class="wg-sortable__handle" aria-hidden="true">&#8942;</span>
				<span class="wg-repeater__title"><?php echo esc_html( (string) ( $row[ $title_key ] ?? $field['row_label'] ?? __( 'Item', 'webgram' ) ) ); ?></span>
				<button type="button" class="wg-repeater__toggle" aria-label="<?php esc_attr_e( 'Toggle', 'webgram' ); ?>">&#9662;</button>
				<button type="button" class="wg-repeater__remove" aria-label="<?php esc_attr_e( 'Remove', 'webgram' ); ?>">&times;</button>
			</div>
			<div class="wg-repeater__body">
				<?php
				foreach ( $subfields as $sid => $sub ) {
					$sub['id'] = $sid;
					self::render( $sub, $row[ $sid ] ?? ( $sub['default'] ?? '' ), $name . '[' . $index . ']' );
				}
				?>
			</div>
		</div>
		<?php
	}
}

/** Icon names available to icon pickers (from assets/images/icons). */
function webgram_icon_choices(): array {
	static $choices = null;
	if ( null === $choices ) {
		$choices = [];
		foreach ( glob( WEBGRAM_DIR . '/assets/images/icons/*.svg' ) ?: [] as $file ) {
			$name             = basename( $file, '.svg' );
			$choices[ $name ] = ucwords( str_replace( '-', ' ', $name ) );
		}
		$choices = (array) apply_filters( 'webgram/icon_choices', $choices );
	}
	return $choices;
}

/** Font families offered in typography controls. */
function webgram_font_choices( bool $with_inherit = false ): array {
	$fonts = [
		'Inter'             => 'Inter',
		'Manrope'           => 'Manrope',
		'DM Sans'           => 'DM Sans',
		'Plus Jakarta Sans' => 'Plus Jakarta Sans',
		'Poppins'           => 'Poppins',
		'Outfit'            => 'Outfit',
		'Playfair Display'  => 'Playfair Display',
		'system'            => __( 'System font', 'webgram' ),
		'custom'            => __( 'Custom (uploaded)', 'webgram' ),
	];
	$fonts = (array) apply_filters( 'webgram/font_choices', $fonts );
	return $with_inherit ? [ 'inherit' => __( 'Inherit', 'webgram' ) ] + $fonts : $fonts;
}
