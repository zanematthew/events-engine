<?php

global $_zm_setting_fields;
function adminInit(){

    global $_zm_setting_fields;

    if ( ! is_null( $_zm_setting_fields ) ){
        foreach( $_zm_setting_fields as $field ) {
            register_setting('wpmc_plugin_options', $field );
        }
    }
}
add_action( 'admin_init', 'adminInit',99 );

function adminMenu(){
    $permission = 'manage_options';
    add_submenu_page( 'edit.php?post_type=events', __('Settings', 'bmx_re'), __('Settings', 'bmx_re'),  $permission, 'wpmc_settings', 'demo_callback' );
}
add_action( 'admin_menu', 'adminMenu' );

function demo_callback(){?>
    <div class="wrap">
        <h2>Settings</h2>
        <form action="options.php" method="post" class="row-container">
            <?php settings_fields('wpmc_plugin_options'); ?>
            <?php do_action('zm_social_settings'); ?>
            <?php do_action('zm_gmaps_settings'); ?>
            <?php do_action('zm_weather_settings'); ?>
            <?php do_action('zm_json'); ?>
            <div class="button-container">
                <input name="Submit" type="submit" class="button " value="<?php esc_attr_e('Save Changes'); ?>" />
            </div>
        </form>
    </div>
<?php }

/**
 * Save the settings, note this is called via ajax!
 */
function zm_ev_save_settings(){
    if ( empty( $_POST ) )
        return;

    $option_value = get_option( $_POST['name'], null );

    if ( empty( $_POST['value'] ) ){
        print delete_option( $_POST['name'] );
    } else {
        print update_option( $_POST['name'], $_POST['value'] );
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
    .zm-ev-settings-container fieldset p {
        float: left;
        width: 400px;
        margin: 0;
        clear: both;
        }

    .zm-ev-settings-container label {
        width: 85px;
        line-height: 25px;
        float: left;
        }

    .zm-ev-settings-container input[type="text"] {
        float: left;
        }

    .zm-ev-settings-container .zm-status-saved {
        float: left;
        margin: 5px 0 0 5px;
        color: green;
        }
    </style>
    <script type="text/javascript">
    jQuery( document ).ready(function( $ ){
        $('#zm_ev_settings_form input').on('blur', function(){
            var _this = $( this );
            var data = {
                name: $( this ).attr('name'),
                value: $( this ).val(),
                action: "zm_ev_save_settings"
            };

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: data,
                success: function( msg ){
                    _this.after('<div class="zm-status-saved">Saved!</div>');
                    $('.zm-status-saved').delay('slow').fadeOut();
                }
            });
        });
    });
    </script>
    <div class="zm-ev-settings-container">
        <h1><?php _e('Events &amp; Venues Settings', 'zm_ev'); ?></h1>
        <form action="#" method="POST" id="zm_ev_settings_form">
        <?php do_action('zm_eve_before_settings'); ?>
        <?php do_action('zm_eve_after_settings'); ?>
        </form>
    </div>
<?php }