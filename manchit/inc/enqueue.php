<?php
/**
 * Enqueue styles and scripts.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end assets.
 */
function manchit_enqueue_assets() {
	$ver = MANCHIT_VERSION;

	// Main stylesheet. The design system uses CSS logical properties so a single
	// stylesheet renders correctly in both RTL and LTR — no separate rtl.css.
	wp_enqueue_style( 'manchit-style', get_stylesheet_uri(), array(), $ver );

	// Theme JS — no jQuery dependency, deferred via performance module.
	wp_enqueue_script( 'manchit-theme', MANCHIT_URI . 'assets/js/theme.js', array(), $ver, true );

	// Pass runtime data to JS.
	wp_localize_script(
		'manchit-theme',
		'ManchitData',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'restUrl'    => esc_url_raw( rest_url() ),
			'nonce'      => wp_create_nonce( 'manchit_nonce' ),
			'copiedText' => __( 'تم نسخ الرابط', 'manchit' ),
			'shareText'  => __( 'شارك', 'manchit' ),
			'isSingular' => (int) is_singular(),
		)
	);

	// Threaded comments.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'manchit_enqueue_assets' );

/**
 * Inline expanded critical (above-the-fold) CSS so first paint never waits on
 * the stylesheet. Covers header, ticker, breadcrumbs, hero, cards, article
 * header and sidebar start — the visible viewport on home and single views.
 * When "optimize_css" is on, the full stylesheet then loads non-render-blocking
 * (see manchit_optimize_css_delivery in performance.php).
 */
