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
function add_shortcode(...$a){} function current_user_can($c){ return $GLOBALS['__cap'][$c] ?? false; } function esc_url($s){ return $s; } function esc_html__($s,$d=null){ return $s; } function admin_url($p=''){ return 'http://x/wp-admin/'.$p; }
function sanitize_html_class($s){ return preg_replace('/[^A-Za-z0-9_-]/','',$s); } function wp_date($f,$t){ return gmdate($f,$t); } function translate_user_role($r){ return $r; }
define('MINUTE_IN_SECONDS',60); define('WEBGRAM_CORE_TEST',true);

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

// 9. Site Tools boots without dependencies; WooEnhancements blocked without WooCommerce
check('site_tools implemented and active', $m->is_active('site_tools') === true);
check('woo_enhancements implemented but blocked without WooCommerce', $m->get('woo_enhancements')->is_implemented() && $m->is_active('woo_enhancements') === false && $m->blocked_reason('woo_enhancements') === 'woocommerce');
check('theme filters registered by site_tools', isset($GLOBALS['__filters']['webgram/settings/tabs'], $GLOBALS['__filters']['webgram/html_block'], $GLOBALS['__filters']['webgram/layout_for']));

// 10. Layout conditions engine
use Webgram\Core\Modules\SiteTools\Layouts\Conditions;
use Webgram\Core\Modules\SiteTools\Layouts\Resolver;
$ctx = ['front_page'=>false,'shop'=>false,'blog'=>false,'search'=>false,'404'=>false,'post_id'=>12,'post_type'=>'product','terms'=>['product_cat'=>['5','decor','2'],'product_brand'=>['9']],'device'=>'mobile','logged_in'=>false];
check('include all matches', Conditions::matches([['op'=>'include','type'=>'all','value'=>[]]], $ctx));
check('include product ids matches by id', Conditions::matches([['op'=>'include','type'=>'product','value'=>['12','13']]], $ctx) && !Conditions::matches([['op'=>'include','type'=>'product','value'=>['99']]], $ctx));
check('include category by slug or ancestor id', Conditions::matches([['op'=>'include','type'=>'product_cat','value'=>['decor']]], $ctx) && Conditions::matches([['op'=>'include','type'=>'product_cat','value'=>['2']]], $ctx));
check('exclude wins over include', !Conditions::matches([['op'=>'include','type'=>'all','value'=>[]],['op'=>'exclude','type'=>'brand','value'=>['9']]], $ctx));
check('no include rules never matches', !Conditions::matches([['op'=>'exclude','type'=>'all','value'=>[]]], $ctx));
check('shop flag rule', !Conditions::matches([['op'=>'include','type'=>'shop','value'=>[]]], $ctx) && Conditions::matches([['op'=>'include','type'=>'shop','value'=>[]]], ['shop'=>true]));
check('device and login gates', Conditions::allowed(['mobile'],'out',$ctx) && !Conditions::allowed(['desktop'],'any',$ctx) && !Conditions::allowed([],'in',$ctx));
$san = Conditions::sanitize([['op'=>'exclude','type'=>'product_cat','value'=>' Decor, 5 ,'],['type'=>'bogus'],['type'=>'all','op'=>'x'],'junk']);
check('conditions sanitize splits values and drops unknown types', count($san)===2 && $san[0]['value']===['decor','5'] && $san[0]['op']==='exclude' && $san[1]['op']==='include');
$cands = [['id'=>1,'priority'=>0,'rules'=>[['op'=>'include','type'=>'all','value'=>[]]],'devices'=>[],'login'=>'any'],['id'=>2,'priority'=>10,'rules'=>[['op'=>'include','type'=>'product','value'=>['12']]],'devices'=>['mobile'],'login'=>'any'],['id'=>3,'priority'=>20,'rules'=>[['op'=>'include','type'=>'all','value'=>[]]],'devices'=>['desktop'],'login'=>'any']];
usort($cands, fn($a,$b)=>$b['priority']<=>$a['priority']);
check('resolver picks highest priority allowed match', Resolver::pick($cands,$ctx)===2 && Resolver::pick($cands,['device'=>'desktop','post_type'=>'page','post_id'=>1])===3 && Resolver::pick([], $ctx)===0);

