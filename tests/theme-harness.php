<?php
// Theme harness: exercises settings, sanitizers, builders, CSS generator and helpers without WordPress.
define('ABSPATH','/tmp/'); define('WEEK_IN_SECONDS',604800); define('DAY_IN_SECONDS',86400); define('MB_IN_BYTES',1048576);
$GLOBALS['opts']=[]; $GLOBALS['t']=[]; $GLOBALS['__filters']=[]; $GLOBALS['__actions']=[];
function get_template_directory(){ return __DIR__.'/../webgram-theme'; }
function get_template_directory_uri(){ return 'http://x/wp-content/themes/webgram'; }
function add_action($h,$cb,$p=10,$a=1){ $GLOBALS['__actions'][$h][]=$cb; } function add_filter($h,$cb,$p=10,$a=1){ $GLOBALS['__filters'][$h][]=$cb; }
function apply_filters($h,$v,...$a){ foreach($GLOBALS['__filters'][$h]??[] as $cb){ $v=$cb($v,...$a);} return $v; } function do_action($h,...$a){ foreach($GLOBALS['__actions'][$h]??[] as $cb){ $cb(...$a);} }
function get_option($k,$d=false){ return $GLOBALS['opts'][$k] ?? $d; } function update_option($k,$v,$al=null){ $GLOBALS['opts'][$k]=$v; return true; } function delete_option($k){ unset($GLOBALS['opts'][$k]); }
function get_theme_mods(){ return $GLOBALS['mods'] ?? []; }
function get_transient($k){ return $GLOBALS['t'][$k] ?? false; } function set_transient($k,$v,$x){ $GLOBALS['t'][$k]=$v; } function delete_transient($k){ unset($GLOBALS['t'][$k]); }
function is_customize_preview(){ return false; } function wp_strip_all_tags($s){ return strip_tags($s); }
function __($s,$d=null){ return $s; } function esc_html__($s,$d=null){ return $s; } function esc_attr__($s,$d=null){ return $s; } function get_bloginfo($k){ return 'Demo Store'; }
function sanitize_html_class($s){ return preg_replace('/[^A-Za-z0-9_-]/','',$s); } function sanitize_key($s){ return strtolower(preg_replace('/[^a-z0-9_\-]/','',strtolower($s))); }
function sanitize_text_field($s){ return trim(strip_tags((string)$s)); } function sanitize_textarea_field($s){ return trim(strip_tags((string)$s)); } function sanitize_email($s){ return filter_var($s,FILTER_VALIDATE_EMAIL)?:''; }
function esc_url_raw($s){ return filter_var($s,FILTER_VALIDATE_URL)?:''; } function esc_url($s){ return htmlspecialchars($s); } function esc_html($s){ return htmlspecialchars((string)$s); } function esc_attr($s){ return htmlspecialchars((string)$s); }
function wp_kses_post($s){ return strip_tags($s,'<a><strong><em><p><br><span>'); } function absint($n){ return abs((int)$n); } function wp_json_encode($v,$f=0){ return json_encode($v,$f); }
function is_singular(){return false;} function is_home(){return false;} function is_archive(){return false;} function is_search(){return false;} function is_single(){return false;} function is_page(){return false;} function is_rtl(){return false;} function is_admin(){return false;}
function sanitize_file_name($s){ return preg_replace('/[^a-z0-9\-]/','',$s); } function wp_kses($s,$a){ return $s; } function admin_url($p=''){ return 'http://x/wp-admin/'.$p; } function home_url($p=''){ return 'http://x'.$p; }
function remove_accents($s){ return $s; } function untrailingslashit($s){ return rtrim($s,'/'); } function content_url(){ return 'http://x/wp-content'; }
class Walker_Nav_Menu { public function start_lvl(&$o,$d=0,$a=null){} public function end_lvl(&$o,$d=0,$a=null){} public function start_el(&$o,$i,$d=0,$a=null,$id=0){} public function end_el(&$o,$i,$d=0,$a=null){} }
class WP_Customize_Manager {}
require get_template_directory().'/functions.php';
$fail=0; function check($l,$c){ global $fail; echo ($c?'PASS':'FAIL')."  $l\n"; if(!$c)$fail++; }

