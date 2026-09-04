<?php
/**
 * Site footer. Footer parts hook into webgram/footer.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
	</div><!-- #content -->
	<?php do_action( 'webgram/before_footer' ); ?>
	<footer id="colophon" class="wg-footer">
		<?php do_action( 'webgram/footer' ); ?>
	</footer>
</div><!-- #page -->
<?php do_action( 'webgram/after_page' ); ?>
<?php wp_footer(); ?>
</body>
</html>
