<?php
/**
 * Page template.
 *
 * @package Manchit
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="mn-container">
		<div class="mn-content-area">
			<div class="mn-primary">
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'mn-article' ); ?>>
					<div class="mn-article__inner">
						<h1 class="mn-article__title"><?php the_title(); ?></h1>

						<?php if ( has_post_thumbnail() && ! is_front_page() ) : ?>
							<figure class="mn-article__featured">
								<?php the_post_thumbnail( 'manchit-hero', array( 'fetchpriority' => 'high' ) ); ?>
							</figure>
						<?php endif; ?>

						<div class="mn-entry">
							<?php
							the_content();
							wp_link_pages(
								array(
									'before' => '<nav class="mn-page-links">' . esc_html__( 'صفحات:', 'manchit' ),
									'after'  => '</nav>',
								)
							);
							?>
						</div>
					</div>
				</article>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>

			<?php get_sidebar(); ?>
		</div>
	</div>
	<?php
endwhile;

get_footer();
