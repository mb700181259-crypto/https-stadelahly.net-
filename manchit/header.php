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

	<?php if ( manchit_get_option( 'show_topbar', 1 ) ) : ?>
		<div class="mn-topbar">
			<div class="mn-container">
				<span class="mn-topbar__date">
					<?php echo manchit_icon( 'clock' ); // phpcs:ignore ?>
					<?php echo esc_html( wp_date( 'l، j F Y' ) ); ?>
				</span>
				<?php
				if ( has_nav_menu( 'topbar' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'topbar',
							'container'      => 'nav',
							'menu_class'     => 'mn-topbar__menu',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
				}
				?>
			</div>
		</div>
	<?php endif; ?>

	<header id="mn-header" class="<?php echo manchit_get_option( 'sticky_header', 1 ) ? 'is-sticky' : ''; ?>"
		data-hide-on-scroll="<?php echo (int) manchit_get_option( 'hide_header_on_scroll', 1 ); ?>">
		<div class="mn-container mn-header__bar">
			<?php manchit_branding(); ?>

			<nav class="mn-primary-nav" aria-label="<?php esc_attr_e( 'القائمة الرئيسية', 'manchit' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'mn-menu',
						'depth'          => 3,
						'walker'         => new Manchit_Nav_Walker(),
						'fallback_cb'    => 'manchit_default_menu',
					)
				);
				?>
			</nav>

			<div class="mn-header__actions">
				<button class="mn-icon-btn" type="button" aria-label="<?php esc_attr_e( 'بحث', 'manchit' ); ?>" data-mn-open-search>
					<?php echo manchit_icon( 'search' ); // phpcs:ignore ?>
				</button>
				<?php manchit_theme_toggle(); ?>
				<button class="mn-icon-btn mn-menu-toggle" type="button" aria-label="<?php esc_attr_e( 'القائمة', 'manchit' ); ?>" aria-expanded="false" data-mn-open-menu>
					<?php echo manchit_icon( 'menu' ); // phpcs:ignore ?>
				</button>
			</div>
		</div>
	</header>

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
