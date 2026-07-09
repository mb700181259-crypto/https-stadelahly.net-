<?php
/**
 * Search results template.
 *
 * @package Manchit
 */

get_header();
?>

<div class="mn-container">
	<div class="mn-content-area">
		<div class="mn-primary">
			<header class="mn-section-head">
				<h1 class="mn-section-title">
					<?php
					printf(
						/* translators: %s: search query */
						esc_html__( 'نتائج البحث عن: %s', 'manchit' ),
						'<span>' . esc_html( get_search_query() ) . '</span>'
					);
					?>
				</h1>
			</header>

			<?php if ( have_posts() ) : ?>
				<div class="mn-cards mn-cards--search">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content', 'list' );
					endwhile;
					?>
				</div>
				<?php manchit_pagination(); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif; ?>
		</div>

		<?php get_sidebar(); ?>
	</div>
</div>

<?php
get_footer();
