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
    add_action( 'wp_print_scripts', 'zm_ev_js_var_setup' );
}
add_action('init','zm_ev_init');

function zm_ev_js_var_setup(){
    global $current_user;
    get_currentuserinfo();

    if ( get_user_meta( $current_user->ID, 'fb_id', true ) ){
        $uid = get_user_meta( $current_user->ID, 'fb_id', true );
    } else {
        $uid = $current_user->ID;
    }

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
 */
function zm_ev_save_user_settings(){

    if ( empty( $_POST ) )
        return;

    global $current_user;
    get_currentuserinfo();
    $key = 'zm_' . $_POST['name'] . '_preference';

    if ( empty( $_POST['value'] ) ){
        print delete_user_meta( $current_user->ID, $key, $_POST['value'] );
    } else {
        if ( $_POST['name'] == 'user_email' ){
            print wp_update_user( array( 'ID' => $current_user->ID, $key => $_POST['value'] ) );
        }
        print update_user_meta( $current_user->ID, $key, $_POST['value'] );
    }
    die();
}
add_action( 'wp_ajax_zm_ev_save_user_settings', 'zm_ev_save_user_settings' );
add_action( 'wp_ajax_nopriv_zm_ev_save_user_settings', 'zm_ev_save_user_settings');