<?php
/**
 * This file handles redirecting of our templates to our given views
 * dir and anything else.
 *
 * Check if the themer has made a theme file in their
 * theme dir, if not load our default.
 *
 * @uses template_redirect http://codex.wordpress.org/Plugin_API/Action_Reference/template_redirect
 */
add_action('template_redirect', function( $params=array() ) {

    $pagename =  get_query_var( 'pagename' );

    $theme_dir = get_stylesheet_directory() . DIRECTORY_SEPARATOR;
    $theme_files = array(
        'settings' => $theme_dir . 'custom/settings.php'
        );

    if ( $pagename == 'settings' ){
        header("HTTP/ 200 OK");
        load_template( $theme_files['settings']  );
        die();
    }
}, 6);