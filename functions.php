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
function zm_ev_venue_address_pane( $post_id=null ){

    global $post_type;
    $venues = New Venues;

    if ( $post_type == 'events' ){
        $venue_id = Events::getVenueId( $post_id );
    } else {
        $venue_id = $post_id;
    }

    if ( get_option('zm_geo_location_version' ) ){
        $location = zm_geo_location_get();

        $street = $venues->getAttribute( array( 'key' => 'street' ) );
        $city = $venues->getAttribute( array( 'key' => 'city' ) );
        $state = $venues->getAttribute( array( 'key' => 'state' ) );
        $zip = $venues->getAttribute( array( 'key' => 'zip' ) );

        $destination = "{$street} {$city}, {$state} {$zip}";

        $directions = '<a href="https://maps.google.com/maps?saddr='.$location['city'].','.$location['region_full'].'&daddr='.$destination.'"target="_blank">Directions</a>';
    } else {
        $directions = null;
    }

    ?>
    <div class="venues-address-pane">
    <div class="content">
        <h3><?php print $venues->getAttribute( array( 'key' => 'title', 'venue_id' => $venue_id, 'echo' => true ) ); ?></h3>
        <?php $venues->getAttribute( array( 'key' => 'street', 'echo' => true ) ); ?>
        <br /><?php $venues->getAttribute( array( 'key' => 'city', 'echo' => true ) ); ?>,
        <?php $venues->getAttribute( array( 'key' => 'state', 'echo' => true ) ); ?>
        <?php $venues->getAttribute( array( 'key' => 'zip', 'echo' => true ) ); ?>
        <br />
        <?php print $directions; ?>
    </div>
</div><?php }


function zm_ev_venue_links_pane( $post_id=null ){

    global $post_type;

    if ( $post_type == 'events' ){
        $venue_id = Events::getVenueId( $post_id );
    } else {
        $venue_id = $post_id;
    }

    if ( get_option('zm_geo_location_version' ) ){
        $location = zm_geo_location_get();
        $directions = '<a href="https://maps.google.com/maps?saddr='.$location['city'].','.$location['region_full'].'&daddr='.Venues::getAttribute( array( 'key' => 'LatLong' ) ).'"target="_blank">Directions</a>';
    } else {
        $directions = null;
    }

    ?>
    <div class="venue-links-pane">
        <ul>
            <li class="website"><a href="<?php print Venues::getAttribute( array( 'key' => 'website' ) ); ?>" target="_blank">Website</a></li>
            <li class="directions"><?php print $directions; ?></li>
            <li class="venue"><?php print Events::getTrackLink( $post_id, 'Venue' ); ?>
            <span class="count">
                <?php if ( Venues::getSchedule( $venue_id ) ) {
                    print Venues::getSchedule( $venue_id )->post_count;
                } else {
                    print 0;
                }
                ?>
            </span>
            </li>
        </ul>
</div><?php }


/**
 * Gets the custom date for an Event given the current $post->ID.
 *
 * Either returns the date from the $prefix_postmeta table
 * for a single event OR for Events that span multiple dates
 * will return start date and end date.
 *
 * @param $post_id
 * @param $both bool, display start and end date, or just start date
 * @uses get_post_custom_values();
 */
function zm_event_date( $post_id=null, $both=true ){

    if ( is_null( $post_id ) ) {
        global $post;
        $post_id = $post->ID;
    }

    $start = get_post_meta( $post_id, 'events_start-date', true );
    $end = get_post_meta( $post_id, 'events_end-date', true );

    if ( $end && $both ){
        $date = date( 'M j', strtotime( $start ) ) . date( ' - M j, Y', strtotime( $end ) );
    } else {
        $date = date( 'M j, Y', strtotime( $start ) );
    }

    print $date;
}


function zm_ev_events_custom_header($columns) {
    return $columns
         + array('events_start-date' => __('Start Date'),
                 'events_end-date' => __('End Date'));
}
add_filter( 'manage_edit-events_columns', 'zm_ev_events_custom_header' );


function zm_ev_events_custom_column_content( $column, $post_id ) {
    switch ( $column ) {
      case 'events_start-date':
        echo get_post_meta( $post_id , 'events_start-date' , true );
        break;

      case 'events_end-date':
        echo get_post_meta( $post_id , 'events_end-date' , true );
        break;
    }
}
add_action( 'manage_events_posts_custom_column' , 'zm_ev_events_custom_column_content', 10, 2 );


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


function zm_ev_init(){
    $dependencies[] = 'jquery';
    wp_enqueue_script( 'zm-ev-tinymce-script', plugin_dir_url( __FILE__ ) . 'vendor/tinymce/jquery.tinymce.js', $dependencies  );
    add_action( 'wp_print_scripts', 'zm_ev_js_var_setup' );
}
add_action('init','zm_ev_init');

function zm_ev_settings(){
    if ( ! is_user_logged_in() ) return;
    global $current_user;
    ?><a href="<?php print site_url(); ?>/attendees/<?php print $current_user->user_login; ?>/settings/" class="zm-ev-settings-icon">Settings</a>
<?php }

/**
 * Save the settings, note this is called via ajax!
 */
function zm_ev_save_user_settings(){

    if ( empty( $_POST ) )
        return;

    global $current_user;
    get_currentuserinfo();

    if ( empty( $_POST['value'] ) ){
        print delete_user_meta( $current_user->ID, 'zm_' . $_POST['name'] . '_preference', $_POST['value'] );
    } else {
        if ( $_POST['name'] == 'user_email' ){
            print wp_update_user( array( 'ID' => $current_user->ID, 'zm_' . $_POST['name'] . '_preference' => $_POST['value'] ) );
        }
        print update_user_meta( $current_user->ID, 'zm_' . $_POST['name'] . '_preference', $_POST['value'] );
    }
    die();
}
add_action( 'wp_ajax_zm_ev_save_user_settings', 'zm_ev_save_user_settings' );
add_action( 'wp_ajax_nopriv_zm_ev_save_user_settings', 'zm_ev_save_user_settings');

function zm_ev_ga_code(){

    $localhosts = array(
        '127.0.0.1',
        'localhost'
        );

    if ( in_array( $_SERVER['REMOTE_ADDR'], $localhosts ) ) : ?>
        <!-- zM Google Analytics disabled for localhost: <?php print $_SERVER['REMOTE_ADDR']; ?> -->
    <?php else : ?>
        $zm_ev_google_anaylitcs_code = get_option('zm_ev_google_anaylitcs_code'); ?>
        <script type="text/javascript">
          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', '<?print $zm_ev_google_anaylitcs_code; ?>']);
          _gaq.push(['_trackPageview']);

          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();
        </script>
    <?php endif; ?>
<?php }
add_action( 'wp_footer', 'zm_ev_ga_code' );