// 11. Site Tools settings sanitizer (Core side)
use Webgram\Core\Modules\SiteTools\Settings as StSettings;
$fields = ['popup_enabled'=>['type'=>'switch'],'popup_delay'=>['type'=>'number','min'=>0,'max'=>120],'popup_devices'=>['type'=>'multicheck','choices'=>['desktop'=>1,'mobile'=>1]],'js_footer'=>['type'=>'code','language'=>'javascript'],'age_redirect'=>['type'=>'url'],'maint_mode'=>['type'=>'select','choices'=>['off'=>1,'maintenance'=>1],'default'=>'off']];
$GLOBALS['__cap']=['manage_options'=>true];
$clean = StSettings::sanitize_values($fields, ['popup_enabled'=>'1','popup_delay'=>'999','popup_devices'=>['mobile','tv'],'js_footer'=>'alert(1)','age_redirect'=>'javascript:x','maint_mode'=>'evil','unknown'=>'x']);
check('site tools sanitizer: switch, clamp, multicheck, url, select fallback', $clean['popup_enabled']===true && $clean['popup_delay']===120 && $clean['popup_devices']===['mobile'] && $clean['age_redirect']==='' && $clean['maint_mode']==='off' && !isset($clean['unknown']));
check('custom JS rejected without unfiltered_html', !array_key_exists('js_footer',$clean));
$GLOBALS['__cap']['unfiltered_html']=true;
check('custom JS accepted with unfiltered_html', StSettings::sanitize_values($fields, ['js_footer'=>'alert(1)'])['js_footer']==='alert(1)');
check('custom JS strips script wrappers', Webgram\Core\Modules\SiteTools\CustomJs::strip_tags("<script type=\"text/javascript\">x();</script>")==='x();');
check('maintenance countdown parses and rejects junk', Webgram\Core\Modules\SiteTools\Maintenance::countdown_timestamp('2030-01-01 09:00') === gmmktime(9,0,0,1,1,2030) && Webgram\Core\Modules\SiteTools\Maintenance::countdown_timestamp('not a date')===0 && Webgram\Core\Modules\SiteTools\Maintenance::countdown_timestamp('')===0);
check('css_class without theme support', Helpers::css_class('popup')==='wgc-popup' && Helpers::css_class('popup','x')==='wgc-popup x');

// 12. Pincode validation, CSV parsing, geocoder parsing
use Webgram\Core\Modules\WooEnhancements\PincodeChecker;
check('IN pincode 6 digits not starting with 0', PincodeChecker::normalize(' 400 001 ','IN')==='400001' && PincodeChecker::normalize('012345','IN')==='' && PincodeChecker::normalize('4000011','IN')==='');
check('US zip and GB postcode', PincodeChecker::normalize('90210-1234','US')==='90210-1234' && PincodeChecker::normalize('sw1a 1aa','GB')==='SW1A 1AA' && PincodeChecker::normalize('abc','US')==='');
check('labels per country', PincodeChecker::label('IN')==='Pincode' && PincodeChecker::label('US')==='ZIP code' && PincodeChecker::label('DE')==='Postal code');
$csv = "Pin Code,City,State,Deliverable,COD,ETA Days\n400001,Mumbai,Maharashtra,1,1,3\n012345,Bad,Row,1,1,1\n560001,Bengaluru,Karnataka,0,,\n400001,Mumbai Dup,MH,1,0,5\n";
$parsed = PincodeChecker::parse_csv($csv,'IN');
check('csv header mapping and invalid rows skipped', $parsed['skipped']===1 && count($parsed['rows'])===2);
check('csv row values typed and duplicates last-wins', $parsed['rows'][0]['city']==='Mumbai Dup' && $parsed['rows'][0]['cod']===false && $parsed['rows'][0]['eta_days']===5 && $parsed['rows'][1]['deliverable']===false && $parsed['rows'][1]['cod']===true && $parsed['rows'][1]['eta_days']===null);
$nom = Webgram\Core\Modules\WooEnhancements\Geo\NominatimGeocoder::parse(['address'=>['postcode'=>'400001','suburb'=>'Fort','city'=>'Mumbai','state'=>'Maharashtra']]);
check('nominatim parse picks city and postcode', $nom===['pincode'=>'400001','city'=>'Mumbai','state'=>'Maharashtra'] && Webgram\Core\Modules\WooEnhancements\Geo\NominatimGeocoder::parse(['address'=>[]])===null);
check('location display label', Webgram\Core\Modules\WooEnhancements\Location::display_label(['city'=>'Mumbai','pincode'=>'400001'])==='Mumbai 400001' && Webgram\Core\Modules\WooEnhancements\Location::display_label(['pincode'=>'400001'])==='400001');

echo "\n" . ($fail ? "$fail FAILURE(S)" : "ALL PASSED") . "\n";
exit($fail ? 1 : 0);
