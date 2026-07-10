<?php
/* Copyrights (C) Arb4Host Network */

defined( 'ABSPATH' ) or die( 'No direct access allowed!' );
const THEME_TEXT_DOMAIN = 'a4h_lang';
global $pagenow;

$allowed_pages = [ 'wp-login.php', 'admin-ajax.php', 'themes.php', 'theme-install.php' ];
require_once( get_template_directory() . '/inc/vendor/dependencies.php' );

if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
    if ( ! in_array( $pagenow, $allowed_pages ) ) {
        a4h_template_dependency_required( 'php' );
        exit();
    }
} else {
    if ( extension_loaded( "IonCube Loader" ) ) {
        $ioncube_ver = function_exists( 'ioncube_loader_version' ) ? ioncube_loader_version() : 'none';

        if ( version_compare( $ioncube_ver, '12.0', '<' ) ) {
            if ( ! in_array( $pagenow, $allowed_pages ) ) {
                a4h_template_dependency_required( 'ioncube' );
                exit();
            }
        } else {
            switch ( true ) {
                case version_compare( PHP_VERSION, '8.2', '>=' ):
                    require_once( get_template_directory() . '/inc/vendor/init82.php' );
                    break;
                case version_compare( PHP_VERSION, '8.1', '>=' ):
                    require_once( get_template_directory() . '/inc/vendor/init81.php' );
                    break;
            }
        }

    } else {
        if ( ! in_array( $pagenow, $allowed_pages ) ) {
            a4h_template_dependency_required( 'ioncube' );
            exit();
        } else {
            add_action( 'admin_notices', 'a4h_template_dependency_required_admin_notices' );
        }
    }
}

if ( !function_exists('str_get_html') ) {
    require_once( get_template_directory() . '/inc/vendor/simple_html_dom.php' );
}