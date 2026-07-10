<?php
/**
 * Blog posts index / homepage (magazine layout).
 *
 * Used when the front page shows the latest posts, or for the assigned posts
 * page. Respects the "homepage_style" option (magazine | grid | list).
 *
 * @package Manchit
 */

get_header();

$style   = manchit_get_option( 'homepage_style', 'magazine' );
$is_first = ! is_paged();

// Section builder homepage (front page, first page only).
if ( 'builder' === $style && $is_first && is_front_page() ) {
	manchit_render_home_sections();
	get_footer();
	return;
}
?>

<div class="mn-container">
	<?php if ( have_posts() ) : ?>

		<?php if ( 'magazine' === $style && $is_first ) : ?>
			<?php
			// Collect the first four posts for the hero block.
			$hero_posts = array();
			$i          = 0;
			while ( have_posts() && $i < 4 ) {
				the_post();
				$hero_posts[] = get_post();
				$i++;
			}
			if ( $hero_posts ) :
				?>
				<section class="mn-hero" aria-label="<?php esc_attr_e( 'أبرز الأخبار', 'manchit' ); ?>">
					<?php
					$main = $hero_posts[0];
					setup_postdata( $GLOBALS['post'] = $main ); // phpcs:ignore
					?>
					<article class="mn-hero__main">
						<?php
						if ( has_post_thumbnail( $main ) ) {
							echo get_the_post_thumbnail( $main, 'manchit-hero', array( 'fetchpriority' => 'high', 'alt' => get_the_title( $main ) ) );
						}
						?>
						<div class="mn-hero__overlay">
							<?php manchit_primary_category( $main->ID ); ?>
							<h2><a href="<?php echo esc_url( get_permalink( $main ) ); ?>"><?php echo esc_html( get_the_title( $main ) ); ?></a></h2>
							<?php manchit_post_meta( array( 'reading' => false, 'views' => false, 'author' => false ) ); ?>
						</div>
					</article>

					<div class="mn-hero__side">
						<?php
						foreach ( array_slice( $hero_posts, 1, 3 ) as $sp ) :
							setup_postdata( $GLOBALS['post'] = $sp ); // phpcs:ignore
							?>
							<article class="mn-card">
								<div class="mn-card__media">
									<?php manchit_thumbnail( 'manchit-card' ); ?>
								</div>
								<div class="mn-card__body">
									<h3 class="mn-card__title"><a href="<?php echo esc_url( get_permalink( $sp ) ); ?>"><?php echo esc_html( get_the_title( $sp ) ); ?></a></h3>
									<?php manchit_post_meta( array( 'reading' => false, 'views' => false, 'author' => false ) ); ?>
								</div>
							</article>
							<?php
						endforeach;
						wp_reset_postdata();
						?>
					</div>
				</section>
				<?php
			endif;
			?>

			<?php if ( have_posts() ) : ?>
				<section class="mn-latest">
					<header class="mn-section-head">
						<h2 class="mn-section-title"><?php esc_html_e( 'أحدث الأخبار', 'manchit' ); ?></h2>
					</header>
					<div class="mn-content-area">
						<div class="mn-primary">
							<div class="mn-cards">
								<?php
								$mn_i = 0;
								while ( have_posts() ) :
									the_post();
									get_template_part( 'template-parts/content', 'card' );
									if ( ++$mn_i === 6 ) {
										$mn_inline = manchit_render_ads( 'archive_inline', false );
										if ( $mn_inline ) {
											echo '<div class="mn-cards__ad">' . $mn_inline . '</div>'; // phpcs:ignore
										}
									}
								endwhile;
								?>
							</div>
							<?php manchit_posts_nav(); ?>
						</div>
						<?php get_sidebar(); ?>
					</div>
				</section>
			<?php endif; ?>

		<?php else : ?>

			<div class="mn-content-area">
				<div class="mn-primary">
					<div class="mn-cards <?php echo 'list' === $style ? 'mn-cards--list' : ''; ?>">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/content', 'list' === $style ? 'list' : 'card' );
						endwhile;
						?>
					</div>
					<?php manchit_posts_nav(); ?>
				</div>
				<?php get_sidebar(); ?>
			</div>

		<?php endif; ?>

	<?php else : ?>
		<div class="mn-content-area">
			<div class="mn-primary">
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			</div>
			<?php get_sidebar(); ?>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
