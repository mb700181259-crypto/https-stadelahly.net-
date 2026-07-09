<?php
/**
 * Related posts.
 *
 * Picks recent posts sharing the same primary category (or tags), excluding the
 * current post. Uses a lightweight, cache-friendly query.
 *
 * @package Manchit
 */

$count = max( 2, (int) manchit_get_option( 'related_count', 6 ) );
$by    = manchit_get_option( 'related_by', 'category' );

$args = array(
	'posts_per_page'      => $count,
	'post__not_in'        => array( get_the_ID() ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'orderby'             => 'date',
);

if ( 'tag' === $by ) {
	$tags = wp_get_post_tags( get_the_ID(), array( 'fields' => 'ids' ) );
	if ( $tags ) {
		$args['tag__in'] = $tags;
	}
} else {
	$cats = wp_get_post_categories( get_the_ID() );
	if ( $cats ) {
		$args['category__in'] = $cats;
	}
}

$related = new WP_Query( $args );

// Fallback to latest posts if nothing related.
if ( ! $related->have_posts() ) {
	$related = new WP_Query(
		array(
			'posts_per_page'      => $count,
			'post__not_in'        => array( get_the_ID() ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
}

if ( $related->have_posts() ) :
	?>
	<section class="mn-related" aria-label="<?php esc_attr_e( 'مقالات ذات صلة', 'manchit' ); ?>">
		<header class="mn-section-head">
			<h2 class="mn-section-title"><?php esc_html_e( 'اقرأ أيضاً', 'manchit' ); ?></h2>
		</header>
		<div class="mn-cards">
			<?php
			while ( $related->have_posts() ) :
				$related->the_post();
				get_template_part( 'template-parts/content', 'card' );
			endwhile;
			?>
		</div>
	</section>
	<?php
endif;
wp_reset_postdata();
