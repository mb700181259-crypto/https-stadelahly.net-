<?php
/**
 * Archive template (category, tag, author, date).
 *
 * @package Manchit
 */

get_header();
?>

<div class="mn-container">
	<div class="mn-content-area">
		<div class="mn-primary">
			<header class="mn-archive-head mn-section-head">
				<div>
					<h1 class="mn-section-title"><?php the_archive_title(); ?></h1>
					<?php
					$desc = get_the_archive_description();
					if ( $desc ) {
						echo '<div class="mn-archive-desc">' . wp_kses_post( $desc ) . '</div>';
					}
					?>
				</div>
			</header>

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
