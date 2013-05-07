<?php

function zm_ev_admin_scripts( $hook ){

    $dependencies[] = 'jquery';

    /**
     * Load our datetime picker on edit post page or
     * adding new post page and only on our cpt
     */
    if ( 'post.php' == $hook || 'post-new.php' == $hook && ! empty( $_GET['post_type'] ) && $_GET['post_type'] == 'events' ){

        // Start Vendor files
        wp_enqueue_script( 'zm-ev-jquery-ui-script', plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/js/jquery-ui-1.9.2.custom.min.js', $dependencies  );
        wp_enqueue_script( 'zm-ev-slide-time-script', plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/ui/minified/jquery.effects.slide.min.js', $dependencies  );
        wp_enqueue_script( 'zm-ev-datepicker-time-script', plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js', $dependencies  );
        wp_enqueue_script( 'zm-ev-date-time-script', plugin_dir_url( __FILE__ ) . 'vendor/jquery-timepicker/jquery-ui-timepicker-addon.js', $dependencies  );

        // Vendor CSS
        wp_enqueue_style( 'zm-ev-theme-style',       plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/themes/ui-lightness/jquery.ui.theme.css' );
        wp_enqueue_style( 'zm-ev-core-style',        plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/themes/ui-lightness/jquery.ui.core.css' );
        wp_enqueue_style( 'zm-ev-datepicker-style',  plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/themes/ui-lightness/jquery.ui.datepicker.css' );
        wp_enqueue_style( 'zm-ev-slider-style',      plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/themes/ui-lightness/jquery.ui.slider.css' );
        wp_enqueue_style( 'zm-ev-datepicker-style',  plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/themes/ui-lightness/jquery.ui.datepicker.css' );
        wp_enqueue_style( 'zm-ev-slider-base-style', plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/themes/base/jquery.ui.slider.css' );
        wp_enqueue_style( 'zm-ev-timepicker-style',  plugin_dir_url( __FILE__ ) . 'vendor/jquery-timepicker/jquery-ui-timepicker-addon.css' );
        // End Vendor files
    }
}
add_action( 'admin_enqueue_scripts', 'zm_ev_admin_scripts' );


/**
 * Hook to display the Admin Menu
 */
function zm_ev_settings_menu(){
    add_submenu_page( 'options-general.php', 'evs', 'EV:Settings', 'activate_plugins', 'events-venues-settings', 'zm_ev_settings_page' );
}
add_action('admin_menu','zm_ev_settings_menu');

/**
 * Print out the settings page/css/js
 */
function zm_ev_settings_page(){?>
    <div class="zm-ev-settings-container">
        <h1><?php _e('Events &amp; Venues Settings', 'zm_ev'); ?></h1>
        <form action="#" method="POST" id="zm_ev_settings_form">
        <fieldset>
            <legend>General Settings</legend>
            <label>Google Analytics</label>
            <input type="text" name="zm_ev_google_anaylitcs_code" value="<?php print get_option('zm_ev_google_anaylitcs_code'); ?>" />
        </fieldset>
        <?php do_action('zm_ev_before_settings'); ?>
        <?php do_action('zm_ev_after_settings'); ?>
        </form>
    </div>
<?php }