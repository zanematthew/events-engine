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
 * Save the settings, note this is called via ajax!
 */
function zm_ev_save_settings(){

    if ( empty( $_POST ) )
        return;

    $option_value = get_option( $_POST['name'] );

    if ( empty( $_POST['value'] ) || $_POST['value'] === "false" ){
        delete_option( $_POST['name'] );
    } else {
        update_option( $_POST['name'], wp_filter_nohtml_kses( $_POST['value'] ) );
    }

    die();
}
add_action( 'wp_ajax_zm_ev_save_settings', 'zm_ev_save_settings' );
add_action( 'wp_ajax_nopriv_zm_ev_save_settings', 'zm_ev_save_settings');

/**
 * Hook to display the Admin Menu
 */
function zm_ev_settings_menu(){
    add_menu_page( 'E V S', 'E&V Settings', 'activate_plugins', 'events-venues-settings', 'zm_ev_settings_page', $icon_url=null, $position=null );
}
add_action('admin_menu','zm_ev_settings_menu');

/**
 * Print out the settings page/css/js
 */
function zm_ev_settings_page(){?>
    <style type="text/css">
    .zm-ev-settings-container fieldset {
        border: 1px solid #ECECEC;
        float: left;
        padding: 10px;
        margin: 0 15px 15px 0;
        width: 400px;
        border-radius: 4px;
        }

    .zm-ev-settings-container legend {
        font-weight: 300;
        }

    .zm-ev-settings-container fieldset p {
        float: left;
        width: 400px;
        margin: 0;
        clear: both;
        }

    .zm-ev-settings-container label {
        width: 125px;
        line-height: 20px;
        float: left;
        text-transform: capitalize;
        }

    .zm-ev-settings-container fieldset input[type="text"] {
        float: left;
        width: 68%;
        }

    .zm-ev-settings-container .zm-status-saved {
        float: left;
        margin: 5px 0 0 5px;
        color: green;
        }
    </style>
    <script type="text/javascript">
    jQuery( document ).ready(function( $ ){

        function zm_json_save_setting( my_obj ){

            if ( my_obj.attr('type') == "checkbox" ){
                value = my_obj.is(":checked");
            } else {
                value = my_obj.val();
            }

            var data = {
                name: my_obj.attr('name'),
                value: value,
                action: "zm_ev_save_settings"
            };

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: data,
                success: function( msg ){
                    my_obj.after('<div class="zm-status-saved">Saved!</div>');
                    $('.zm-status-saved').delay('slow').fadeOut();
                }
            });
        }

        $('#zm_ev_settings_form input[type="checkbox"]').on('change', function(){
            zm_json_save_setting( $(this) );
        });

        $('#zm_ev_settings_form input[type="text"]').on('blur', function(){
            zm_json_save_setting( $(this) );
        });
    });
    </script>
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