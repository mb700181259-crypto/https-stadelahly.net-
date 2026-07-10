<?php
/**
 * Single post template (news article).
 *
 * @package Manchit
 */

get_header();

while ( have_posts() ) :
	the_post();

	// Register a view (server-side fallback; JS ping is the primary path).
	if ( function_exists( 'manchit_get_post_views' ) ) {
		// The JS ping handles cached pages; no double-count here.
		do_action( 'manchit_single_view', get_the_ID() );
	}
	?>

	<div class="mn-container">
		<div class="mn-content-area">
			<div class="mn-primary">
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'mn-article' ); ?> data-post-id="<?php the_ID(); ?>" data-mn-url="<?php the_permalink(); ?>">
					<div class="mn-article__inner">

						<?php
						// Categories.
						$cats = get_the_category();
						if ( $cats ) :
							?>
							<div class="mn-article__cats">
								<?php foreach ( $cats as $cat ) : ?>
									<a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<h1 class="mn-article__title"><?php the_title(); ?></h1>

						<?php if ( has_excerpt() ) : ?>
							<p class="mn-article__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>

						<div class="mn-article__meta">
							<?php manchit_post_meta(); ?>
						</div>

						<?php manchit_render_ads( 'after_title' ); ?>

						<?php
						// Featured image (LCP element — perf module sets fetchpriority).
						if ( manchit_get_option( 'show_featured_image', 1 ) && has_post_thumbnail() ) :
							?>
							<figure class="mn-article__featured">
								<?php the_post_thumbnail( 'manchit-hero', array( 'fetchpriority' => 'high' ) ); ?>
								<?php
								$caption = get_the_post_thumbnail_caption();
								if ( $caption ) {
									echo '<figcaption>' . esc_html( $caption ) . '</figcaption>';
								}
								?>
							</figure>
						<?php endif; ?>

						<?php
						// Top share bar (above the content).
						if ( manchit_get_option( 'show_share', 1 ) && manchit_get_option( 'share_top', 1 ) ) {
							get_template_part( 'template-parts/share' );
						}
						?>

						<?php
						// Render the content once. The content filters (headings/TOC
						// collection, ad injection, lazy iframes) run a single time; we
						// then place the collected TOC above the rendered body.
						$manchit_content = apply_filters( 'the_content', get_the_content() );
						$manchit_content = str_replace( ']]>', ']]&gt;', $manchit_content );

						if ( function_exists( 'manchit_the_toc' ) ) {
							manchit_the_toc();
						}
						?>

						<div class="mn-entry" itemprop="articleBody">
							<?php
							echo $manchit_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already filtered by the_content.
							wp_link_pages(
								array(
									'before' => '<nav class="mn-page-links">' . esc_html__( 'صفحات:', 'manchit' ),
									'after'  => '</nav>',
								)
							);
							?>
						</div>

						<?php
						// Tags.
						$tags = get_the_tags();
						if ( $tags ) :
							?>
							<div class="mn-tags">
								<?php foreach ( $tags as $tag ) : ?>
									<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php
						// Share buttons.
						if ( manchit_get_option( 'show_share', 1 ) ) {
							get_template_part( 'template-parts/share' );
						}
						?>

						<?php
						// Author box.
						if ( manchit_get_option( 'show_author_box', 1 ) ) {
							get_template_part( 'template-parts/author-box' );
						}
						?>

					</div><!-- .mn-article__inner -->
				</article>

				<?php
				// Prev / next navigation.
				if ( manchit_get_option( 'show_prev_next', 1 ) ) {
					$prev = get_previous_post();
					$next = get_next_post();
					if ( $prev || $next ) :
						?>
						<nav class="mn-post-nav" aria-label="<?php esc_attr_e( 'تصفح المقالات', 'manchit' ); ?>">
							<?php if ( $prev ) : ?>
								<a class="prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>" rel="prev">
									<span class="mn-post-nav__label"><?php echo manchit_icon( 'chevron-right' ) . esc_html__( 'السابق', 'manchit' ); // phpcs:ignore ?></span>
									<span class="mn-post-nav__title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
								</a>
							<?php else : ?>
								<span></span>
							<?php endif; ?>
							<?php if ( $next ) : ?>
								<a class="next" href="<?php echo esc_url( get_permalink( $next ) ); ?>" rel="next">
									<span class="mn-post-nav__label"><?php echo esc_html__( 'التالي', 'manchit' ) . manchit_icon( 'chevron-left' ); // phpcs:ignore ?></span>
									<span class="mn-post-nav__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
								</a>
							<?php endif; ?>
						</nav>
						<?php
					endif;
				}

				manchit_render_ads( 'before_related' );

				// Related posts.
				if ( manchit_get_option( 'show_related', 1 ) ) {
					get_template_part( 'template-parts/related' );
				}

				// Comments.
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}

				// Autoload the next (older) article on scroll — infinite news reading.
				if ( manchit_get_option( 'autoload_next', 1 ) ) {
					$older = get_previous_post();
					if ( $older ) {
						printf(
							'<div class="mn-autoload" data-mn-next="%s"><div class="mn-autoload__hint">%s</div><span class="mn-skeleton mn-autoload__skel"></span></div>',
							esc_url( get_permalink( $older ) ),
							esc_html__( 'المقال التالي…', 'manchit' )
						);
					}
				}
				?>
			</div><!-- .mn-primary -->

			<?php get_sidebar(); ?>
		</div>
	</div>

	<?php
endwhile;

get_footer();
