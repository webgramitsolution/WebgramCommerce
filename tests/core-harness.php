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
function add_shortcode(...$a){} function wp_strip_all_tags($s){ return strip_tags((string)$s); } function current_user_can($c){ return $GLOBALS['__cap'][$c] ?? false; } function esc_url($s){ return $s; } function esc_html__($s,$d=null){ return $s; } function admin_url($p=''){ return 'http://x/wp-admin/'.$p; }
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

// 13. Phase 2 pure logic: badges, specifications, contact seller, track order, coupons, recently viewed, bulk inquiry
function wc_price($n){ return '₹'.number_format((float)$n,0); } function wp_unique_id($p=''){ return $p.'1'; } function _n($s,$p,$n,$d=null){ return $n===1?$s:$p; }
use Webgram\Core\Modules\Badges\Module as Badges;
$bs = ['new_days'=>14,'new_text'=>'New','sale_mode'=>'percent','sale_text'=>'Sale','best_threshold'=>50,'best_text'=>'Best seller','low_stock'=>3,'out_of_stock'=>true,'max_badges'=>2];
$now = 1_700_000_000;
$b = Badges::evaluate(['created_ts'=>$now-5*86400,'is_on_sale'=>true,'sale_percent'=>45,'total_sales'=>120,'stock'=>2,'managing_stock'=>true,'in_stock'=>true,'custom_text'=>''], $bs, $now);
check('badges: sale percent then best seller, capped at 2', count($b)===2 && $b[0]['text']==='45% off' && $b[1]['type']==='best');
$b = Badges::evaluate(['created_ts'=>$now-5*86400,'is_on_sale'=>false,'total_sales'=>0,'stock'=>2,'managing_stock'=>true,'in_stock'=>true,'custom_text'=>'Hot','custom_color'=>'#000'], $bs, $now);
check('badges: custom first, then new, low stock dropped by cap', $b[0]['type']==='custom' && $b[1]['type']==='new' && count($b)===2);
$b = Badges::evaluate(['in_stock'=>false,'is_on_sale'=>true,'sale_percent'=>50,'custom_text'=>''], $bs, $now);
check('badges: sold out replaces everything', count($b)===1 && $b[0]['type']==='out');
check('badges: theme sale mode prints no sale badge, old product no new badge', Badges::evaluate(['created_ts'=>$now-40*86400,'is_on_sale'=>true,'sale_percent'=>20,'in_stock'=>true,'total_sales'=>1], ['sale_mode'=>'theme']+$bs, $now)===[]);
use Webgram\Core\Modules\WooEnhancements\Specifications;
$rows = Specifications::merge([['label'=>'Weight','value'=>'1 kg'],['label'=>'Color','value'=>'Red']], [['label'=>'weight','value'=>'1.2 kg'],['label'=>'','value'=>'x'],['label'=>'Best offer','value'=>'1kg, 5kg']]);
check('specifications merge: custom overrides attribute case-insensitively, empty dropped', count($rows)===3 && $rows[0]['value']==='1.2 kg' && $rows[2]['label']==='Best offer');
check('specifications source filters', count(Specifications::merge([['label'=>'A','value'=>'1']],[['label'=>'B','value'=>'2']],'attributes'))===1 && Specifications::merge([['label'=>'A','value'=>'1']],[['label'=>'B','value'=>'2']],'custom')[0]['label']==='B');
check('specifications sanitize rows', Specifications::sanitize_rows([['label'=>'<b>A</b>','value'=>'1'],['label'=>'','value'=>'2'],'junk'])===[['label'=>'A','value'=>'1']]);
use Webgram\Core\Modules\WooEnhancements\ContactSeller;
check('whatsapp link strips formatting and encodes message', ContactSeller::whatsapp_link('+91 98765-43210','Hi & bye')==='https://wa.me/919876543210?text=Hi%20%26%20bye' && ContactSeller::whatsapp_link('abc','x')==='');
use Webgram\Core\Modules\WooEnhancements\TrackOrder;
check('track order: email match is case-insensitive', TrackOrder::contact_matches('Buyer@Example.com','buyer@example.com','') && !TrackOrder::contact_matches('other@example.com','buyer@example.com',''));
check('track order: phone match via E.164 and local digits', TrackOrder::contact_matches('098765 43210','', '+91 98765 43210') && TrackOrder::contact_matches('+919876543210','','9876543210') && !TrackOrder::contact_matches('12345','','9876543210'));
$tl = TrackOrder::timeline('processing', ['placed'=>'1 Sep']);
check('track order timeline: processing reaches confirmed', $tl[0]['done'] && $tl[1]['done'] && $tl[1]['current'] && !$tl[2]['done'] && $tl[0]['date']==='1 Sep' && count($tl)===6);
check('track order timeline: completed marks all', count(array_filter(TrackOrder::timeline('completed'), fn($s)=>$s['done']))===6 && count(array_filter(TrackOrder::timeline('pending'), fn($s)=>$s['done']))===0);
use Webgram\Core\Modules\Coupons\OfferProgress;
use Webgram\Core\Modules\Coupons\Module as Coupons;
$ms = OfferProgress::parse("amount|799|15% OFF|HYP15\nqty|2|Buy 2 @ 799|BUY2\nbad line\nqty|0|zero|X\nqty|3|Buy 3 @ 1149|");
check('milestones parse: sorted by threshold, invalid dropped', count($ms)===3 && $ms[0]['type']==='qty' && $ms[0]['threshold']==2.0 && $ms[2]['code']==='HYP15');
$pr = OfferProgress::compute($ms, 500.0, 1);
check('progress: nothing achieved, next is qty 2, percent partial', $pr['achieved']===null && $pr['next']['label']==='Buy 2 @ 799' && $pr['percent']>0 && $pr['percent']<34 && str_contains($pr['message'],'Add 1 more'));
$pr = OfferProgress::compute($ms, 850.0, 2);
check('progress: qty 2 and amount 799 done, next qty 3', $pr['achieved']['code']==='HYP15' && $pr['next']['type']==='qty' && str_contains($pr['message'],'Unlocked 15% OFF | Code: HYP15'));
check('progress: everything reached gives 100', OfferProgress::compute($ms, 2000.0, 5)['percent']===100 && OfferProgress::compute([], 10.0, 1)['percent']===0);
check('coupon headline', Coupons::headline('percent',20.0,false)==='FLAT 20% OFF' && Coupons::headline('fixed_cart',100.0,false)==='FLAT ₹100 OFF' && Coupons::headline('x',0,true)==='FREE SHIPPING' && Coupons::headline('percent',20.0,false,'Festive deal')==='Festive deal');
use Webgram\Core\Modules\WooEnhancements\RecentlyViewed;
check('recently viewed push: dedupe, prepend, cap', RecentlyViewed::push([3,2,1],2,3)===[2,3,1] && count(RecentlyViewed::push(range(1,25),99))===20 && RecentlyViewed::push([],7)[0]===7);
$bi = new Webgram\Core\Modules\WooEnhancements\BulkInquiry($m->get('woo_enhancements'));
check('bulk inquiry: honeypot rejected', $bi->validate(['website'=>'spam','name'=>'A','phone'=>'9876543210','email'=>'a@b.co','quantity'=>5])['ok']===false);
check('bulk inquiry: invalid phone rejected, valid accepted with E.164', $bi->validate(['website'=>'','name'=>'A','phone'=>'123','email'=>'a@b.co','quantity'=>5])['ok']===false && $bi->validate(['website'=>'','name'=>'A','phone'=>'98765 43210','email'=>'a@b.co','quantity'=>5])['phone']==='+919876543210');
check('bulk inquiry: quantity required', $bi->validate(['website'=>'','name'=>'A','phone'=>'9876543210','email'=>'a@b.co','quantity'=>0])['ok']===false);