// 1. CSS generator
$css = Webgram_CSS_Generator::instance()->get_css();
check('css generator outputs :root block', str_starts_with($css, ':root{--'));
check('primary color token present', str_contains($css, '--wg-color-primary:#a0181f'));
check('container token has px', str_contains($css, '--wg-container-max:1320px'));
check('font stack quoted', str_contains($css, '--wg-font-body:"Inter",system-ui,sans-serif'));
check('heading size tokens per device', str_contains($css,'--wg-fs-h1:40px') && str_contains($css,'@media(max-width:767.98px){:root{--wg-font-size-base:15px;--wg-fs-h1:28px'));
Webgram_Settings::instance()->update(['color_primary'=>'#123456','radius_scale'=>'pill','custom_css'=>'.x{color:red}</style><script>alert(1)</script>','custom_css_mobile'=>'.m{display:none}']);
$css = Webgram_CSS_Generator::instance()->get_css();
check('settings override applied', str_contains($css,'--wg-color-primary:#123456'));
check('radius scale pill', str_contains($css,'--wg-radius-md:14px'));
check('custom css appended and mobile wrapped', str_contains($css,'.x{color:red}') && str_contains($css,'@media(max-width:767.98px){.m{display:none}}'));
check('custom css cannot break out of style tag', !str_contains($css,'</style>') && !str_contains($css,'<script'));
Webgram_Settings::instance()->update(['color_accent'=>'</style><script>x</script>']);
check('malicious color value dropped', !str_contains(Webgram_CSS_Generator::instance()->get_css(),'script'));
Webgram_Settings::instance()->reset();

// 2. Icons and layout
$icon = webgram_icon('cart','extra',false);
check('svg icon injected with class and aria-hidden', str_contains($icon,'class="wg-icon wg-icon--cart extra"') && str_contains($icon,'aria-hidden="true"'));
check('unknown icon returns empty', webgram_icon('../../etc/passwd','',false)==='');
check('default layout container', webgram_layout()==='container');
check('icon set covers builder and tab icons', count(array_intersect(['settings','megaphone','device-mobile','map-pin','social-instagram','truck','help-circle','package'], array_keys(webgram_icon_choices())))===8);

// 3. Settings tabs and defaults consistency
$tabs = Webgram_Settings::instance()->tabs();
check('21 theme tabs registered', count($tabs)===21 && isset($tabs['general'],$tabs['single_product'],$tabs['cart_checkout'],$tabs['custom_css']));
$fields = Webgram_Settings::instance()->theme_fields();
$missing = [];
foreach (array_keys(webgram_defaults()) as $key) { if (!isset($fields[$key])) $missing[] = $key; }
check('every default has a field ('.implode(',',$missing).')', $missing===[]);
$nodefault = [];
foreach ($fields as $id=>$f) { if (!in_array($f['type']??'text',['heading','link','info'],true) && !array_key_exists($id, webgram_defaults()) && !isset($f['default'])) $nodefault[]=$id; }
check('every stored field has a default ('.implode(',',$nodefault).')', $nodefault===[]);

// 4. Sanitizer
$S = 'Webgram_Settings_Sanitizer';
check('switch accepts 1/true/on', $S::sanitize(['type'=>'switch'],'1')===true && $S::sanitize(['type'=>'switch'],'on')===true && $S::sanitize(['type'=>'switch'],'0')===false);
check('number clamps to range', $S::sanitize(['type'=>'number','min'=>10,'max'=>20],'99')===20 && $S::sanitize(['type'=>'number','min'=>10,'max'=>20],'abc','')===10);
check('color accepts hex and rgba, rejects junk', $S::color('#ABCDEF')==='#abcdef' && $S::color('rgba(1, 2, 3, 0.5)')==='rgba(1,2,3,0.5)' && $S::color('url(x)')==='' && $S::color('red')==='');
check('select falls back to default on unknown', $S::sanitize(['type'=>'select','choices'=>['a'=>1,'b'=>2],'default'=>'a'],'zzz')==='a');
check('multicheck filters unknown', $S::sanitize(['type'=>'multicheck','choices'=>['a'=>1,'b'=>2]],['a','x','b'])===['a','b']);
check('sortable keeps order and drops unknown/dupes', $S::sanitize(['type'=>'sortable','items'=>['t'=>1,'p'=>1,'m'=>1]],['p','x','t','p'])===['p','t']);
$dims = $S::sanitize(['type'=>'dimensions','min'=>8,'max'=>100,'default'=>['desktop'=>16]],['desktop'=>'500','tablet'=>'','mobile'=>'12']);
check('dimensions clamp and fill', $dims===['desktop'=>100,'tablet'=>100,'mobile'=>12]);
$rep = $S::sanitize(['type'=>'repeater','max'=>2,'fields'=>['icon'=>['type'=>'text'],'url'=>['type'=>'url']]],[['icon'=>'<b>x</b>','url'=>'javascript:alert(1)'],['icon'=>'y','url'=>'https://a.b/'],['icon'=>'z']]);
check('repeater sanitizes subfields and respects max', count($rep)===2 && $rep[0]['icon']==='x' && $rep[0]['url']==='' && $rep[1]['url']==='https://a.b/');
check('sanitize_all keeps untouched non-boolean fields', $S::sanitize_all(['a'=>['type'=>'text'],'b'=>['type'=>'switch']],['a'=>'v'])===['a'=>'v','b'=>false]);
check('code css strips tags', !str_contains($S::code('a{}</style><img onerror=x>','css'),'<'));

