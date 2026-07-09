<?php
/**
 * "Nothing found" placeholder.
 *
 * @package Manchit
 */

?>
<div class="mn-box mn-none">
	<div class="mn-box__body mn-text-center">
		<h2><?php esc_html_e( 'لا توجد نتائج', 'manchit' ); ?></h2>
		<?php if ( is_search() ) : ?>
			<p><?php esc_html_e( 'لم نعثر على ما يطابق بحثك. جرّب كلمات أخرى.', 'manchit' ); ?></p>
			<?php get_search_form(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'لا توجد مقالات هنا حالياً. عد قريباً.', 'manchit' ); ?></p>
			<a class="mn-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'العودة للرئيسية', 'manchit' ); ?></a>
		<?php endif; ?>
	</div>
</div>
