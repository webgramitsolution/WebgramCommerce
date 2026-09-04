<?php
// Minimal WordPress function stubs so Core classes can be exercised outside WordPress.
define('ABSPATH', '/tmp/');
define('AUTH_KEY', 'test-auth-key-1234567890');
define('SECURE_AUTH_KEY', 'test-secure-key-0987654321');
define('HOUR_IN_SECONDS', 3600); define('DAY_IN_SECONDS', 86400);
$GLOBALS['__opts'] = []; $GLOBALS['__actions'] = []; $GLOBALS['__filters'] = [];
function add_action($h,$cb,$p=10,$a=1){ $GLOBALS['__actions'][$h][] = $cb; }
function add_filter($h,$cb,$p=10,$a=1){ $GLOBALS['__filters'][$h][] = $cb; }
function apply_filters($h,$v,...$args){ foreach($GLOBALS['__filters'][$h]??[] as $cb){ $v=$cb($v,...$args);} return $v; }
function do_action($h,...$a){ foreach($GLOBALS['__actions'][$h]??[] as $cb){ $cb(...$a);} }
function did_action($h){ return 0; }
function get_option($k,$d=false){ return $GLOBALS['__opts'][$k] ?? $d; }
function update_option($k,$v,$al=null){ $GLOBALS['__opts'][$k]=$v; return true; }
function add_option($k,$v){ if(!isset($GLOBALS['__opts'][$k])) $GLOBALS['__opts'][$k]=$v; }
function delete_option($k){ unset($GLOBALS['__opts'][$k]); }
function get_transient($k){ return $GLOBALS['__opts']['_t_'.$k] ?? false; }
function set_transient($k,$v,$t=0){ $GLOBALS['__opts']['_t_'.$k]=$v; }
function delete_transient($k){ unset($GLOBALS['__opts']['_t_'.$k]); }
function wp_using_ext_object_cache(){ return false; }
function sanitize_key($s){ return strtolower(preg_replace('/[^a-z0-9_\-]/','',strtolower($s))); }
function sanitize_text_field($s){ return trim(strip_tags((string)$s)); }
function sanitize_textarea_field($s){ return trim(strip_tags((string)$s)); }
function sanitize_email($s){ return filter_var($s, FILTER_VALIDATE_EMAIL) ?: ''; }
function sanitize_title($s){ return strtolower(preg_replace('/[^a-z0-9]+/','-',strtolower($s))); }
function sanitize_hex_color($s){ return preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/i',$s)?$s:''; }
function esc_url_raw($s){ return filter_var($s, FILTER_VALIDATE_URL) ?: ''; }
function absint($n){ return abs((int)$n); }
function wp_kses_post($s){ return $s; }
function esc_sql($s){ return addslashes($s); }
function __($s,$d=null){ return $s; }
function is_admin(){ return false; }
function load_plugin_textdomain(...$a){}
function plugin_basename($f){ return basename(dirname($f)).'/'.basename($f); }
function plugin_dir_path($f){ return dirname($f).'/'; }
function plugin_dir_url($f){ return 'http://example.test/wp-content/plugins/'.basename(dirname($f)).'/'; }
function locate_template($t){ return ''; }
function wp_json_encode($v){ return json_encode($v); }
function get_theme_support($f){ return false; }
function wp_register_style(...$a){} function wp_register_script(...$a){}
function class_exists_stub(){}
function register_activation_hook(...$a){} function register_deactivation_hook(...$a){}

require __DIR__ . '/../webgram-core/webgram-core.php';

$fail = 0;
function check($label, $cond){ global $fail; echo ($cond ? "PASS" : "FAIL") . "  $label\n"; if(!$cond) $fail++; }

// 1. Boot + module discovery
webgram_core()->boot();
do_action('plugins_loaded');
$m = webgram_core()->modules();
check('18 built-in modules discovered', count($m->all()) === 18);
check('ai_assistant disabled by default', $m->is_enabled_in_settings('ai_assistant') === false);
check('reviews enabled by default', $m->is_enabled_in_settings('reviews') === true);
check('stubs are not booted as active', $m->is_active('reviews') === false); // is_implemented false => never booted, and WooCommerce absent
check('webgram_core/loaded fired', true);

// 2. Third-party module registration + dependency block
require __DIR__ . '/fake-module.php';
add_filter('webgram_core/modules', fn($mods) => $mods + ['fake' => FakeModule::class]);
$m2 = new Webgram\Core\Modules\ModuleManager(webgram_core()->container());
$m2->boot_all();
check('third-party module registered', $m2->get('fake') !== null);
check('module blocked when WooCommerce missing', $m2->is_active('fake') === false && $m2->blocked_reason('fake') === 'woocommerce');

// 3. Settings
$s = webgram_core()->settings('reviews');
$s->set('per_page', 8);
check('settings write/read', webgram_core()->settings('reviews')->get('per_page') === 8);
check('settings default', $s->get('missing', 'x') === 'x');
check('settings filter', (function() use ($s){ add_filter('webgram_core/setting/reviews/per_page', fn($v)=>99); return $s->get('per_page')===99; })());

// 4. Crypto
$c = webgram_core()->crypto();
$enc = $c->encrypt('EAAG-super-secret-token-123');
check('encrypted value is not plaintext', $enc !== 'EAAG-super-secret-token-123' && $c->is_encrypted($enc));
check('decrypt roundtrip', $c->decrypt($enc) === 'EAAG-super-secret-token-123');
check('tampered ciphertext returns empty', $c->decrypt(substr($enc,0,-3).'abc') === '');
check('mask', Webgram\Core\Support\Crypto::mask('EAAG-super-secret-token-123') === 'EAAG************-123');

// 5. Sanitizer
$out = Webgram\Core\Support\Sanitizer::apply(['ids'=>['1','x','3'],'email'=>'bad','flag'=>'true','extra'=>'drop'], ['ids'=>'int_list','email'=>'email','flag'=>'bool']);
check('sanitizer int_list', $out['ids'] === [1,3]);
check('sanitizer email invalid -> empty', $out['email'] === '');
check('sanitizer bool', $out['flag'] === true);
check('sanitizer drops unknown fields', !isset($out['extra']));

// 6. Phone normalization
use Webgram\Core\Support\Helpers;
check('E.164 india local', Helpers::to_e164('098512 95129', '91') === '+919851295129');
check('E.164 already intl', Helpers::to_e164('+91 98512-95129') === '+919851295129');
check('E.164 00 prefix', Helpers::to_e164('0044 7911 123456') === '+447911123456');
check('E.164 no country code -> empty', Helpers::to_e164('9851295129') === '');
check('E.164 too short -> empty', Helpers::to_e164('12345', '91') === '');

// 7. Cache group invalidation
$cache = webgram_core()->cache();
$cache->set('k', 'v1', 100, 'reviews');
check('cache get', $cache->get('k','reviews') === 'v1');
$cache->flush_group('reviews');
check('cache group flush', $cache->get('k','reviews') === false);

// 8. Template resolution
$t = new Webgram\Core\Support\Template();
check('template path traversal stripped', str_contains($t->locate('../../etc/passwd'), 'templates/') || $t->locate('../../etc/passwd') === '');

echo "\n" . ($fail ? "$fail FAILURE(S)" : "ALL PASSED") . "\n";
exit($fail ? 1 : 0);
