<?php
namespace Webgram\Core\Modules\Integrations\Elementor;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Webgram\Core\Modules\Integrations\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * One widget class for every Webgram definition. The definition is resolved from the constructor args on
 * registration and from $data['widgetType'] when Elementor rebuilds a saved widget.
 */
final class SectionWidget extends Widget_Base {

	private array $def;

	public function __construct( $data = [], $args = null ) {
		$def = $args['wgc_def'] ?? null;
		if ( ! is_array( $def ) && ! empty( $data['widgetType'] ) ) {
			$def = \webgram_core()->modules()->get( 'integrations' )?->registry()->get( preg_replace( '/^webgram-/', '', (string) $data['widgetType'] ) ?? '' );
		}
		$this->def = is_array( $def ) ? $def : [ 'id' => 'unknown', 'title' => 'Webgram', 'icon' => 'eicon-code', 'category' => 'webgram', 'controls' => [] ];
		parent::__construct( $data, $args );
	}

	public function get_name(): string {
		return 'webgram-' . $this->def['id'];
	}

	public function get_title(): string {
		return (string) $this->def['title'];
	}

	public function get_icon(): string {
		return (string) $this->def['icon'];
	}

	public function get_categories(): array {
		return [ (string) $this->def['category'] ];
	}

	public function get_keywords(): array {
		return array_merge( [ 'webgram' ], (array) ( $this->def['keywords'] ?? [] ) );
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'wgc_content', [ 'label' => __( 'Content', 'webgram-core' ) ] );
		foreach ( $this->def['controls'] as $cid => $c ) {
			$this->add_mapped_control( $this, $cid, $c );
		}
		if ( ! $this->def['controls'] ) {
			$this->add_control( 'wgc_note', [ 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'This widget has no options. Configure it in Webgram > Settings.', 'webgram-core' ) ] );
		}
		$this->end_controls_section();
	}

	/** @param Widget_Base|Repeater $target */
	private function add_mapped_control( $target, string $cid, array $c ): void {
		$base = [ 'label' => (string) $c['label'], 'default' => $c['default'] ];
		if ( ! empty( $c['description'] ) ) {
			$base['description'] = (string) $c['description'];
		}
		switch ( $c['type'] ) {
			case 'textarea':
			case 'html':
				$target->add_control( $cid, $base + [ 'type' => Controls_Manager::TEXTAREA, 'rows' => 4 ] );
				break;
			case 'number':
				$target->add_control( $cid, $base + [ 'type' => Controls_Manager::NUMBER, 'min' => $c['min'] ?? null, 'max' => $c['max'] ?? null, 'step' => $c['step'] ?? 1 ] );
				break;
			case 'switch':
				$target->add_control( $cid, [ 'label' => $base['label'], 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => $c['default'] ? 'yes' : '' ] );
				break;
			case 'select':
				$target->add_control( $cid, $base + [ 'type' => Controls_Manager::SELECT, 'options' => array_map( 'strval', Registry::options( $c ) ) ] );
				break;
			case 'url':
				$target->add_control( $cid, [ 'label' => $base['label'], 'type' => Controls_Manager::URL, 'default' => [ 'url' => (string) $c['default'] ], 'options' => false ] );
				break;
			case 'color':
				$target->add_control( $cid, $base + [ 'type' => Controls_Manager::COLOR ] );
				break;
			case 'image':
				$target->add_control( $cid, [ 'label' => $base['label'], 'type' => Controls_Manager::MEDIA, 'default' => [ 'id' => (int) $c['default'], 'url' => '' ] ] );
				break;
			case 'product':
			case 'post':
			case 'category':
			case 'tag':
				$target->add_control( $cid, [ 'label' => $base['label'], 'type' => Controls_Manager::SELECT2, 'multiple' => in_array( $c['type'], [ 'category', 'tag' ], true ), 'options' => self::choices( $c ), 'default' => $c['default'], 'label_block' => true ] );
				break;
			case 'repeater':
				$repeater = new Repeater();
				foreach ( (array) ( $c['fields'] ?? [] ) as $fid => $f ) {
					$this->add_mapped_control( $repeater, $fid, $f );
				}
				$target->add_control( $cid, [ 'label' => $base['label'], 'type' => Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => (array) $c['default'], 'title_field' => '{{{ ' . (string) array_key_first( (array) ( $c['fields'] ?? [ 'title' => 1 ] ) ) . ' }}}' ] );
				break;
			default:
				$target->add_control( $cid, $base + [ 'type' => Controls_Manager::TEXT, 'label_block' => true ] );
		}
	}

	/** Options for product, post, category and tag pickers (capped so the editor stays responsive). */
	private static function choices( array $c ): array {
		$out = [];
		if ( 'category' === $c['type'] || 'tag' === $c['type'] ) {
			$terms = get_terms( [ 'taxonomy' => 'category' === $c['type'] ? 'product_cat' : 'product_tag', 'hide_empty' => false, 'number' => 300 ] );
			foreach ( is_array( $terms ) ? $terms : [] as $term ) {
				$out[ $term->slug ] = $term->name;
			}
			return $out;
		}
		$type = 'product' === $c['type'] ? 'product' : (string) ( $c['post_type'] ?? 'post' );
		foreach ( get_posts( [ 'post_type' => $type, 'numberposts' => 200, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ] ) as $post ) {
			$out[ (int) $post->ID ] = $post->post_title;
		}
		return $out;
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$args     = [];
		foreach ( $this->def['controls'] as $cid => $c ) {
			$value = $settings[ $cid ] ?? $c['default'];
			if ( 'switch' === $c['type'] ) {
				$value = 'yes' === $value;
			} elseif ( 'url' === $c['type'] && is_array( $value ) ) {
				$value = (string) ( $value['url'] ?? '' );
			} elseif ( 'image' === $c['type'] && is_array( $value ) ) {
				$value = (int) ( $value['id'] ?? 0 );
			}
			$args[ $cid ] = $value;
		}
		$html = \webgram_core()->modules()->get( 'integrations' )?->registry()->render( $this->def['id'], $args ) ?? '';
		if ( '' === $html && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$html = '<div class="wgc-widget-empty" style="padding:24px;border:1px dashed #ccc;text-align:center;color:#666">' . esc_html( sprintf( /* translators: %s: widget title */ __( '%s: nothing to show yet. Check the widget options or the module settings.', 'webgram-core' ), $this->def['title'] ) ) . '</div>';
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer output.
	}
}
