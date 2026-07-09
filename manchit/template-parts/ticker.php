<?php
/**
 * Breaking-news ticker.
 *
 * @package Manchit
 */

$ticker_args = array(
	'posts_per_page'      => 8,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);
if ( 'category' === manchit_get_option( 'ticker_source', 'recent' ) && manchit_get_option( 'ticker_category' ) ) {
	$ticker_args['cat'] = (int) manchit_get_option( 'ticker_category' );
}
$ticker = new WP_Query( $ticker_args );

if ( $ticker->have_posts() ) :
	?>
	<div class="mn-container">
		<div class="mn-ticker" role="marquee" aria-label="<?php esc_attr_e( 'أحدث الأخبار', 'manchit' ); ?>">
			<span class="mn-ticker__label"><span class="mn-pulse"></span><?php esc_html_e( 'عاجل', 'manchit' ); ?></span>
			<div class="mn-ticker__track">
				<div class="mn-ticker__list">
					<?php
					// Duplicate the list once for a seamless loop.
					for ( $pass = 0; $pass < 2; $pass++ ) :
						$ticker->rewind_posts();
						while ( $ticker->have_posts() ) :
							$ticker->the_post();
							printf( '<a href="%s">%s</a>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
						endwhile;
					endfor;
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
endif;
wp_reset_postdata();
