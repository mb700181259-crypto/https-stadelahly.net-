<?php
/**
 * Header template.
 *
 * @package Manchit
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php
	// Set the initial theme (avoids dark/light flash) before paint.
	$mode = manchit_theme_mode();
	?>
	<script>(function(){try{var p="<?php echo esc_js( $mode ); ?>",s=localStorage.getItem("mn-theme"),m=s||p;if(m==="auto"){m=matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light";}document.documentElement.setAttribute("data-theme",m);}catch(e){}})();</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#mn-main"><?php esc_html_e( 'تخطّ إلى المحتوى', 'manchit' ); ?></a>

<?php if ( is_singular() && manchit_get_option( 'show_reading_progress', 1 ) ) : ?>
	<div class="mn-progress" aria-hidden="true"><div class="mn-progress__bar" data-mn-progress></div></div>
<?php endif; ?>

<div id="mn-page" class="mn-site">

	<?php
	// Header is rendered by the zone builder (configurable rows/zones/devices).
	manchit_render_header();
	?>

	<?php
	/**
	 * Fires right after the header — used by the ad engine for the top banner.
	 */
	do_action( 'manchit_after_header' );
	?>

	<?php
	// News ticker.
	if ( manchit_get_option( 'show_news_ticker', 1 ) && ! is_singular() ) {
		get_template_part( 'template-parts/ticker' );
	}
	?>

	<?php manchit_breadcrumbs(); ?>

	<main id="mn-main" class="mn-main">
