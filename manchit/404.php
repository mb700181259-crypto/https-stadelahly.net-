<?php
/**
 * 404 template.
 *
 * @package Manchit
 */

get_header();
?>

<div class="mn-container">
	<div class="mn-content-area">
		<div class="mn-primary">
			<div class="mn-box mn-404">
				<div class="mn-box__body mn-text-center">
					<p class="mn-404__code">404</p>
					<h1><?php esc_html_e( 'الصفحة غير موجودة', 'manchit' ); ?></h1>
					<p><?php esc_html_e( 'ربما حُذفت الصفحة أو تغيّر رابطها. جرّب البحث أو تصفّح أحدث الأخبار.', 'manchit' ); ?></p>
					<?php get_search_form(); ?>
					<a class="mn-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'العودة للرئيسية', 'manchit' ); ?></a>
				</div>
			</div>

			<section class="mn-related">
				<header class="mn-section-head">
					<h2 class="mn-section-title"><?php esc_html_e( 'أحدث المقالات', 'manchit' ); ?></h2>
				</header>
				<div class="mn-cards">
					<?php
					$latest = new WP_Query(
						array(
							'posts_per_page'      => 6,
							'ignore_sticky_posts' => true,
							'no_found_rows'       => true,
						)
					);
					while ( $latest->have_posts() ) :
						$latest->the_post();
						get_template_part( 'template-parts/content', 'card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
		</div>
	</div>
</div>

<?php
get_footer();