// 5. Header builder
$hb = Webgram_Header_Builder::instance();
check('header elements registered', count($hb->elements())>=20 && isset($hb->elements()['announcement'],$hb->elements()['search'],$hb->elements()['track_order']));
check('cart element unavailable without WooCommerce', $hb->element('cart')->is_available()===false && $hb->element('logo')->is_available()===true);
$san = $hb->sanitize(['desktop'=>['main'=>['left'=>['logo','bogus','logo','search'],'right'=>['search','cart'],'settings'=>['height'=>'9999','bg'=>'url(javascript:1)','container'=>'nope']]],'sticky'=>['enabled'=>'1','rows'=>['main','zzz']],'elements'=>['search'=>['placeholder'=>'<i>Find</i>','min_width'=>'50'],'nope'=>['a'=>1]]]);
check('unknown element ids stripped and dupes removed', $san['desktop']['main']['left']===['logo','search'] && $san['desktop']['main']['right']===['cart']);
check('row settings clamped and unsafe css rejected', $san['desktop']['main']['settings']['height']===160 && $san['desktop']['main']['settings']['bg']==='' && $san['desktop']['main']['settings']['container']==='boxed');
check('sticky rows validated', $san['sticky']['rows']===['main'] && $san['sticky']['enabled']===true);
check('element settings sanitized, unknown element dropped', $san['elements']['search']['placeholder']==='Find' && $san['elements']['search']['min_width']===200 && !isset($san['elements']['nope']));
check('mobile layout normalized when absent', isset($san['mobile']['main']['left']) && $san['mobile']['main']['left']===[]);
$GLOBALS['opts']['webgram_header_layout']='garbage';
$layout = $hb->layout();
check('malformed stored layout falls back to preset', $layout['desktop']['main']['left']===['logo','deliver_to'] && $layout['desktop']['bottom']['center']===['menu_secondary']);
check('css value sanitizer allows tokens and gradients', Webgram_Header_Builder::sanitize_css_value('var(--wg-color-primary)')==='var(--wg-color-primary)' && Webgram_Header_Builder::sanitize_css_value('linear-gradient(45deg,#fff,#000)')!=='' && Webgram_Header_Builder::sanitize_css_value('expression(x)')==='');
check('presets: store preset matches spec header', webgram_header_presets()['store']['layout']['desktop']['main']['right']===['track_order','bulk_order','wishlist','compare','help','cart','account']);

// 6. Footer builder
$fb = Webgram_Footer_Builder::instance();
$fs = $fb->sanitize(['widgets'=>['columns'=>'9','areas'=>['col_1'=>['logo','nope'],'col_2'=>['menu_1'],'col_9'=>['x']]],'bottom'=>['left'=>['copyright','copyright'],'right'=>['payment_icons']]]);
check('footer columns clamped and elements validated', $fs['widgets']['columns']===6 && $fs['widgets']['areas']['col_1']===['logo'] && $fs['bottom']['left']===['copyright'] && $fs['bottom']['right']===['payment_icons']);