// Phase 3: cart recommendations and help FAQs
use Webgram\Core\Modules\WooEnhancements\CartRecommendations;
check('cart recommendations pick: cross sells first, cart items excluded, deduped, capped', CartRecommendations::pick([5,6,'7'],[6,8,9,10],[7],4)===[5,6,8,9] && CartRecommendations::pick([],[0,-1,3],[],5)===[3] && CartRecommendations::pick([],[],[1],3)===[]);
$faqs = Webgram\Core\Modules\SiteTools\Module::parse_faqs("How long is delivery?\nUsually 3 to 5 days.\nMetro cities faster.\n\n\nOnly a question\n\nReturns?\r\n7 day returns.");
check('help faqs parse: blank line blocks, first line question, single lines dropped', count($faqs)===2 && $faqs[0]['q']==='How long is delivery?' && $faqs[0]['a']==="Usually 3 to 5 days.\nMetro cities faster." && $faqs[1]['a']==='7 day returns.');
check('help faqs parse: empty input', Webgram\Core\Modules\SiteTools\Module::parse_faqs('')===[]);

// Phase 4: lists (wishlist, compare), share tokens, reviews maths, media validation, compat, schema, compare table
if ( ! defined( 'MB_IN_BYTES' ) ) { define( 'MB_IN_BYTES', 1048576 ); }
if ( ! function_exists( 'number_format_i18n' ) ) { function number_format_i18n( $n, $d = 0 ) { return number_format( (float) $n, $d ); } }
use Webgram\Core\Support\Lists\ProductList;
use Webgram\Core\Support\Lists\CookieStorage;
use Webgram\Core\Support\Lists\ShareToken;
use Webgram\Core\Modules\Reviews\Summary;
use Webgram\Core\Modules\Reviews\Query;
use Webgram\Core\Modules\Reviews\Media;
use Webgram\Core\Modules\Reviews\Compat;
use Webgram\Core\Modules\Reviews\Schema;
use Webgram\Core\Modules\Compare\Table;
check('phase 4 modules implemented, blocked only by WooCommerce', $m->get('reviews')->is_implemented() && $m->get('wishlist')->is_implemented() && $m->get('compare')->is_implemented() && $m->blocked_reason('wishlist') === 'woocommerce');
$mem = new class implements Webgram\Core\Support\Lists\StorageInterface { public array $ids = []; public function get(): array { return $this->ids; } public function set(array $ids): void { $this->ids = $ids; } };
$list = new ProductList($mem, 3);
check('product list: add prepends, dedupes, toggles, caps at max', $list->add(5) && $list->add(6) && $list->ids() === [6,5] && $list->toggle(5) === 'removed' && $list->toggle(7) === 'added' && $list->add(8) && $list->toggle(9) === 'full' && $list->count() === 3 && $list->is_full());
$list->merge([5, 8, 11]);
check('product list: merge keeps existing first and respects cap', $list->ids() === [8,7,6]);
check('product list normalize drops junk and dupes', ProductList::normalize(['3','x',0,-2,3,'4']) === [3,4]);
$signer = fn(string $p) => hash_hmac('sha256', $p, 'k');
$packed = CookieStorage::pack([12, 7], $signer);
check('cookie storage round trip', CookieStorage::unpack($packed, $signer) === [12,7]);
check('cookie storage rejects tampering and foreign key', CookieStorage::unpack(substr($packed, 0, -1) . 'x', $signer) === [] && CookieStorage::unpack($packed, fn($p) => hash_hmac('sha256', $p, 'other')) === [] && CookieStorage::unpack('', $signer) === []);
$tok = ShareToken::create([3, 4], 2000, $signer);
check('share token parses before expiry, rejects after and when tampered', ShareToken::parse($tok, 1999, $signer) === [3,4] && ShareToken::parse($tok, 2001, $signer) === null && ShareToken::parse('a.b', 1, $signer) === null && ShareToken::parse(str_replace('.', 'x.', $tok), 1, $signer) === null);
$sum = Summary::compute([5 => 200, 4 => 40, 3 => 10, 1 => 6]);
check('summary compute: average, total, ordered rows with percents', $sum['total'] === 256 && $sum['average'] === 4.7 && $sum['rows'][0]['stars'] === 5 && $sum['rows'][0]['percent'] === 78 && $sum['rows'][3]['count'] === 0 && count($sum['rows']) === 5);
check('summary compute empty and given average', Summary::compute([])['average'] === 0.0 && Summary::compute([5 => 1], 4.25)['average'] === 4.3);
check('summary showing labels', Summary::showing(1, 4, 256) === ['from' => 1, 'to' => 4, 'total' => 256] && Summary::showing(3, 4, 10) === ['from' => 9, 'to' => 10, 'total' => 10] && Summary::showing(1, 4, 0)['to'] === 0);
$p = Query::params(['sort' => 'evil', 'stars' => '9', 'media' => '1', 'page' => '0', 'per_page' => '500'], 4);
check('query params normalized', $p === ['sort' => 'newest', 'stars' => 0, 'media' => true, 'page' => 1, 'per_page' => 50]);
$a = Query::args(12, Query::params(['sort' => 'highest', 'stars' => 4, 'page' => 2], 4));
check('query args: rating filter, rating sort clause, offset', $a['post_id'] === 12 && $a['offset'] === 4 && $a['meta_query']['rating_filter']['value'] === 4 && isset($a['meta_query']['rating_sort']) && $a['orderby'] === ['rating_sort' => 'DESC', 'comment_date_gmt' => 'DESC']);
$a = Query::args(12, Query::params(['sort' => 'helpful'], 4));
check('query args: helpful sort uses EXISTS or NOT EXISTS so unvoted reviews stay', isset($a['meta_query'][0]['helpful_sort'], $a['meta_query'][0]['helpful_none']) && $a['orderby']['helpful_sort'] === 'DESC');
check('query args: newest has no meta query', Query::args(1, Query::params([], 4))['meta_query'] === []);
$mimes = Media::IMAGE_MIMES + Media::VIDEO_MIMES;
check('media validate: ok jpg, bad ext, oversize, wrong mime, no file', Media::validate('a.JPG', 'image/jpeg', 1000, 0, $mimes, 8 * MB_IN_BYTES) === '' && Media::validate('a.exe', 'image/jpeg', 1000, 0, $mimes, 8 * MB_IN_BYTES) !== '' && Media::validate('a.png', 'image/png', 9 * MB_IN_BYTES, 0, $mimes, 8 * MB_IN_BYTES) !== '' && Media::validate('a.mp4', 'video/mp4', 10, 0, Media::IMAGE_MIMES, 8 * MB_IN_BYTES) !== '' && Media::validate('', '', 0, 4, $mimes, 1) === '');
check('compat detects known review plugins', Compat::detect(['woocommerce/woocommerce.php', 'judgeme-product-reviews-woocommerce/judgeme.php']) === 'Judge.me' && Compat::detect(['customer-reviews-woocommerce/ivole.php']) !== '' && Compat::detect(['woocommerce/woocommerce.php']) === '');
$schema = Schema::merge(['description' => 'x', 'image' => ['keep']], 'Great', '<p>Body</p>', ['a.jpg']);
check('schema merge adds name and reviewBody, never overwrites', $schema['name'] === 'Great' && $schema['reviewBody'] === 'Body' && $schema['image'] === ['keep'] && Schema::merge(['name' => 'Old'], 'New', '', ['b.jpg'])['name'] === 'Old' && Schema::merge([], '', '', ['b.jpg'])['image'] === ['b.jpg']);
check('compare table: differences and attribute union', Table::differs(['Red', ' red ']) === false && Table::differs(['Red', 'Blue']) && Table::differs(['', 'x']) && Table::attribute_labels([['Color' => 'Red', 'Size' => 'M'], ['Size' => 'L', 'Material' => 'Cotton']]) === ['Color', 'Size', 'Material']);

