<?php
/**
 * Login / register split page (spec 4.10): left column with a Login | Signup segmented toggle, forms with icon
 * prefixes and password toggles, trust logos strip; right column image from Theme Settings.
 * All WooCommerce login and registration hooks are preserved so social login plugins keep working.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Webgram
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

$webgram_register = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$webgram_tab      = $webgram_register && isset( $_GET['action'] ) && 'register' === $_GET['action'] ? 'register' : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$webgram_image    = (int) webgram_option( 'login_image' );
$webgram_mobile   = (int) webgram_option( 'login_image_mobile' );
$webgram_logos    = array_filter( (array) webgram_option( 'login_trust_logos' ), static fn( $l ) => ! empty( $l['image'] ) );
$webgram_gen_pass = 'yes' === get_option( 'woocommerce_registration_generate_password' );
?>
<div class="wg-login<?php echo $webgram_image ? ' wg-login--with-image' : ''; ?>" data-wg-component="login" data-tab="<?php echo esc_attr( $webgram_tab ); ?>">
	<div class="wg-login__forms">
		<?php if ( $webgram_register ) : ?>
			<div class="wg-login__toggle" role="tablist">
				<button type="button" class="wg-login__tab<?php echo 'login' === $webgram_tab ? ' is-active' : ''; ?>" role="tab" data-wg-login-tab="login" aria-selected="<?php echo 'login' === $webgram_tab ? 'true' : 'false'; ?>"><?php esc_html_e( 'Login', 'webgram' ); ?></button>
				<button type="button" class="wg-login__tab<?php echo 'register' === $webgram_tab ? ' is-active' : ''; ?>" role="tab" data-wg-login-tab="register" aria-selected="<?php echo 'register' === $webgram_tab ? 'true' : 'false'; ?>"><?php esc_html_e( 'Signup', 'webgram' ); ?></button>
			</div>
		<?php else : ?>
			<h2 class="wg-login__title"><?php esc_html_e( 'Login', 'webgram' ); ?></h2>
		<?php endif; ?>

		<div class="wg-login__pane<?php echo 'login' === $webgram_tab ? ' is-active' : ''; ?>" data-wg-login-pane="login">
			<form class="woocommerce-form woocommerce-form-login login wg-form" method="post" <?php echo ( isset( $_GET['redirect_to'] ) ) ? 'action="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '"' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>>
				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wg-form__row">
					<label for="username"><?php esc_html_e( 'Username or email address', 'webgram' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
					<span class="wg-form__field"><?php webgram_icon( 'user' ); ?><input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( sanitize_user( wp_unslash( $_POST['username'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the login nonce. ?>" required aria-required="true" /></span>
				</p>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wg-form__row">
					<label for="password"><?php esc_html_e( 'Password', 'webgram' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
					<span class="wg-form__field"><?php webgram_icon( 'lock' ); ?><input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" /><button type="button" class="wg-form__eye" data-wg-eye aria-label="<?php esc_attr_e( 'Show password', 'webgram' ); ?>"><?php webgram_icon( 'eye' ); ?></button></span>
				</p>

				<?php do_action( 'woocommerce_login_form' ); ?>

				<p class="form-row wg-form__row wg-form__row--inline">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme wg-check">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'webgram' ); ?></span>
					</label>
					<a class="wg-login__lost" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'webgram' ); ?></a>
				</p>
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit wg-btn wg-btn--primary wg-btn--block<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="<?php esc_attr_e( 'Login', 'webgram' ); ?>"><?php esc_html_e( 'Login', 'webgram' ); ?></button>

				<?php do_action( 'woocommerce_login_form_end' ); ?>
			</form>
		</div>

		<?php if ( $webgram_register ) : ?>
			<div class="wg-login__pane<?php echo 'register' === $webgram_tab ? ' is-active' : ''; ?>" data-wg-login-pane="register">
				<form method="post" class="woocommerce-form woocommerce-form-register register wg-form" <?php do_action( 'woocommerce_register_form_tag' ); ?> >
					<?php do_action( 'woocommerce_register_form_start' ); ?>
					<input type="hidden" name="webgram_register" value="1">

					<div class="wg-form__grid">
						<p class="form-row wg-form__row">
							<label for="webgram_full_name"><?php esc_html_e( 'Full name', 'webgram' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
							<span class="wg-form__field"><?php webgram_icon( 'user' ); ?><input type="text" class="input-text" name="webgram_full_name" id="webgram_full_name" autocomplete="name" value="<?php echo ( ! empty( $_POST['webgram_full_name'] ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['webgram_full_name'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the registration nonce. ?>" required aria-required="true" /></span>
						</p>
						<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
							<p class="woocommerce-form-row form-row wg-form__row">
								<label for="reg_username"><?php esc_html_e( 'Username', 'webgram' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
								<span class="wg-form__field"><?php webgram_icon( 'user' ); ?><input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( sanitize_user( wp_unslash( $_POST['username'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the registration nonce. ?>" required aria-required="true" /></span>
							</p>
						<?php endif; ?>
						<p class="woocommerce-form-row form-row wg-form__row">
							<label for="reg_email"><?php esc_html_e( 'Email address', 'webgram' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
							<span class="wg-form__field"><?php webgram_icon( 'mail' ); ?><input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( sanitize_email( wp_unslash( $_POST['email'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the registration nonce. ?>" required aria-required="true" /></span>
						</p>
						<?php if ( ! $webgram_gen_pass ) : ?>
							<p class="woocommerce-form-row form-row wg-form__row">
								<label for="reg_password"><?php esc_html_e( 'Password', 'webgram' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
								<span class="wg-form__field"><?php webgram_icon( 'lock' ); ?><input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" /><button type="button" class="wg-form__eye" data-wg-eye aria-label="<?php esc_attr_e( 'Show password', 'webgram' ); ?>"><?php webgram_icon( 'eye' ); ?></button></span>
							</p>
							<p class="form-row wg-form__row">
								<label for="webgram_password2"><?php esc_html_e( 'Confirm password', 'webgram' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
								<span class="wg-form__field"><?php webgram_icon( 'lock' ); ?><input type="password" class="input-text" name="webgram_password2" id="webgram_password2" autocomplete="new-password" required aria-required="true" /><button type="button" class="wg-form__eye" data-wg-eye aria-label="<?php esc_attr_e( 'Show password', 'webgram' ); ?>"><?php webgram_icon( 'eye' ); ?></button></span>
							</p>
						<?php endif; ?>
					</div>
					<?php if ( $webgram_gen_pass ) : ?>
						<p class="wg-form__hint"><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'webgram' ); ?></p>
					<?php endif; ?>

					<?php do_action( 'woocommerce_register_form' ); ?>

					<p class="woocommerce-form-row form-row wg-form__row">
						<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
						<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit wg-btn wg-btn--primary wg-btn--block<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="register" value="<?php esc_attr_e( 'Create account', 'webgram' ); ?>"><?php esc_html_e( 'Create account', 'webgram' ); ?></button>
					</p>

					<?php do_action( 'woocommerce_register_form_end' ); ?>
				</form>
			</div>
		<?php endif; ?>

		<?php if ( $webgram_logos ) : ?>
			<div class="wg-login__trust">
				<?php foreach ( $webgram_logos as $webgram_logo ) : ?>
					<?php echo wp_get_attachment_image( (int) $webgram_logo['image'], 'medium', false, [ 'alt' => (string) ( $webgram_logo['label'] ?? '' ), 'loading' => 'lazy' ] ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $webgram_image ) : ?>
		<div class="wg-login__image<?php echo webgram_option( 'login_image_mobile_show' ) ? ' wg-login__image--mobile' : ''; ?>">
			<?php echo wp_get_attachment_image( $webgram_image, 'full', false, [ 'class' => 'wg-login__img wg-login__img--desktop', 'loading' => 'lazy' ] ); ?>
			<?php if ( $webgram_mobile ) : ?>
				<?php echo wp_get_attachment_image( $webgram_mobile, 'large', false, [ 'class' => 'wg-login__img wg-login__img--mobile', 'loading' => 'lazy' ] ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
