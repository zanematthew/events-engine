<?php
/**
 * Plugin Name: zM Events Engine
 * Plugin URI: --
 * Description: Used to create custom Events and Venues
 * Version: 1.0.0
 * Author: Zane M. Kolnik
 * Author URI: http://zanematthew.com/
 * License: GP
 */
define( 'ZM_EV_VERSION', '1' );
define( 'ZM_EV_OPTION', 'zm_ev_version' );

/**
 * Check if zM Easy Custom Post Types is installed. If it
 * is NOT installed we display an admin notice and stop
 * execution of this plugin, returning.
 */
if ( ! get_option('zm_easy_cpt_version' ) ){
    function zm_ev_admin_notice(){
        echo '<div class="updated"><p>This plugin requires <strong>zM Easy Custom Post Types</strong>.</p></div>';
    }
    add_action('admin_notices', 'zm_ev_admin_notice');
    return;
}

require_once 'my-admin-functions.php';
require_once 'functions.php';
require_once 'template-tags.php';

/**
 * Auto load our events.php, events_controller.php, etc.
 * and enqueue our admin and front end asset files.
 */
require_once plugin_dir_path( __FILE__ ) . '../zm-easy-cpt/plugin.php';
if ( ! function_exists( 'zm_easy_cpt_reqiure' ) ) return;
zm_easy_cpt_reqiure( plugin_dir_path(__FILE__) );

/**
 * Add the version number to the options table when
 * the plugin is installed.
 *
 * @note Our version number is used in Themes to check
 * if the plugin is installed!
 */
function zm_ev_activation() {

    if ( get_option( ZM_EV_OPTION ) &&
         get_option( ZM_EV_OPTION ) > ZM_EV_VERSION )
        return;

    update_option( ZM_EV_OPTION, ZM_EV_VERSION );

    /**
     * When the plugin is activated create the needed asset files.
     */
    do_action( 'zm_easy_cpt_create_assets', array('events', 'venues'), plugin_dir_path(__FILE__) );
}
register_activation_hook( __FILE__, 'zm_ev_activation' );

/**
 * When the plugins is deactivated delete our version number from the database.
 */
function zm_ev_deactivation(){
    delete_option( ZM_EV_OPTION );
}
register_deactivation_hook( __FILE__, 'zm_ev_deactivation' );