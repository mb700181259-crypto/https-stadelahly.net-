<?php
/**
 * Author box.
 *
 * @package Manchit
 */

$author_id  = get_the_author_meta( 'ID' );
$bio        = get_the_author_meta( 'description' );
$display    = get_the_author();
if ( ! $display ) {
	return;
}
?>
<div class="mn-authorbox">
	<?php echo get_avatar( $author_id, 72, '', $display, array( 'loading' => 'lazy' ) ); ?>
	<div class="mn-authorbox__body">
		<a class="mn-authorbox__name" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" rel="author"><?php echo esc_html( $display ); ?></a>
		<?php if ( $bio ) : ?>
			<p class="mn-authorbox__bio"><?php echo esc_html( $bio ); ?></p>
		<?php endif; ?>
		<?php
		$url = get_the_author_meta( 'user_url' );
		if ( $url ) {
			printf( '<a class="mn-authorbox__link" href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $url ), esc_html__( 'الموقع الإلكتروني', 'manchit' ) );
		}
		?>
	</div>
</div>
