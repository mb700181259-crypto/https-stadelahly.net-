<?php
/**
 * Theme setup: supports, menus, image sizes, i18n.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme features.
 */
function manchit_setup() {
	load_theme_textdomain( 'manchit', MANCHIT_DIR . 'languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 60,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support( 'custom-background', array( 'default-color' => 'f4f5f7' ) );

	// News-optimized image sizes (16:9 for cards & Discover/News thumbnails).
	set_post_thumbnail_size( 1200, 675, true );
	add_image_size( 'manchit-card', 640, 360, true );      // grid cards
	add_image_size( 'manchit-list', 168, 168, true );      // list thumbnails
	add_image_size( 'manchit-hero', 1280, 720, true );     // hero / featured
	add_image_size( 'manchit-og', 1200, 630, true );       // Open Graph / Discover 1.91:1

	register_nav_menus(
		array(
			'primary' => __( 'القائمة الرئيسية', 'manchit' ),
			'topbar'  => __( 'قائمة الشريط العلوي', 'manchit' ),
			'footer'  => __( 'قائمة التذييل', 'manchit' ),
			'mobile'  => __( 'قائمة الجوال', 'manchit' ),
		)
	);
}
add_action( 'after_setup_theme', 'manchit_setup' );

/**
 * Content width for embeds.
 */
function manchit_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'manchit_content_width', 820 );
}
add_action( 'after_setup_theme', 'manchit_content_width', 0 );

/**
 * Make Discover/News crawlers get large image previews & fresh thumbnails.
 * The og size is used for social + Discover cards.
 */
function manchit_default_thumbnail_size( $size ) {
	return $size;
}