function manchit_inline_critical_css() {
	$brand   = manchit_get_option( 'brand_color', '#d5011a' );
	$brand6  = manchit_adjust_brightness( $brand, -18 );
	$brand7  = manchit_adjust_brightness( $brand, -34 );
	// Minified critical CSS. Mirrors tokens/components from style.css.
	$css = <<<CSS
:root{--mn-brand:{$brand};--mn-brand-600:{$brand6};--mn-brand-700:{$brand7};--mn-bg:#f4f5f7;--mn-surface:#fff;--mn-surface-2:#f7f8fa;--mn-text:#16181d;--mn-text-soft:#4a4f5a;--mn-text-mute:#7a8090;--mn-border:#e6e8ec;--mn-radius:12px;--mn-gap:1.5rem;--mn-container:1200px;--mn-font-body:"Cairo","Segoe UI",system-ui,sans-serif;--mn-font-head:"Tajawal","Cairo",system-ui,sans-serif}
[data-theme=dark]{--mn-bg:#0e0f13;--mn-surface:#16181d;--mn-surface-2:#1d2027;--mn-text:#e9eaed;--mn-text-soft:#b9bcc6;--mn-text-mute:#868b98;--mn-border:#262a33}
*,*::before,*::after{box-sizing:border-box}body{margin:0;background:var(--mn-bg);color:var(--mn-text);font-family:var(--mn-font-body);line-height:1.9;-webkit-font-smoothing:antialiased;overflow-x:hidden}
img,svg{display:block;max-width:100%;height:auto}a{color:var(--mn-brand);text-decoration:none}h1,h2,h3{font-family:var(--mn-font-head);line-height:1.35;font-weight:800;margin:0}
.mn-container{width:100%;max-width:var(--mn-container);margin-inline:auto;padding-inline:var(--mn-gap)}
.mn-topbar{background:var(--mn-brand);color:#fff;font-size:.82rem}.mn-topbar .mn-container{display:flex;align-items:center;justify-content:space-between;min-height:34px;gap:1rem}.mn-topbar a{color:#fff}
#mn-header{position:sticky;top:0;z-index:100;background:var(--mn-surface);border-bottom:1px solid var(--mn-border)}
.mn-header__bar{display:flex;align-items:center;gap:var(--mn-gap);min-height:68px}
.mn-branding{display:flex;align-items:center;gap:.6rem;margin-inline-end:auto}.mn-site-title{font-family:var(--mn-font-head);font-size:1.5rem;font-weight:900;color:var(--mn-text)}.mn-site-title b{color:var(--mn-brand)}
.mn-primary-nav ul{list-style:none;margin:0;padding:0;display:flex;align-items:center;gap:.25rem}.mn-primary-nav a{display:block;padding:.55em .9em;border-radius:8px;font-weight:700;color:var(--mn-text-soft);font-size:.95rem}
.mn-header__actions{display:flex;align-items:center;gap:.35rem}.mn-icon-btn{display:inline-grid;place-items:center;width:42px;height:42px;border-radius:8px;color:var(--mn-text-soft)}.mn-icon-btn svg{width:22px;height:22px}.mn-menu-toggle{display:none}
@media(max-width:991px){.mn-primary-nav{display:none}.mn-menu-toggle{display:inline-grid}}
.mn-ticker{display:flex;background:var(--mn-surface);border:1px solid var(--mn-border);border-radius:8px;overflow:hidden}.mn-ticker__label{flex-shrink:0;background:var(--mn-brand);color:#fff;font-weight:800;font-family:var(--mn-font-head);padding:.6rem 1rem;display:flex;align-items:center;gap:.4rem}.mn-ticker__track{flex:1;overflow:hidden}.mn-ticker__list{display:flex;gap:2.5rem;white-space:nowrap;padding:.6rem 1rem}
.mn-breadcrumbs{font-size:.84rem;color:var(--mn-text-mute);padding-block:1rem;display:flex;flex-wrap:wrap;gap:.4rem;align-items:center}.mn-breadcrumbs a{color:var(--mn-text-soft);font-weight:600}
.mn-hero{display:grid;grid-template-columns:2fr 1fr;gap:var(--mn-gap);margin-block:2.5rem}.mn-hero__main{position:relative;border-radius:var(--mn-radius);overflow:hidden;min-height:380px}.mn-hero__main img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.mn-hero__overlay{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:flex-end;gap:.6rem;padding:1.6rem;background:linear-gradient(0deg,rgba(0,0,0,.85),rgba(0,0,0,.35) 45%,transparent 75%);color:#fff}.mn-hero__overlay h2{font-size:clamp(1.3rem,1rem + 1.6vw,2rem);color:#fff}.mn-hero__side{display:grid;grid-template-rows:repeat(3,1fr);gap:.75rem}
@media(max-width:767px){.mn-hero{grid-template-columns:1fr}}
.mn-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--mn-gap)}@media(max-width:767px){.mn-cards{grid-template-columns:repeat(2,1fr);gap:.75rem}}@media(max-width:460px){.mn-cards{grid-template-columns:1fr}}
.mn-card{display:flex;flex-direction:column;background:var(--mn-surface);border:1px solid var(--mn-border);border-radius:var(--mn-radius);overflow:hidden}.mn-card__media{position:relative;aspect-ratio:16/9;overflow:hidden;background:var(--mn-surface-2)}.mn-card__media img{width:100%;height:100%;object-fit:cover}.mn-card__cat{position:absolute;top:.6rem;inset-inline-start:.6rem;background:var(--mn-brand);color:#fff;font-size:.72rem;font-weight:800;padding:.25em .7em;border-radius:999px}.mn-card__body{display:flex;flex-direction:column;gap:.5rem;padding:.9rem 1rem 1.1rem;flex:1}.mn-card__title{font-size:1.05rem;line-height:1.5;font-weight:800}.mn-card__title a{color:var(--mn-text)}
.mn-section-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-block-end:var(--mn-gap)}.mn-section-title{position:relative;font-size:1.35rem;padding-inline-start:.8rem}
.mn-content-area{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:2.5rem;align-items:start;padding-block:2.5rem}@media(max-width:991px){.mn-content-area{grid-template-columns:minmax(0,1fr)}}
.mn-article{background:var(--mn-surface);border:1px solid var(--mn-border);border-radius:var(--mn-radius);overflow:hidden}.mn-article__inner{padding:clamp(1.1rem,.5rem + 2vw,2.4rem)}.mn-article__title{font-size:clamp(1.7rem,1.2rem + 2.2vw,2.6rem);margin-block-end:.6rem}.mn-article__featured{margin-block-end:1.4rem;border-radius:var(--mn-radius);overflow:hidden}
.skip-link{position:absolute;inset-inline-start:-9999px;top:0}
CSS;
	echo '<style id="manchit-critical">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static/derived CSS.
}
add_action( 'wp_head', 'manchit_inline_critical_css', 2 );

/**
 * Editor styles so the block editor mirrors the front-end.
 */
function manchit_editor_assets() {
	add_editor_style( 'assets/css/editor.css' );
}
add_action( 'after_setup_theme', 'manchit_editor_assets' );

/**
 * Admin panel assets (only on our settings screens).
 *
 * @param string $hook Current admin page hook.
 */
function manchit_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'manchit' ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_style( 'manchit-admin', MANCHIT_URI . 'assets/css/admin.css', array(), MANCHIT_VERSION );
	wp_enqueue_script( 'manchit-admin', MANCHIT_URI . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker', 'wp-i18n' ), MANCHIT_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'manchit_admin_assets' );
