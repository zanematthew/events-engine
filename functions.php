<?php

function zm_ev_admin_scripts( $hook ){

    $dependencies[] = 'jquery';

    /**
     * Load our datetime picker on edit post page or
     * adding new post page and only on our cpt
     */
    if ( 'post.php' == $hook || 'post-new.php' == $hook && isset( $_GET ) && $_GET['post_type'] == 'events' ){

        // Start Vendor files
        wp_enqueue_script( 'zm-ev-jquery-ui-script', plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/js/jquery-ui-1.8.20.custom.min.js', $dependencies  );
        wp_enqueue_script( 'zm-ev-slide-time-script', plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/ui/minified/jquery.effects.slide.min.js', $dependencies  );
        wp_enqueue_script( 'zm-ev-datepicker-time-script', plugin_dir_url( __FILE__ ) . 'vendor/jquery-ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js', $dependencies  );
        wp_enqueue_script( 'zm-ev-timepicker-script', plugin_dir_url( __FILE__ ) . 'vendor/jquery-timepicker/jquery-ui-timepicker-addon.js', $dependencies  );
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

function zm_ee_comment_class( $post_id=null ){

    $comments_count = wp_count_comments( $post_id );

    if ( $comments_count->total_comments == 1 )
        $comment_class = 'comment-count';

    elseif ( $comments_count->total_comments > 1 )
        $comment_class = 'comments-count';
    else
        $comment_class = '';

    print $comment_class;
}

function zm_ee_format_date( $post_id=null, $both=true, $echo=true ) {

    if ( is_null( $post_id ) ) {
        global $post;
        $post_id = $post->ID;
    }

    $start = get_post_meta( $post_id, 'events_start-date', true );
    $end = get_post_meta( $post_id, 'events_end-date', true );

    if ( $end && $both )
        $date = date( 'M j', strtotime( $start ) ) . date( ' - M j, Y', strtotime( $end ) );
    else
        $date = date( 'M j, Y', strtotime( $start ) );

    if ( $echo ) print $date; else return $date;
}

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
 * @package This function makes use of the 'zm_geo_location' plugin
 * to return the users current location for directions.
 * @subpackage Makes use of the zM Geo Location to derive the directions
 * link.
 */
function zm_ev_venue_info_pane( $post_id=null ){

    global $post_type;

    if ( $post_type == 'events' ){
        $venue_id = Events::getTrackId( $post_id );
    } else {
        $venue_id = $post_id;
    }

    if ( get_option('zm_geo_location_version' ) ){
        $location = zm_geo_location_get();
        $directions = '<li><a href="https://maps.google.com/maps?saddr='.$location['city'].','.$location['region_full'].'&daddr='.Venues::getLatLon( $venue_id ).'"target="_blank">Directions</a><span class="bar">|</span></li>';
    } else {
        $directions = null;
    }

    ?>
    <div class="venue-info">
    <div class="content">
        <h3><?php print Venues::getName( $venue_id, $echo=true ); ?></h3>
        <?php Venues::getStreet( $venue_id, $echo=true ); ?>
        <ul class="inline meta-navigation">
            <li><a href="<?php Venues::getWebsite( $venue_id, $echo=true ); ?>" target="_blank">Website</a><span class="bar">|</span></li>
            <?php print $directions; ?>
            <li><?php print Events::getTrackLink( $post_id, 'Events' ); ?> <span class="count"><?php Events::getTrackEventCount( $venue_id, $echo=true ); ?></span></li>
        </ul>
    </div>
</div><?php }
add_action( 'zm_ev_venue_info', 'zm_ev_venue_info_pane', 8, 1 );

global $_zm_setting_fields;
function adminInit(){

    global $_zm_setting_fields;

    // if ( get_option('zm_gmaps_version') ) {
    //     $fields[] = 'zm_gmaps_api_key';
    // }
    if ( ! is_null( $_zm_setting_fields ) ){
        foreach( $_zm_setting_fields as $field ) {
            register_setting('wpmc_plugin_options', $field );
        }
    }
}
add_action( 'admin_init', 'adminInit',99 );

function adminMenu(){
        $permission = 'manage_options';
        add_submenu_page( 'edit.php?post_type=events', __('Settings', 'bmx_re'), __('Settings', 'bmx_re'),  $permission, 'wpmc_settings', function(){?>
        <div class="wrap">

            <h2>Settings</h2>
            <form action="options.php" method="post" class="row-container">
                <?php settings_fields('wpmc_plugin_options'); ?>
                <?php do_action('zm_social_settings'); ?>
                <?php do_action('zm_gmaps_settings'); ?>
                <?php do_action('zm_weather_settings'); ?>

                <div class="button-container">
                    <input name="Submit" type="submit" class="button " value="<?php esc_attr_e('Save Changes'); ?>" />
                </div>

            </form>
        </div>
        <?});
}
add_action( 'admin_menu', 'adminMenu' );

function zm_events_conut(){
    print Events::eventCount();
}

function zm_venues_count(){
    print Venues::trackCount();
}