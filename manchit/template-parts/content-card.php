<?php
/**
 * Article card (grid).
 *
 * @package Manchit
 */

?>
<article <?php post_class( 'mn-card' ); ?>>
	<div class="mn-card__media">
		<?php manchit_primary_category(); ?>
		<?php manchit_thumbnail( 'manchit-card' ); ?>
	</div>
	<div class="mn-card__body">
		<h3 class="mn-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>
		<?php if ( get_the_excerpt() ) : ?>
			<p class="mn-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), (int) manchit_get_option( 'excerpt_length', 22 ) ) ); ?></p>
		<?php endif; ?>
		<?php
		manchit_post_meta(
			array(
				'author'  => false,
				'reading' => false,
				'avatar'  => false,
			)
		);
		?>
	</div>
</article>
