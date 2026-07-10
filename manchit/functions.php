<?php
/**
 * Manchit theme bootstrap.
 *
 * A blazing-fast, SEO-first WordPress news theme engineered for
 * Google News, Google Discover and Google Suggest.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MANCHIT_VERSION', '1.2.1' );
define( 'MANCHIT_DIR', trailingslashit( get_template_directory() ) );
define( 'MANCHIT_URI', trailingslashit( get_template_directory_uri() ) );

/**
 * Load a theme include file from /inc.
 *
 * @param string $file Relative file name without extension.
 */
function manchit_require( $file ) {
	$path = MANCHIT_DIR . 'inc/' . $file . '.php';
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

/* Core modules — order matters. Options load first (helpers used everywhere). */
manchit_require( 'options' );            // option store, defaults, helpers
manchit_require( 'setup' );              // theme supports, menus, image sizes
manchit_require( 'performance' );        // speed: dequeue bloat, preload, lazy, CWV
manchit_require( 'enqueue' );            // styles & scripts
manchit_require( 'fonts' );              // Arabic font system
manchit_require( 'template-functions' ); // body classes, filters
manchit_require( 'template-tags' );      // helpers used inside templates
manchit_require( 'breadcrumbs' );        // accessible breadcrumbs + schema
manchit_require( 'seo' );                // JSON-LD schema, OG, News/Discover meta
manchit_require( 'ads' );                // ad placement engine
manchit_require( 'revenue-share' );      // optional author revenue sharing
manchit_require( 'news-sitemap' );       // Google News XML sitemap
manchit_require( 'customizer' );         // Customizer (colors, logo, live preview)
manchit_require( 'admin-panel' );        // dedicated admin control panel
manchit_require( 'widgets' );            // widget areas + custom widgets
manchit_require( 'post-views' );         // lightweight post views counter
manchit_require( 'toc' );                // automatic table of contents
manchit_require( 'faq' );                // FAQ block + FAQPage schema
manchit_require( 'nav-walker' );         // accessible menu walker
