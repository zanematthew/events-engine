<?php

/**
 * Set our global white list of keys
 */
global $zm_user_settings;
$zm_user_settings = array(
    'default_location',
    'state',
    'type',
    'venues',
    'user_email'
    );

/**
 * This file handles redirecting of our templates to our given views
 * dir and anything else.
 *
 * Check if the themer has made a theme file in their
 * theme dir, if not load our default.
 *
 * @uses template_redirect http://codex.wordpress.org/Plugin_API/Action_Reference/template_redirect
 */
function zm_ev_tempalte_redirect() {

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
}
add_action('template_redirect', 'zm_ev_tempalte_redirect', 6);

function zm_ev_init(){
    $dependencies[] = 'jquery';
    // wp_enqueue_script( 'zm-ev-tinymce-script', plugin_dir_url( __FILE__ ) . 'vendor/tinymce/jquery.tinymce.js', $dependencies  );

    wp_register_script( 'zm-chosen-script', plugin_dir_url( __FILE__ ) . 'vendor/chosen/chosen.jquery.min.js', $dependencies );
    wp_register_style( 'zm-chosen-style', plugin_dir_url( __FILE__ ) . 'vendor/chosen/chosen.css' );

    add_action( 'wp_print_scripts', 'zm_ev_js_var_setup' );
}
add_action('init','zm_ev_init');

function zm_ev_js_var_setup(){
    global $current_user;
    global $zm_user_settings;
    get_currentuserinfo();

    if ( get_user_meta( $current_user->ID, 'fb_id', true ) ){
        $uid = get_user_meta( $current_user->ID, 'fb_id', true );
    } else {
        $uid = $current_user->ID;
    }

    $tmp = array();
    foreach( $zm_user_settings as $k ){
        $value = get_user_meta( $current_user->ID, $k, true );
        if ( ! empty( $value ) ) $tmp[$k] = $value;
    }
    $settings = json_encode( $tmp );

    ?><script type="text/javascript">

    var _site_url   = "<?php print site_url(); ?>";
    var _vendor_url = "<?php print site_url(); ?>/wp-content/plugins/zm-events-venues/vendor";

    if ( typeof _user !== "object") {
        var _user = {};
        _user.profile = {};
    }
    _user.profile = {
        user_login: "<?php print $current_user->user_login; ?>",
        uid:        <?php print $uid; ?>
    };
    _user.settings = <?php print $settings; ?>;
    </script>
<?php }

function zm_ev_get_tax_term( $tax=array() ){

    if ( ! is_array( $tax ) || is_null( $tax ) )
        die('need tax and make it array');

    extract( $tax );

    // simple error checking
    if ( empty( $post_id ) || empty( $taxonomy ) ) {
        return;
    }

    $data = array();
    $terms = get_the_terms( $post_id, $taxonomy );

    if ( $terms && is_array( $terms ) ) {
        foreach( $terms as $term ){
            $data[] = $term->name;
        }
        return implode( ' ', $data );
    } else {
        return '';
    }
}

/**
 * Save the settings, note this is called via ajax!
 * @todo check ajax refer
 */
function zm_ev_save_user_settings(){

    global $current_user;
    get_currentuserinfo();

    global $zm_user_settings;

    /**
     * Action is being sent with the ajax requst, so we unset it.
     */
    unset( $_POST['action'] );

    foreach( $zm_user_settings as $key ){

        /**
         * If any value is not in our white list we
         * unset (remove it) from our $_POST variable
         */

        /**
         * Since I'm not a fan of storing empty key/values in the db,
         * we remove the key if its empty.
         */
        if ( ! isset( $_POST[ $key ] )  ) {
            delete_user_meta( $current_user->ID, $key );
            unset( $_POST[ $key ] );
        }

        /**
         * A special case for email updates
         */
        elseif ( $key == 'user_email' ){
            wp_update_user( array( 'ID' => $current_user->ID, $key => $_POST[ $key ] ) );
        }

        /**
         * Finally, our default, update the user meta with the
         * user ID, key and value.
         */
        else {
            update_user_meta( $current_user->ID, $key, $_POST[ $key ] );
        }
    }

    /**
     * We send the new settings back to the ajax request as json encoded data.
     */
    print json_encode( $_POST );

    /**
     * Yes, ALL WordPress ajax request must die!
     */
    die();
}
add_action( 'wp_ajax_zm_ev_save_user_settings', 'zm_ev_save_user_settings' );
add_action( 'wp_ajax_nopriv_zm_ev_save_user_settings', 'zm_ev_save_user_settings');