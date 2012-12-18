<?php
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
    .zm-ev-settings-container fieldset {
        border: 1px solid #ECECEC;
        float: left;
        padding: 10px;
        margin: 0 15px 15px 0;
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
        width: 85px;
        line-height: 20px;
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