// Phase 5: product query, trending, slider data, Instagram API mapping, section registry, blocks
use Webgram\Core\Support\ProductQuery;
use Webgram\Core\Support\Trending;
use Webgram\Core\Modules\Slider\Slides;
use Webgram\Core\Modules\Slider\Renderer as SliderRenderer;
use Webgram\Core\Modules\Instagram\Api as IgApi;
use Webgram\Core\Modules\Instagram\Module as IgModule;
use Webgram\Core\Modules\Integrations\Registry;
use Webgram\Core\Modules\Integrations\Blocks;
if ( ! function_exists( 'shortcode_exists' ) ) { function shortcode_exists( $t ) { return false; } }
check('phase 5 modules implemented and booted without WooCommerce', $m->get('slider')->is_implemented() && $m->get('instagram')->is_implemented() && $m->get('integrations')->is_implemented() && $m->is_active('slider') && $m->is_active('integrations'));
$pq = ProductQuery::normalize(['source' => 'nope', 'limit' => '999', 'category' => 'Decor, wall-art ', 'ids' => '5,x,7', 'in_stock' => '0']);
check('product query normalize: unknown source, limit cap, slug lists, ids, in_stock', $pq['source'] === 'recent' && $pq['limit'] === 48 && $pq['category'] === ['decor', 'wall-art'] && $pq['ids'] === [5, 7] && $pq['in_stock'] === false);
$pa = ProductQuery::args(ProductQuery::normalize(['source' => 'best_selling', 'limit' => 4]));
check('product query args: best selling orders by total_sales, in stock only', $pa['meta_key'] === 'total_sales' && $pa['orderby'] === 'meta_value_num' && $pa['stock_status'] === 'instock' && $pa['limit'] === 4 && $pa['status'] === 'publish');
$pa = ProductQuery::args(ProductQuery::normalize(['source' => 'ids', 'ids' => [9, 3]]));
check('product query args: ids keep order and ignore stock', $pa['include'] === [9, 3] && $pa['orderby'] === 'post__in' && ! isset($pa['stock_status']));
check('product query args: trending uses trend score meta, empty ids source is safe', ProductQuery::args(ProductQuery::normalize(['source' => 'trending']))['meta_key'] === '_wg_trend_score' && ProductQuery::args(ProductQuery::normalize(['source' => 'ids']))['include'] === [0]);
$scores = Trending::scores([10 => [0 => 20], 11 => [13 => 20]], [11 => [0 => 2]], 14);
check('trending scores: recent views beat old views, sales weigh 5x, sorted desc', array_keys($scores)[0] === 10 && $scores[10] === 20.0 && $scores[11] > 10 && $scores[11] < 20);
$sl = Slides::sanitize([['image' => '12', 'heading' => '<b>Hi</b>', 'align' => 'weird', 'overlay_color' => 'red', 'overlay_opacity' => '250', 'animation' => 'zoom', 'benefits' => "truck|Free ship\nSecure\n\nx|1\ny|2\nz|3"], ['heading' => ''], 'junk']);
check('slides sanitize: strips html, validates enums, clamps opacity, parses benefits (max 4), drops empty', count($sl) === 1 && $sl[0]['heading'] === 'Hi' && $sl[0]['align'] === 'left' && $sl[0]['overlay_color'] === '' && $sl[0]['overlay_opacity'] === 100 && $sl[0]['animation'] === 'zoom' && count($sl[0]['benefits']) === 4 && $sl[0]['benefits'][1] === ['icon' => 'check', 'text' => 'Secure']);
$ss = Slides::sanitize_settings(['delay' => '100', 'ratio' => '21 / 9', 'ratio_mobile' => 'bad', 'effect' => 'cube', 'height_mode' => 'viewport']);
check('slider settings sanitize: delay floor, ratio formats, fallbacks', $ss['delay'] === 1000 && $ss['ratio'] === '21:9' && $ss['ratio_mobile'] === '4:5' && $ss['effect'] === 'fade' && $ss['height_mode'] === 'viewport' && $ss['autoplay'] === false);
check('slider ratio css and per-device sources fallback', Slides::ratio_css('16:6') === '16 / 6' && SliderRenderer::sources(['image' => 1, 'image_tablet' => 0, 'image_mobile' => 3]) === ['desktop' => 1, 'tablet' => 1, 'mobile' => 3] && SliderRenderer::sources(['image' => 1, 'image_tablet' => 2]) === ['desktop' => 1, 'tablet' => 2, 'mobile' => 2] && str_contains(SliderRenderer::inline_style($ss), '--wgc-slider-ratio:21 / 9;'));
$ig = IgApi::normalize(['data' => [['id' => '1', 'media_type' => 'VIDEO', 'media_url' => 'https://v/mp4', 'thumbnail_url' => 'https://i/1.jpg', 'permalink' => 'https://instagram.com/p/1', 'caption' => ' Hi '], ['id' => '2', 'media_type' => 'IMAGE', 'media_url' => 'http://insecure/2.jpg', 'permalink' => 'https://instagram.com/p/2'], ['id' => '3', 'media_type' => 'IMAGE', 'media_url' => 'https://i/3.jpg', 'permalink' => 'https://instagram.com/p/3'], 'junk']], 5);
check('instagram normalize: video uses thumbnail, insecure urls dropped, captions trimmed', count($ig) === 2 && $ig[0]['type'] === 'video' && $ig[0]['image'] === 'https://i/1.jpg' && $ig[0]['caption'] === 'Hi' && $ig[1]['id'] === '3');
check('instagram media url and limit cap', IgApi::media_url('v21.0', '123', 99) === 'https://graph.facebook.com/v21.0/123/media?fields=' . IgApi::FIELDS . '&limit=50');
check('instagram manual lines parse', IgModule::parse_lines("45 | https://a.b | Hello\n\nhttps://x/y.jpg\n| skip") === [['image' => '45', 'link' => 'https://a.b', 'caption' => 'Hello'], ['image' => 'https://x/y.jpg', 'link' => '', 'caption' => '']]);
$controls = Registry::normalize_controls(['limit' => ['type' => 'number', 'min' => 1, 'max' => 10, 'default' => 4], 'on' => ['type' => 'switch', 'default' => true], 'layout' => ['type' => 'select', 'options' => ['grid' => 'G', 'band' => 'B'], 'default' => 'grid'], 'cats' => ['type' => 'category'], 'rows' => ['type' => 'repeater', 'max' => 2, 'fields' => ['t' => ['type' => 'text'], 'n' => ['type' => 'number', 'default' => 1]]], 'bad' => ['type' => 'nope']]);
$sa = Registry::sanitize_args($controls, ['limit' => '50', 'on' => 'yes', 'layout' => 'evil', 'cats' => 'Decor, Wall Art', 'rows' => [['t' => '<i>a</i>', 'n' => 'x'], ['t' => 'b'], ['t' => 'c']], 'extra' => 1]);
check('registry sanitize: clamps, switch strings, select fallback, slugs, repeater max and nested defaults, unknown dropped', $sa['limit'] === 10 && $sa['on'] === true && $sa['layout'] === 'grid' && $sa['cats'] === ['decor', 'wall-art'] && count($sa['rows']) === 2 && $sa['rows'][0]['t'] === 'a' && $sa['rows'][0]['n'] === 1 && $sa['rows'][1]['n'] === 1 && ! isset($sa['extra']) && $controls['bad']['type'] === 'text');
$ba = Blocks::attributes($controls);
check('block attributes derived from controls', $ba['limit'] === ['type' => 'number', 'default' => 4] && $ba['on'] === ['type' => 'boolean', 'default' => true] && $ba['cats']['type'] === 'array' && $ba['layout'] === ['type' => 'string', 'default' => 'grid'] && isset($ba['align']));
$reg = webgram_core()->modules()->get('integrations')->registry();
check('registry collects core, theme-style and module definitions with defaults', isset($reg->all()['product_grid'], $reg->all()['categories'], $reg->all()['testimonials'], $reg->all()['slider'], $reg->all()['instagram'], $reg->all()['product_title']) && $reg->get('trending')['controls']['source']['default'] === 'trending' && $reg->get('best_sellers')['controls']['layout']['default'] === 'band');
check('registry render of unknown id is empty and unknown control type falls back to text', $reg->render('does_not_exist', []) === '' && Blocks::dashicon('eicon-unknown') === 'layout');

