<?php
/**
 * Footer template.
 *
 * @package Manchit
 */

?>
	</main><!-- #mn-main -->

	<footer id="mn-footer" class="mn-footer">
		<?php
		$cols   = (int) manchit_get_option( 'footer_columns', 4 );
		$active = false;
		for ( $i = 1; $i <= $cols; $i++ ) {
			if ( is_active_sidebar( 'footer-' . $i ) ) {
				$active = true;
				break;
			}
		}
		?>
		<?php if ( $active ) : ?>
			<div class="mn-container">
				<div class="mn-footer__widgets" style="grid-template-columns:repeat(<?php echo esc_attr( min( 4, max( 1, $cols ) ) ); ?>,1fr);">
					<?php for ( $i = 1; $i <= $cols; $i++ ) : ?>
						<div class="mn-footer__col">
							<?php dynamic_sidebar( 'footer-' . $i ); ?>
						</div>
					<?php endfor; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php
		// Footer bar is rendered by the zone builder (configurable rows/zones).
		manchit_render_footer_bar();
		?>
	</footer>
</div><!-- #mn-page -->

<?php
// Mobile drawer.
?>
<div class="mn-overlay" data-mn-overlay></div>
<aside class="mn-drawer" data-mn-drawer aria-hidden="true">
	<div class="mn-drawer__head">
		<?php manchit_branding(); ?>
		<button class="mn-icon-btn" type="button" aria-label="<?php esc_attr_e( 'إغلاق', 'manchit' ); ?>" data-mn-close-menu>
			<?php echo manchit_icon( 'close' ); // phpcs:ignore ?>
		</button>
	</div>
	<nav class="mn-drawer__nav" aria-label="<?php esc_attr_e( 'قائمة الجوال', 'manchit' ); ?>">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
				'container'      => false,
				'menu_class'     => 'mn-drawer__menu',
				'depth'          => 3,
				'fallback_cb'    => 'manchit_default_menu',
			)
		);
		?>
	</nav>
</aside>

<?php
// Search overlay.
?>
<div class="mn-search-overlay" data-mn-search aria-hidden="true">
	<button class="mn-icon-btn mn-search-close" type="button" aria-label="<?php esc_attr_e( 'إغلاق البحث', 'manchit' ); ?>" data-mn-close-search>
		<?php echo manchit_icon( 'close' ); // phpcs:ignore ?>
	</button>
	<?php get_search_form(); ?>
</div>

<button id="mn-scrolltop" type="button" aria-label="<?php esc_attr_e( 'العودة للأعلى', 'manchit' ); ?>" data-mn-scrolltop>
	<?php echo manchit_icon( 'arrow-up' ); // phpcs:ignore ?>
</button>

<?php wp_footer(); ?>
</body>
</html>
