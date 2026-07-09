<?php
/**
 * Sidebar template.
 *
 * @package Manchit
 */

if ( 'full' === manchit_get_option( 'layout', 'right-sidebar' ) ) {
	return;
}
if ( ! is_active_sidebar( 'sidebar-main' ) ) {
	return;
}
?>
<aside class="mn-sidebar" role="complementary" aria-label="<?php esc_attr_e( 'الشريط الجانبي', 'manchit' ); ?>">
	<?php manchit_render_ads( 'sidebar_top' ); ?>
	<?php dynamic_sidebar( 'sidebar-main' ); ?>
	<?php manchit_render_ads( 'sidebar_bottom' ); ?>
</aside>
