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


function zm_ev_user_state_pref( $state_pref=null, $echo=true ){

    if ( ! $state_pref ) return false;

    $venues = New Venues;
    $count = count( $state_pref );
    $i = 0;

    $html = null;
    foreach( $state_pref as $state_abbr ) {
        $state = $venues->stateByAbbreviation( $state_abbr );
        $html .= "<em>{$state}</em>";
        $i++;
        if ( $i != $count ){
            $html .= ", ";
        }
    }

    if ( $echo ){
        print '<div class="alert alert-success"><strong>States</strong> ' . $html . '</div>';
    } else {
        return $html;
    }
}

function zm_ev_user_venue_pref( $venues_id=null, $echo=true ){

    if ( ! $venues_id ) return false;

    $i = 0;
    $count = count( $venues_id );
    $html = null;
    foreach( $venues_id as $id ){
        $html .= '<em>'.get_the_title( $id ).'</em>';
        $i++;
        if ( $i != $count ){
            $html .= ", ";
        }
    }
    if ( $echo ){
        print '<div class="alert alert-success"><strong>States</strong> ' . $html . '</div>';
    } else {
        return $html;
    }
}

function zm_ev_user_type_pref( $type_ids=null, $echo=true ){

    if ( ! $type_ids ) return false;

    $i = 0;
    $count = count( $type_ids );
    $html = null;
    foreach( $type_ids as $id ){
        $terms = get_term_by('id', $id, 'type' );
        $i++;
        $html .= '<em>'.$terms->name.'</em>';
        if ( $i != $count ){
            $html .= ", ";
        }
    }

    if ( $echo ){
        print '<div class="alert alert-info"><strong>Types</strong> '. $html . '</div>';
    } else {
        return $html;
    }
}

