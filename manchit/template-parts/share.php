<?php
/**
 * Social share buttons.
 *
 * @package Manchit
 */

$url      = rawurlencode( get_permalink() );
$title    = rawurlencode( get_the_title() );
$networks = (array) manchit_get_option( 'share_networks', array( 'facebook', 'x', 'whatsapp', 'telegram', 'copy' ) );

$links = array(
	'facebook' => array(
		'class' => 's-facebook',
		'label' => 'Facebook',
		'href'  => "https://www.facebook.com/sharer/sharer.php?u={$url}",
		'icon'  => 'facebook',
	),
	'x'        => array(
		'class' => 's-x',
		'label' => 'X',
		'href'  => "https://twitter.com/intent/tweet?url={$url}&text={$title}",
		'icon'  => 'x',
	),
	'whatsapp' => array(
		'class' => 's-whatsapp',
		'label' => 'WhatsApp',
		'href'  => "https://api.whatsapp.com/send?text={$title}%20{$url}",
		'icon'  => 'whatsapp',
	),
	'telegram' => array(
		'class' => 's-telegram',
		'label' => 'Telegram',
		'href'  => "https://t.me/share/url?url={$url}&text={$title}",
		'icon'  => 'telegram',
	),
);
?>
<div class="mn-share">
	<span class="mn-share__label"><?php esc_html_e( 'شارك', 'manchit' ); ?></span>
	<?php
	foreach ( $networks as $net ) {
		if ( 'copy' === $net ) {
			continue;
		}
		if ( isset( $links[ $net ] ) ) {
			$l = $links[ $net ];
			printf(
				'<a class="%1$s" href="%2$s" target="_blank" rel="noopener nofollow" aria-label="%3$s">%4$s</a>',
				esc_attr( $l['class'] ),
				esc_url( $l['href'] ),
				esc_attr( sprintf( __( 'شارك على %s', 'manchit' ), $l['label'] ) ),
				manchit_icon( $l['icon'] ) // phpcs:ignore
			);
		}
	}
	if ( in_array( 'copy', $networks, true ) ) {
		printf(
			'<button class="s-copy" type="button" data-mn-copy="%s" aria-label="%s">%s</button>',
			esc_url( get_permalink() ),
			esc_attr__( 'نسخ الرابط', 'manchit' ),
			manchit_icon( 'link' ) // phpcs:ignore
		);
	}
	?>
</div>
