<?php
/**
 * Main template — fallback for all archive/blog views.
 *
 * @package Manchit
 */

get_header();
?>

<div class="mn-container">
	<div class="mn-content-area">
		<div class="mn-primary">
			<?php if ( is_home() && ! is_front_page() && get_the_title( get_option( 'page_for_posts' ) ) ) : ?>
				<header class="mn-section-head">
					<h1 class="mn-section-title"><?php echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) ); ?></h1>
				</header>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>
				<div class="mn-cards">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content', 'card' );
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