// 7. Migration and import
$conv = Webgram_Settings_Migration::convert(['color_primary'=>'#111111','header_sticky'=>false,'font_size_base'=>18,'shop_columns'=>4,'unknown'=>'x']);
check('migration maps and converts', $conv['color_primary']==='#111111' && $conv['sticky_enabled']===false && $conv['font_size_base']['desktop']===18 && $conv['shop_columns']['desktop']===4 && !isset($conv['unknown']));
$applied = Webgram_Import_Export::apply_import(['product'=>'webgram','theme_settings'=>['color_primary'=>'#222222','evil'=>'x','container_width'=>'5000'],'header_layout'=>['desktop'=>['main'=>['left'=>['logo']]]]]);
check('import applies sanitized values and ignores unknown keys', in_array('theme_settings',$applied,true) && Webgram_Settings::instance()->get('color_primary')==='#222222' && Webgram_Settings::instance()->get('container_width')===1600 && !array_key_exists('evil',Webgram_Settings::instance()->stored()));
check('import applies header layout', get_option('webgram_header_layout')['desktop']['main']['left']===['logo']);
$export = Webgram_Import_Export::build_export();
check('export round trip contains sections', $export['product']==='webgram' && isset($export['theme_settings'],$export['header_layout'],$export['footer_layout']));

// 8. Live search results shape without WooCommerce is guarded (no WP_Query here, so only the helper contract)
check('payment icon choices and svg exist', isset(webgram_payment_icon_choices()['upi']) && str_contains(webgram_payment_icon('visa',false),'<svg'));
check('placeholder replacement', webgram_replace_placeholders('© {year} {site}')==='© '.gmdate('Y').' Demo Store');

// 9. Product card helpers (Phase 2)
function number_format_i18n($n,$d=0){ return number_format((float)$n,$d); } function _n($s,$p,$n,$d=null){ return $n===1?$s:$p; }
check('savings percent', Webgram_WC_Product_Card::percent(900,499)===45 && Webgram_WC_Product_Card::percent(100,120)===0 && Webgram_WC_Product_Card::percent(0,10)===0);
$pill = Webgram_WC_Product_Card::rating_pill(4.5, 12);
check('rating pill markup', str_contains($pill,'class="wg-rating-pill"') && str_contains($pill,'4.5') && str_contains($pill,'(12)') && Webgram_WC_Product_Card::rating_pill(0,3)==='');
$pill = Webgram_WC_Product_Card::rating_pill(4.53, 196, '#reviews-anchor', true);
check('rating pill large variant with link and count text', str_starts_with($pill,'<a ') && str_contains($pill,'4.53') && str_contains($pill,'/5') && str_contains($pill,'196 reviews'));
check('shop and product classes load without WooCommerce', class_exists('Webgram_WC_Shop') && class_exists('Webgram_WC_Product'));

// 7. Cart, checkout and account (Phase 3)
check('cart, checkout and account classes load without WooCommerce', class_exists('Webgram_WC_Cart') && class_exists('Webgram_WC_Checkout') && class_exists('Webgram_WC_Account'));
$tl = Webgram_WC_Checkout::timeline('processing', true);
check('thank you timeline: processing marks 3 of 4, current is processing', count($tl)===4 && $tl[2]['done'] && $tl[2]['current'] && !$tl[3]['done']);
check('thank you timeline: on-hold unpaid stops at placed, paid reaches payment', Webgram_WC_Checkout::timeline('on-hold', false)[0]['current'] && Webgram_WC_Checkout::timeline('on-hold', true)[1]['current'] && !Webgram_WC_Checkout::timeline('on-hold', true)[2]['done']);
check('thank you timeline: completed marks all', count(array_filter(Webgram_WC_Checkout::timeline('completed', true), fn($s)=>$s['done']))===4);
check('split name: first and rest, whitespace collapsed', Webgram_WC_Account::split_name('  Priya   Sharma Rao ')===['Priya','Sharma Rao'] && Webgram_WC_Account::split_name('Priya')===['Priya',''] && Webgram_WC_Account::split_name('')===['','']);
check('cart drawer defaults registered', webgram_defaults()['cart_drawer']===true && webgram_defaults()['cart_after_add']==='drawer' && isset(webgram_defaults()['checkout_coupon_place']));

