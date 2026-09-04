<?php
define('ABSPATH','/tmp/'); define('WEEK_IN_SECONDS',604800);
$GLOBALS['mods']=[]; $GLOBALS['t']=[];
function get_template_directory(){ return __DIR__.'/../webgram-theme'; }
function get_template_directory_uri(){ return 'http://x/wp-content/themes/webgram'; }
function add_action(...$a){} function add_filter(...$a){} function apply_filters($h,$v,...$a){ return $v; }
function get_theme_mod($k,$d=null){ return $GLOBALS['mods'][$k] ?? $d; }
function get_transient($k){ return $GLOBALS['t'][$k] ?? false; } function set_transient($k,$v,$x){ $GLOBALS['t'][$k]=$v; } function delete_transient($k){ unset($GLOBALS['t'][$k]); }
function is_customize_preview(){ return false; } function wp_strip_all_tags($s){ return strip_tags($s); }
function __($s,$d=null){ return $s; } function get_bloginfo($k){ return 'Demo Store'; }
function sanitize_html_class($s){ return preg_replace('/[^A-Za-z0-9_-]/','',$s); }
function is_singular(){return false;} function is_home(){return false;} function is_archive(){return false;} function is_search(){return false;} function is_single(){return false;} function is_rtl(){return false;}
function sanitize_file_name($s){ return preg_replace('/[^a-z0-9\-]/','',$s); } function esc_attr($s){ return htmlspecialchars($s); }
function wp_kses($s,$a){ return $s; }
require get_template_directory().'/functions.php'; // will require all inc files; some define hooks only
$fail=0; function check($l,$c){ global $fail; echo ($c?'PASS':'FAIL')."  $l\n"; if(!$c)$fail++; }
$css = Webgram_CSS_Generator::instance()->get_css();
check('css generator outputs :root block', str_starts_with($css, ':root{--'));
check('primary color token present', str_contains($css, '--wg-color-primary:#a0181f'));
check('container token has px', str_contains($css, '--wg-container-max:1320px'));
check('font stack quoted', str_contains($css, '--wg-font-body:"Inter",system-ui,sans-serif'));
$GLOBALS['mods']['color_primary']='#123456'; $GLOBALS['mods']['radius_scale']='pill'; Webgram_CSS_Generator::instance()->flush();
$css = Webgram_CSS_Generator::instance()->get_css();
check('customizer override applied', str_contains($css,'--wg-color-primary:#123456'));
check('radius scale pill', str_contains($css,'--wg-radius-md:14px'));
$GLOBALS['mods']['color_accent']='</style><script>x</script>'; Webgram_CSS_Generator::instance()->flush();
check('malicious token value dropped', !str_contains(Webgram_CSS_Generator::instance()->get_css(),'script'));
$icon = webgram_icon('cart','extra',false);
check('svg icon injected with class and aria-hidden', str_contains($icon,'class="wg-icon wg-icon--cart extra"') && str_contains($icon,'aria-hidden="true"'));
check('unknown icon returns empty', webgram_icon('../../etc/passwd','',false)==='');
check('default layout container', webgram_layout()==='container');
echo "\n".($fail?"$fail FAILURE(S)":"ALL PASSED")."\n"; exit($fail?1:0);
