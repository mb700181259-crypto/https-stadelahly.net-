<?php
/**
 * Search form.
 *
 * @package Manchit
 */

?>
<form role="search" method="get" class="mn-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="mn-s"><?php esc_html_e( 'ابحث في الموقع', 'manchit' ); ?></label>
	<input type="search" id="mn-s" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'ابحث عن خبر…', 'manchit' ); ?>" autocomplete="off">
	<button type="submit" aria-label="<?php esc_attr_e( 'بحث', 'manchit' ); ?>">
		<?php echo manchit_icon( 'search' ); // phpcs:ignore ?>
	</button>
</form>
