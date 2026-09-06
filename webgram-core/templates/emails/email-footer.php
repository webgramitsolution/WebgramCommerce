<?php
/**
 * Branded WooCommerce email footer. Replaces woocommerce/templates/emails/email-footer.php while the Webgram
 * email templates are on. The woocommerce_email_footer_text filter and WooCommerce ids are preserved.
 *
 * @package Webgram\Core
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Modules\Emails\Branding;

$wgc_module = webgram_core()->modules()->get( 'emails' );
$wgc_s      = $wgc_module ? $wgc_module->settings()->all() : [];
$wgc_t      = Branding::tokens( $wgc_s );
$wgc_social = Branding::social_links( (string) ( $wgc_s['social'] ?? '' ) );
?>
														</div>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<table border="0" cellpadding="10" cellspacing="0" width="<?php echo (int) $wgc_t['width']; ?>" id="template_footer" style="max-width:100%">
						<tr>
							<td valign="top">
								<table border="0" cellpadding="10" cellspacing="0" width="100%">
									<?php if ( $wgc_social ) : ?>
										<tr>
											<td colspan="2" valign="middle" class="wgc-email-social" style="text-align:center;padding:12px 0 0">
												<?php foreach ( $wgc_social as $wgc_link ) : ?>
													<a href="<?php echo esc_url( $wgc_link['url'] ); ?>" style="display:inline-block;margin:0 6px;color:<?php echo esc_attr( $wgc_t['text_color'] ); ?>;font-size:12px"><?php echo esc_html( $wgc_link['label'] ); ?></a>
												<?php endforeach; ?>
											</td>
										</tr>
									<?php endif; ?>
									<tr>
										<td colspan="2" valign="middle" id="credit" style="text-align:center;color:<?php echo esc_attr( $wgc_t['text_color'] ); ?>;opacity:.7;font-family:<?php echo esc_attr( $wgc_t['font'] ); ?>;font-size:12px;line-height:1.6;padding:12px 24px">
											<?php echo wp_kses_post( wpautop( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) ); ?>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
</body>
</html>