function zm_ev_venues_by_user_pref_args( $cpt=null ){

    $zm_state_preference = get_user_meta( get_current_user_id(), 'state', true );
    $zm_venues_id_preference = get_user_meta( get_current_user_id(), 'venues', true );
    $zm_type_ids_preference = get_user_meta( get_current_user_id(), 'type', true );
    $venues = New Venues;

    // start our shared arguments
    $args = array( 'post_status' => 'publish', 'posts_per_page' => -1 );
    $tax_query = array( array( 'taxonomy' => 'type', 'field' => 'id', 'terms' => $zm_type_ids_preference ) );

    if ( $zm_state_preference && $zm_venues_id_preference && $zm_type_ids_preference ) {

        $tmp_venues_ids = $venues->getVenueIdByState( $zm_state_preference );
        if ( $tmp_venues_ids ){
            $venues_id_intersect = array_intersect( $tmp_venues_ids, $zm_venues_id_preference );
        }

        // Select Events by user Venues pref.
        if ( ! empty( $venues_id_intersect ) && $cpt == 'events' ){
            $args['post_type'] = 'events';
            $args['tax_query'] = $tax_query;
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key' => 'venues_id',
                    'value' => $venues_id_intersect,
                    'compare' => 'IN'
                    ),
                array(
                    'key' => 'events_start-date',
                    'value' => date('Y'),
                    'compare' => '>='
                    )
                );
            $args['orderby'] = 'meta_value';
            $args['meta_key'] = 'events_start-date';
            $args['order'] = 'ASC';
        } elseif ( ! empty( $venues_id_intersect ) && $cpt == 'venues' ) {
            $args['post_type'] = 'venues';
            $args['post__in'] = $venues_id_intersect;
            unset( $args['meta_query'] );
        } else {
            return false;
        }

        zm_ev_user_state_pref( $zm_state_preference );
        zm_ev_user_venue_pref( $zm_venues_id_preference );
        zm_ev_user_type_pref( $zm_type_ids_preference );

    }

    /**
     * If we have a state pref. and type pref. and post is Events
     */
    elseif ( $zm_state_preference && $zm_type_ids_preference && $cpt == 'events' ){
        $tmp_venues_ids = $venues->getVenueIdByState( $zm_state_preference );

        $args['post_type'] = 'events';
        $args['tax_query'] = $tax_query;
        $args['meta_query'] = array(
            'relation' => 'AND',
            array(
                'key' => 'venues_id',
                'value' => $tmp_venues_ids,
                'compare' => 'IN',
                'type' => 'NUMERIC'
                ),
            array(
                'key' => 'events_start-date',
                'value' => date('Y'),
                'compare' => '>='
                )
            );

    } elseif ( $zm_type_ids_preference && $cpt == 'events' ){
        $args['post_type'] = 'events';
        $args['tax_query'] = $tax_query;
    } elseif ( $zm_state_preference && $zm_venues_id_preference ) {
        $tmp_venues_ids = $venues->getVenueIdByState( $zm_state_preference );
        if ( $tmp_venues_ids ){
            $venues_id_intersect = array_intersect( $tmp_venues_ids, $zm_venues_id_preference );
        }

        // Select Events by user Venues pref.
        if ( ! empty( $venues_id_intersect ) && $cpt == 'events' ){
            $args['post_type'] = 'events';
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key' => 'venues_id',
                    'value' => $venues_id_intersect,
                    'compare' => 'IN'
                    ),
                array(
                    'key' => 'events_start-date',
                    'value' => date('Y'),
                    'compare' => '>='
                    )
                );
            $args['orderby'] = 'meta_value';
            $args['meta_key'] = 'events_start-date';
            $args['order'] = 'ASC';
        } elseif ( ! empty( $venues_id_intersect ) && $cpt == 'venues' ) {
            $args['post_type'] = 'venues';
            $args['post__in'] = $venues_id_intersect;
            unset( $args['meta_query'] );
        } else {
            return false;
        }

    }

    /**
     * If we have no state pref. and our post type is Venues
     * Select Venues by user state pref.
     */
    elseif ( $zm_state_preference && $cpt == 'venues' ){
        $args['post_type'] = $cpt;
        $args['meta_query'] = array(
            array(
                'key' => 'venues_state',
                'value' => $zm_state_preference,
                'compare' => 'IN'
                )
            );

    } elseif ( $zm_state_preference && $cpt == 'events' ){
        /**
         * Since we can't query Events by state we need to query
         * Venues by state, returing a list of Venue IDs. Then
         * query Events by Venues IDs. So we build a meta query
         * by post_type of Venues where the meta key "venues_state"
         * is any value from our state pref.
         */
        $tmp_venues_ids = $venues->getVenueIdByState( $zm_state_preference );

        /**
         * Finally, we build our query arguments based the
         * venues_ids found.
         */
        $args['post_type'] = 'events';
        $args['meta_query'] = array(
            'AND',
            array(
                'key' => 'venues_id',
                'value' => $tmp_venues_ids,
                'compare' => 'IN'
                ),
            array(
                'key' => 'events_start-date',
                'value' => date('Y'),
                'compare' => '>='
                )
            );
        $args['orderby'] = 'meta_value';
        $args['meta_key'] = 'events_start-date';
        $args['order'] = 'ASC';

    }

    /**
     * If this is a venue call
     */
    elseif ( $cpt == 'venues' ){
        $args = array(
                'post_type' => array( $cpt ),
                'post_status' => 'publish',
                'order' => 'ASC'
                );
    }

    /**
     * Default
     */
    else {
        $args = array(
                'post_type' => array( $cpt ),
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => 'events_start-date',
                        'value' => date('Y'),
                        'compare' => '>='
                        )
                    ),
                'meta_key' => 'events_start-date',
                'orderby' => 'meta_value',
                'order' => 'ASC'
                );
    }

    $my_query = New WP_Query( $args );
    return $my_query->posts;
}

function zm_ev_user_pref_message(){

    $user_id = get_current_user_id();

    $types = zm_ev_user_type_pref( get_user_meta( $user_id, 'type', true ), $echo=false );
    $states = zm_ev_user_state_pref( get_user_meta( $user_id, 'state', true ), $echo=false );
    $venues = zm_ev_user_venue_pref( get_user_meta( $user_id, 'venues', true ), $echo=false );
    $html = null;
    if ( $types || $states || $venues )
        $html = $types . ' &bull; ' . $states . ' &bull; ' . $venues;

     print '<div class="alert alert-success">' . zm_user_setting_link() . $html . '</div>';
}