// 10. Phase 8: bundled Core installer and demo importer pure logic
check('installer parses the plugin header version', Webgram_Plugin_Installer::parse_header_version("<?php\n/**\n * Plugin Name: Webgram Core\n * Version:           1.0.0\n */")==='1.0.0' && Webgram_Plugin_Installer::parse_header_version('no header')==='');
check('installer state: no bundle, install, update, activate, current', Webgram_Plugin_Installer::resolve_state(false,'1.0.0','',false)==='no_bundle' && Webgram_Plugin_Installer::resolve_state(true,'1.0.0','',false)==='install' && Webgram_Plugin_Installer::resolve_state(true,'1.1.0','1.0.0',true)==='update' && Webgram_Plugin_Installer::resolve_state(true,'1.0.0','1.0.0',false)==='activate' && Webgram_Plugin_Installer::resolve_state(true,'1.0.0','1.0.0',true)==='current' && Webgram_Plugin_Installer::resolve_state(true,'','1.0.0',true)==='current');
check('demo importer keeps canonical step order and drops unknown steps', Webgram_Demo_Importer::normalize_steps(['widgets','bogus','settings','pages'])===['settings','pages','widgets']);
check('demo importer injects the slider id once', Webgram_Demo_Importer::inject_slider_id('<!-- wp:webgram/slider {"slider_id":0} /--> {"slider_id":0}', 42)==='<!-- wp:webgram/slider {"slider_id":42} /--> {"slider_id":0}' && Webgram_Demo_Importer::inject_slider_id('x{"slider_id":0}', 0)==='x{"slider_id":0}');
$demo = json_decode(file_get_contents(get_template_directory().'/demo/theme-settings.json'), true);
check('demo settings file valid and presets exist', is_array($demo) && isset(webgram_header_presets()[$demo['header_preset']], webgram_footer_presets()[$demo['footer_preset']]) && json_decode(file_get_contents(get_template_directory().'/demo/posts.json'), true) && json_decode(file_get_contents(get_template_directory().'/demo/widgets.json'), true));
$csv = array_map('str_getcsv', file(get_template_directory().'/demo/products.csv'));
$missing = array_diff(array_map(fn($r)=>$r[7], array_slice($csv,1)), array_map(fn($f)=>basename($f), glob(get_template_directory().'/demo/images/product-*.png')));
check('demo products csv: 12 rows, every image file present', count($csv)===13 && $csv[0][0]==='sku' && $missing===[]);
check('version constants agree', WEBGRAM_VERSION==='1.0.0' && WEBGRAM_MIN_CORE_VERSION==='1.0.0');
check('setup wizard plan order and options', Webgram_Setup_Wizard::plan([])===['woocommerce','core'] && Webgram_Setup_Wizard::plan(['elementor'=>1,'child'=>1,'demo'=>1])===['woocommerce','core','elementor','child','demo:settings','demo:images','demo:products','demo:posts','demo:core','demo:pages','demo:menus','demo:widgets']);
check('custom sidebar ids are unique and safe', Webgram_Sidebars::make_id('Shop Promo!')==='sidebar-shop-promo' && Webgram_Sidebars::make_id('Shop Promo', ['sidebar-shop-promo'])==='sidebar-shop-promo-2' && Webgram_Sidebars::make_id('!!!')==='sidebar-custom');
check('page options layouts and sidebar choices', isset(Webgram_Page_Metabox::layouts()['sidebar-left']) && isset(Webgram_Sidebars::choices()['sidebar-product']) && Webgram_Sidebars::for_context('shop')==='sidebar-shop');
check('page title type helper defaults to blog', webgram_page_title_type()==='blog');
$css = Webgram_CSS_Generator::instance()->get_css();
check('spacing, border and link tokens generated', str_contains($css,'--wg-space-4:16px') && str_contains($css,'--wg-border-width:1px') && str_contains($css,'--wg-color-link:#a0181f') && str_contains($css,'--wg-section-gap:72px'));
Webgram_Settings::instance()->update(['spacing_scale'=>'compact']);
check('compact spacing scale shrinks tokens', str_contains(Webgram_CSS_Generator::instance()->get_css(),'--wg-space-4:14px'));
Webgram_Settings::instance()->reset();
echo "\n".($fail?"$fail FAILURE(S)":"ALL PASSED")."\n"; exit($fail?1:0);
