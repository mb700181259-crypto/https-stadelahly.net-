<?php
/**
 * Article list row (horizontal).
 *
 * @package Manchit
 */

?>
<article <?php post_class( 'mn-card mn-card--row' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="mn-card__media">
			<?php manchit_primary_category(); ?>
			<?php manchit_thumbnail( 'manchit-card' ); ?>
		</div>
	<?php endif; ?>
	<div class="mn-card__body">
		<h3 class="mn-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( get_the_excerpt() ) : ?>
			<p class="mn-card__excerpt mn-clamp-3"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 32 ) ); ?></p>
		<?php endif; ?>
		<?php manchit_post_meta(); ?>
	</div>
</article>