// Phase 6: reels sources, voice engines, AI assistant (providers, tools, orchestration helpers, session)
use Webgram\Core\Modules\Reels\Sources;
use Webgram\Core\Modules\AiAssistant\Tools as AiTools;
use Webgram\Core\Modules\AiAssistant\Assistant;
use Webgram\Core\Modules\AiAssistant\Session as AiSession;
use Webgram\Core\Modules\AiAssistant\Providers\RuleBasedProvider;
use Webgram\Core\Modules\AiAssistant\Providers\AnthropicProvider;
use Webgram\Core\Modules\AiAssistant\Providers\OpenAiProvider;
use Webgram\Core\Modules\AiAssistant\Providers\GeminiProvider;
if ( ! function_exists( 'wp_rand' ) ) { function wp_rand( $a = 0, $b = 0 ) { return random_int( $a, $b ); } }
check('phase 6 modules implemented, voice search boots without WooCommerce, assistant off by default', $m->get('reels')->is_implemented() && $m->get('voice_search')->is_implemented() && $m->get('ai_assistant')->is_implemented() && $m->is_active('voice_search') && $m->is_enabled_in_settings('ai_assistant') === false);
check('reels sources: detect mp4, youtube shorts, vimeo, cloudflare, bunny, unknown', Sources::detect('https://cdn.x/clip.mp4?x=1') === 'mp4' && Sources::detect('https://youtube.com/shorts/AbC123xyz') === 'youtube' && Sources::detect('https://vimeo.com/123456') === 'vimeo' && Sources::detect('https://customer-abc.cloudflarestream.com/0123456789abcdef0123456789abcdef/watch') === 'cloudflare' && Sources::detect('https://iframe.mediadelivery.net/play/1234/aaaa-bbbb') === 'bunny' && Sources::detect('https://example.com/page') === '' && Sources::detect('') === '');
$yt = Sources::embed('https://www.youtube.com/shorts/AbC123xyz');
check('reels embed: youtube nocookie iframe with loop, vimeo id, mp4 video', $yt['type'] === 'iframe' && str_contains($yt['src'], 'youtube-nocookie.com/embed/AbC123xyz') && str_contains($yt['src'], 'playlist=AbC123xyz') && Sources::embed('https://vimeo.com/video/987')['src'] === 'https://player.vimeo.com/video/987?autoplay=1&muted=1&loop=1&background=1&playsinline=1' && Sources::embed('https://a/b.webm') === ['type' => 'video', 'src' => 'https://a/b.webm', 'source' => 'mp4'] && Sources::embed('https://youtube.com/') === []);
check('ai session key validation', AiSession::valid(str_repeat('a', 64)) && ! AiSession::valid(str_repeat('A', 64)) && ! AiSession::valid('abc') && AiSession::valid(AiSession::generate()) && strlen(AiSession::hash('x')) === 40);
$schema = ['type' => 'object', 'properties' => ['query' => ['type' => 'string'], 'max_price' => ['type' => 'number'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 6], 'sort' => ['type' => 'string', 'enum' => ['relevance', 'price_asc']]], 'required' => ['query']];
$args = AiTools::sanitize_arguments($schema, ['query' => '<b>lamps</b>', 'max_price' => '2000', 'limit' => 99, 'sort' => 'evil', 'extra' => 1, 'unused' => null]);
check('ai tool arguments: strips html, casts numbers, clamps integers, enum fallback, drops unknown', $args === ['query' => 'lamps', 'max_price' => 2000.0, 'limit' => 6, 'sort' => 'relevance']);
$i = RuleBasedProvider::intent('Show me wall decor under 2000');
check('rule based intent: product search with max price and cleaned query', $i['tool'] === 'search_products' && $i['args']['max_price'] === 2000.0 && $i['args']['query'] === 'wall decor');
check('rule based intent: coupons, best sellers, order, store info, greeting', RuleBasedProvider::intent('any coupons today?')['tool'] === 'active_coupons' && RuleBasedProvider::intent('what are your best sellers')['tool'] === 'best_sellers' && RuleBasedProvider::intent('where is my order #1234')['args']['order_id'] === '1234' && RuleBasedProvider::intent('what is your return policy')['tool'] === 'store_info' && RuleBasedProvider::intent('hello')['text'] === 'greeting' && RuleBasedProvider::intent('cheapest lamps between 500 and 900')['args']['sort'] === 'price_asc' && RuleBasedProvider::intent('cheapest lamps between 500 and 900')['args']['min_price'] === 500.0);
check('rule based faq match: contained question and word overlap', RuleBasedProvider::faq_match('Do you ship internationally?', [['q' => 'ship internationally', 'a' => 'Yes']]) === 'Yes' && RuleBasedProvider::faq_match('how long does delivery take to mumbai', [['q' => 'How long does delivery take?', 'a' => '3 days'], ['q' => 'Gift wrap?', 'a' => 'No']]) === '3 days' && RuleBasedProvider::faq_match('random words here', [['q' => 'Gift wrap?', 'a' => 'No']]) === null);
$rb = new RuleBasedProvider([], 'Demo');
$r = $rb->complete([['role' => 'system', 'content' => 'x'], ['role' => 'user', 'content' => 'best sellers']], [['name' => 'best_sellers']], []);
check('rule based provider emits a tool call, then composes from the tool result', $r->has_tool_calls() && $r->tool_calls[0]['name'] === 'best_sellers' && str_contains($rb->complete([['role' => 'user', 'content' => 'x'], ['role' => 'assistant', 'content' => '', 'tool_calls' => []], ['role' => 'tool', 'tool_call_id' => '1', 'name' => 'search_products', 'content' => '{"products":[{"id":1}],"count":1}']], [], [])->text, '1 product') && str_contains(RuleBasedProvider::compose('order_status', ['error' => 'login_required']), 'log in'));
check('rule based provider refuses unknown tools and greets', ! $rb->complete([['role' => 'user', 'content' => 'any coupon?']], [['name' => 'search_products']], [])->has_tool_calls() && str_contains($rb->complete([['role' => 'user', 'content' => 'hi']], [], [])->text, 'Demo'));
$msgs = [['role' => 'system', 'content' => 'SYS'], ['role' => 'user', 'content' => 'lamps'], ['role' => 'assistant', 'content' => '', 'tool_calls' => [['id' => 't1', 'name' => 'search_products', 'arguments' => ['query' => 'lamps']]]], ['role' => 'tool', 'tool_call_id' => 't1', 'name' => 'search_products', 'content' => '{"count":0}']];
$tools = [['name' => 'search_products', 'description' => 'd', 'parameters' => $schema]];
$req = (new AnthropicProvider('sk-test'))->build_request($msgs, $tools, ['model' => 'claude-opus-5']);
check('anthropic request: messages endpoint, system separated, tool_use and tool_result blocks, auto tool choice, low effort, fallbacks on opus 5', $req['url'] === 'https://api.anthropic.com/v1/messages' && $req['headers']['x-api-key'] === 'sk-test' && $req['headers']['anthropic-version'] === '2023-06-01' && $req['body']['system'] === 'SYS' && $req['body']['messages'][0] === ['role' => 'user', 'content' => 'lamps'] && $req['body']['messages'][1]['content'][0]['type'] === 'tool_use' && $req['body']['messages'][2]['content'][0]['type'] === 'tool_result' && $req['body']['messages'][2]['content'][0]['tool_use_id'] === 't1' && $req['body']['tool_choice'] === ['type' => 'auto'] && $req['body']['tools'][0]['input_schema']['required'] === ['query'] && $req['body']['output_config']['effort'] === 'low' && $req['body']['fallbacks'] === 'default' && $req['headers']['anthropic-beta'] === 'server-side-fallback-2026-07-01' && ! isset($req['body']['thinking']));
check('anthropic request: no fallbacks header on other models', ! isset((new AnthropicProvider('k'))->build_request($msgs, [], ['model' => 'claude-haiku-4-5'])['headers']['anthropic-beta']));
$res = (new AnthropicProvider('k'))->parse_response(['content' => [['type' => 'text', 'text' => 'Sure. '], ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'search_products', 'input' => ['query' => 'lamps']]], 'stop_reason' => 'tool_use', 'usage' => ['input_tokens' => 10, 'output_tokens' => 5]]);
check('anthropic response: text plus tool call with decoded arguments and usage; refusal flagged', $res->text === 'Sure.' && $res->tool_calls[0] === ['id' => 'tu_1', 'name' => 'search_products', 'arguments' => ['query' => 'lamps']] && $res->usage === ['input' => 10, 'output' => 5] && (new AnthropicProvider('k'))->parse_response(['content' => [], 'stop_reason' => 'refusal'])->refused);
$oreq = (new OpenAiProvider('k'))->build_request($msgs, $tools, []);
$ores = (new OpenAiProvider('k'))->parse_response(['choices' => [['message' => ['content' => null, 'tool_calls' => [['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'search_products', 'arguments' => '{"query":"lamps","max_price":2000}']]]], 'finish_reason' => 'tool_calls']], 'usage' => ['prompt_tokens' => 3, 'completion_tokens' => 2]]);
check('openai request and response shapes', $oreq['body']['messages'][0]['role'] === 'system' && $oreq['body']['messages'][2]['tool_calls'][0]['function']['arguments'] === '{"query":"lamps"}' && $oreq['body']['messages'][3]['tool_call_id'] === 't1' && $oreq['body']['tools'][0]['type'] === 'function' && $oreq['headers']['Authorization'] === 'Bearer k' && $ores->tool_calls[0]['arguments'] === ['query' => 'lamps', 'max_price' => 2000] && $ores->text === '');
$greq = (new GeminiProvider('k'))->build_request($msgs, $tools, []);
$gres = (new GeminiProvider('k'))->parse_response(['candidates' => [['content' => ['parts' => [['text' => 'Hi'], ['functionCall' => ['name' => 'best_sellers', 'args' => ['limit' => 3]]]]], 'finishReason' => 'STOP']]]);
check('gemini request and response shapes', str_contains($greq['url'], 'models/gemini-2.0-flash:generateContent') && $greq['body']['system_instruction']['parts'][0]['text'] === 'SYS' && $greq['body']['contents'][1]['role'] === 'model' && isset($greq['body']['contents'][2]['parts'][0]['functionResponse']) && $greq['body']['tools'][0]['function_declarations'][0]['name'] === 'search_products' && $gres->text === 'Hi' && $gres->tool_calls[0]['name'] === 'best_sellers' && $gres->tool_calls[0]['arguments'] === ['limit' => 3]);
$sys = Assistant::system_prompt('Demo Store', 'INR', 'We ship from Mumbai.', 'Maya');
check('assistant system prompt includes name, store, currency, rules and notes', str_starts_with($sys, 'You are Maya, the shopping assistant of the online store "Demo Store"') && str_contains($sys, 'Prices are in INR') && str_ends_with($sys, 'We ship from Mumbai.'));
check('assistant history mapping skips empty assistant rows and tool rows', Assistant::history_to_messages([['role' => 'user', 'content' => 'a'], ['role' => 'assistant', 'content' => ''], ['role' => 'tool', 'content' => 'x'], ['role' => 'assistant', 'content' => 'b']]) === [['role' => 'user', 'content' => 'a'], ['role' => 'assistant', 'content' => 'b']]);
check('assistant product collection dedupes across tool results and caps', count(Assistant::collect_products([['products' => [['id' => 1], ['id' => 2]]], ['products' => [['id' => 2], ['id' => 3]]]])) === 3 && count(Assistant::collect_products([['products' => array_map(fn($i) => ['id' => $i], range(1, 10))]], 6)) === 6);

// Phase 7: invoice numbering and data helpers, email branding, analytics, notifications helpers
use Webgram\Core\Modules\Invoice\Numbering;
use Webgram\Core\Modules\Invoice\InvoiceData;
use Webgram\Core\Modules\Invoice\Storage as InvoiceStorage;
use Webgram\Core\Modules\Emails\Branding;
use Webgram\Core\Modules\Analytics\Collector;
use Webgram\Core\Modules\Analytics\Reports;
use Webgram\Core\Modules\Notifications\Events as NotifEvents;
use Webgram\Core\Modules\Notifications\Templates as NotifTemplates;
use Webgram\Core\Modules\Notifications\OptIn;
use Webgram\Core\Modules\Notifications\PhoneNumber;
use Webgram\Core\Modules\Notifications\Queue as NotifQueue;
use Webgram\Core\Modules\Notifications\Log as NotifLog;
use Webgram\Core\Modules\Notifications\Channels\WhatsAppCloudChannel;
use Webgram\Core\Modules\Notifications\Channels\EmailChannel;
use Webgram\Core\Modules\Notifications\Rest\WhatsAppController;
if ( ! function_exists( 'is_email' ) ) { function is_email( $e ) { return (bool) filter_var( $e, FILTER_VALIDATE_EMAIL ); } }
if ( ! function_exists( '_x' ) ) { function _x( $s, $c, $d = null ) { return $s; } }
check('phase 7 modules implemented and blocked only by WooCommerce', $m->get('invoice')->is_implemented() && $m->get('emails')->is_implemented() && $m->get('notifications')->is_implemented() && $m->get('analytics')->is_implemented() && $m->blocked_reason('invoice') === 'woocommerce');
$d = new DateTimeImmutable('2026-09-04 10:00:00', new DateTimeZone('UTC'));
check('invoice financial year and period start', Numbering::financial_year($d) === '2026-27' && Numbering::financial_year(new DateTimeImmutable('2026-02-10')) === '2025-26' && Numbering::financial_year($d, 1) === '2026' && Numbering::period_start($d, true) === '2026-04-01 00:00:00' && Numbering::period_start(new DateTimeImmutable('2026-02-10'), true) === '2025-04-01 00:00:00' && Numbering::period_start($d, false) === '2026-01-01 00:00:00');
check('invoice number format placeholders, padding and sanitizing', Numbering::format('{prefix}{fy}-{number}', 123, 6, $d, 'WG-') === 'WG-2026-27-000123' && Numbering::format('INV/{yyyy}/{mm}/{number}{suffix}', 7, 3, $d, '', '-A') === 'INV/2026/09/007-A' && Numbering::format('', 1, 4, $d, 'X') === 'X2026-27-0001' && Numbering::format('{prefix} {number}', 5, 0, $d, 'A B') === 'A-B-5');
$taxes = InvoiceData::classify_taxes([['label' => 'CGST 9%', 'amount' => 9.0, 'rate' => 9], ['label' => 'SGST', 'amount' => 9.0], ['label' => 'Tax', 'amount' => 1.0]]);
check('invoice tax classification and payment line', $taxes[0]['kind'] === 'CGST' && $taxes[1]['kind'] === 'SGST' && $taxes[2]['kind'] === 'other' && InvoiceData::payment_line('Razorpay (UPI)', 'pay_123', true) === 'Paid via Razorpay (UPI) (Transaction ID: pay_123)' && InvoiceData::payment_line('COD', '', false) === 'Payment pending via COD');
check('invoice storage filename', InvoiceStorage::filename('WG-2026-27/000123') === 'invoice-WG-2026-27-000123.pdf' && InvoiceStorage::filename('a b', 'html') === 'invoice-a-b.html');
$bt = Branding::tokens(['header_bg' => '#ABCDEF', 'button_radius' => '99', 'width' => '100', 'font' => 'georgia', 'text_color' => 'red']);
check('email branding tokens: validated colors, clamped sizes, font stack, social links', $bt['header_bg'] === '#abcdef' && $bt['button_radius'] === 40 && $bt['width'] === 480 && str_contains($bt['font'], 'Georgia') && $bt['text_color'] === '#1f2937' && Branding::social_links("Instagram|https://instagram.com/x\nbad line\nX|ftp://no") === [['label' => 'Instagram', 'url' => 'https://instagram.com/x']] && str_contains(Branding::css($bt), '#template_header{background-color:#abcdef'));
$ev = Collector::validate([['event' => 'reel_play', 'object_type' => 'reel', 'object_id' => '12', 'meta' => ['email' => 'x@y', 'source' => '<b>row</b>', 'n' => 3]], ['event' => 'evil'], 'junk', ['event' => 'chat_open']], Collector::ALLOWED, 20);
check('analytics batch validation: unknown events dropped, personal keys removed, html stripped, junk skipped', count($ev) === 2 && count(Collector::validate([['event' => 'chat_open'], ['event' => 'chat_open']], Collector::ALLOWED, 1)) === 1 && $ev[0]['object_id'] === 12 && $ev[0]['meta'] === ['source' => 'row', 'n' => 3] && $ev[1]['event'] === 'chat_open');
$series = Reports::series(['2026-09-03' => 4, '2026-09-04' => 8], 3, '2026-09-04');
check('analytics report series fills missing days and scales to 100', count($series) === 3 && $series[0]['n'] === 0 && $series[1]['pct'] === 50 && $series[2]['pct'] === 100 && $series[2]['day'] === '2026-09-04');
check('notification events from statuses including third party shipped', NotifEvents::event_for_status('wc-shipped') === 'shipped' && NotifEvents::event_for_status('dispatched', ['dispatched']) === 'shipped' && NotifEvents::event_for_status('out-for-delivery') === 'out_for_delivery' && NotifEvents::event_for_status('completed') === 'completed' && NotifEvents::event_for_status('on-hold') === '');
check('notification template params and fill', NotifTemplates::parse_params('{customer_name}, order_number , {nope}') === ['customer_name', 'order_number'] && NotifTemplates::params(['order_number', 'eta'], ['order_number' => '55']) === ['55', ''] && NotifTemplates::fill('Hi {customer_name}, order {order_number}', ['customer_name' => 'Asha', 'order_number' => '9']) === 'Hi Asha, order 9');
check('whatsapp consent resolution', OptIn::consented('yes', 'no') && ! OptIn::consented('no', 'yes') && OptIn::consented('', 'yes') && ! OptIn::consented('', ''));
check('phone normalization with billing country and default', PhoneNumber::normalize('098765 43210', 'IN') === '+919876543210' && PhoneNumber::normalize('+44 7911 123456', 'IN') === '+447911123456' && PhoneNumber::normalize('abc', 'IN') === '');
$pl = WhatsAppCloudChannel::payload('+919876543210', 'wg_order_shipped', 'en', ['Asha', '55'], ['url' => 'https://s/inv.pdf', 'filename' => 'inv.pdf']);
check('whatsapp payload: template with document header and ordered body params', $pl['to'] === '919876543210' && $pl['type'] === 'template' && $pl['template']['name'] === 'wg_order_shipped' && $pl['template']['components'][0]['type'] === 'header' && $pl['template']['components'][1]['parameters'][1]['text'] === '55');
check('whatsapp send response parsing and retry classification', WhatsAppCloudChannel::parse_send_response(200, ['messages' => [['id' => 'wamid.1']]])['provider_message_id'] === 'wamid.1' && WhatsAppCloudChannel::parse_send_response(500, [])['retryable'] && ! WhatsAppCloudChannel::parse_send_response(400, ['error' => ['code' => 132001, 'message' => 'Template not found']])['retryable'] && WhatsAppCloudChannel::parse_send_response(0, ['error' => ['code' => 'network', 'message' => 'timeout']])['retryable']);
check('whatsapp redaction and template parsing', WhatsAppCloudChannel::redact('token EAAabcdefghijklmnop failed for 919876543210') === 'token EAA[redacted] failed for [number]' && WhatsAppCloudChannel::parse_templates(['data' => [['name' => 'Wg_Order_Shipped', 'language' => 'en', 'status' => 'approved'], 'junk']]) === [['name' => 'wg_order_shipped', 'language' => 'en', 'status' => 'APPROVED', 'category' => '']]);
check('queue backoff and retry rules', NotifQueue::backoff(1) === 60 && NotifQueue::backoff(2) === 300 && NotifQueue::backoff(3) === 1800 && NotifQueue::should_retry(['ok' => false, 'retryable' => true], 1) && ! NotifQueue::should_retry(['ok' => false, 'retryable' => true], 3) && ! NotifQueue::should_retry(['ok' => false, 'retryable' => false], 1) && ! NotifQueue::should_retry(['ok' => true], 1));
check('log masking of phone and email', NotifLog::mask('+919876543210') === '********3210' && NotifLog::mask('asha@example.com') === 'as**@example.com');
$body = '{"entry":[{"changes":[{"value":{"statuses":[{"id":"wamid.1","status":"delivered"},{"id":"wamid.2","status":"failed","errors":[{"code":131026,"title":"Undeliverable"}]}]}}]}]}';
check('webhook signature and status parsing', WhatsAppController::verify_signature($body, 'sha256=' . hash_hmac('sha256', $body, 'secret'), 'secret') && ! WhatsAppController::verify_signature($body, 'sha256=bad', 'secret') && ! WhatsAppController::verify_signature($body, 'sha256=' . hash_hmac('sha256', $body, ''), '') && WhatsAppController::parse_statuses(json_decode($body, true)) === [['id' => 'wamid.1', 'status' => 'delivered', 'error_code' => '', 'error_message' => ''], ['id' => 'wamid.2', 'status' => 'failed', 'error_code' => '131026', 'error_message' => 'Undeliverable']]);
check('email channel defers WooCommerce covered events', EmailChannel::handled_by_woocommerce('completed') && ! EmailChannel::handled_by_woocommerce('shipped'));
echo "\n" . ($fail ? "$fail FAILURE(S)" : "ALL PASSED") . "\n";
exit($fail ? 1 : 0);
