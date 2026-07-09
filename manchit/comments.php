<?php
/**
 * Comments template.
 *
 * @package Manchit
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="mn-comments">
	<?php if ( have_comments() ) : ?>
		<header class="mn-section-head">
			<h2 class="mn-section-title">
				<?php
				$count = get_comments_number();
				printf(
					/* translators: %s: comments count */
					esc_html( _n( '%s تعليق', '%s تعليقات', $count, 'manchit' ) ),
					esc_html( number_format_i18n( $count ) )
				);
				?>
			</h2>
		</header>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>

		<?php
		the_comments_navigation(
			array(
				'prev_text' => esc_html__( 'التعليقات الأقدم', 'manchit' ),
				'next_text' => esc_html__( 'التعليقات الأحدث', 'manchit' ),
			)
		);
	endif;

	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) {
		echo '<p class="mn-comments-closed">' . esc_html__( 'التعليقات مغلقة.', 'manchit' ) . '</p>';
	}

	comment_form(
		array(
			'class_form'         => 'mn-comment-form',
			'title_reply_before' => '<h3 class="mn-comment-form__title">',
			'title_reply_after'  => '</h3>',
			'title_reply'        => esc_html__( 'أضف تعليقاً', 'manchit' ),
			'label_submit'       => esc_html__( 'إرسال التعليق', 'manchit' ),
			'class_submit'       => 'mn-btn',
		)
	);
	?>
</section>
