<?php

// Functions to be used in themes files

function zm_ev_settings(){
    if ( ! is_user_logged_in() ) return;
    global $current_user;
    ?><a href="<?php print site_url(); ?>/attendees/<?php print $current_user->user_login; ?>/settings/" class="zm-ev-settings-icon">Settings</a>
<?php }


function zm_ev_ga_code(){

    $localhosts = array(
        '127.0.0.1',
        'localhost'
        );

    if ( in_array( $_SERVER['REMOTE_ADDR'], $localhosts ) ) {
        print '<!-- zM Google Analytics disabled for localhost: ' . $_SERVER['REMOTE_ADDR'] . '-->';
    } else {
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
    <?php }
}
add_action( 'wp_footer', 'zm_ev_ga_code' );

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

// I think we did this before? Check later
function zm_venues_by_region_tmp( $state_abbr=null ){
    $args['post_type'] = 'venues';
    $args['meta_query'] = array(
        array(
            'key' => 'venues_state',
            'value' => $state_abbr,
            'compare' => 'IN'
            )
        );

    /**
     * Once we have the arguments build we run the query and
     * build an array of post IDs.
     */
    $venues_by_region = New WP_Query( $args );
    $tmp_venues_ids = array();
    foreach( $venues_by_region->posts as $venues ){
        $tmp_venues_ids[] = $venues->ID;
    }

    unset( $args['meta_query'] );
    wp_reset_postdata();
    return $tmp_venues_ids;
}

function zm_ev_user_state_pref( $state_pref=null ){

    $venues = New Venues;
    $count = count( $state_pref );
    $i = 0;
    $current_user = wp_get_current_user();

    $html = '<div class="row"><div class="padding"><div class="alert alert-success"><strong>States</strong> ';
    foreach( $state_pref as $state_abbr ) {
        $state = $venues->stateByAbbreviation( $state_abbr );
        $html .= "<em>{$state}</em>";
        $i++;
        if ( $i != $count ){
            $html .= ", ";
        }
    }

    $html .= '<a href="' . site_url() .'/attendees/' . $current_user->user_login . '/settings/"> Settings</a></div></div></div>';
    print $html;
}

function zm_ev_user_venue_pref( $venues_id=null ){
    $i = 0;
    $count = count( $venues_id );
    $current_user = wp_get_current_user();
    $html = '<div class="row"><div class="padding"><div class="alert alert-success"><strong>Venues</strong> ';
    foreach( $venues_id as $id ){
        $html .= get_the_title( $id );
        $i++;
        if ( $i != $count ){
            $html .= ", ";
        }
    }
    $html .= '<a href="' . site_url() .'/attendees/' . $current_user->user_login . '/settings/"> Settings</a></div></div></div>';
    print $html;
}

function zm_ev_user_type_pref( $type_ids=null ){
    $i = 0;
    $count = count( $type_ids );
    $current_user = wp_get_current_user();
    $html = '<div class="row"><div class="padding"><div class="alert alert-success"><strong>types</strong> ';

    foreach( $type_ids as $id ){
        $terms = get_term_by('id', $id, 'type' );
        $i++;
        $html .= $terms->name;
        if ( $i != $count ){
            $html .= ", ";
        }
    }
    $html .= '<a href="' . site_url() .'/attendees/' . $current_user->user_login . '/settings/"> Settings</a></div></div></div>';
    print $html;
}

function zm_ev_venues_by_user_pref_args( $cpt=null ){

    $current_user = wp_get_current_user();
    $zm_state_preference = get_user_meta( $current_user->ID, 'zm_state_preference', true );
    $zm_venues_id_preference = get_user_meta( $current_user->ID, 'zm_venue_preference', true );
    $zm_type_ids_preference = get_user_meta( $current_user->ID, 'zm_type_preference', true );

    // start our shared arguments
    $args = array(
        'post_status' => 'publish',
        'posts_per_page' => -1
        );

    if ( $zm_state_preference && $zm_type_ids_preference && $cpt == 'events' ){
        /**
         * If we have a state pref. and type pref. and post is Events
         */
        $tmp_venues_ids = zm_venues_by_region_tmp( $zm_state_preference );
        $args['post_type'] = 'events';
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'type',
                'field' => 'id',
                'terms' => $zm_type_ids_preference,
                'operator' => 'IN'
                )
            );
        $args['meta_query'] = array(
            'relation' => 'AND',
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
print '<pre>';
print_r( $args );
print '</pre>';
        zm_ev_user_type_pref( $zm_type_ids_preference );
        zm_ev_user_state_pref( $zm_state_preference );
    } elseif ( $zm_type_ids_preference && $cpt == 'events' ){
        $args['post_type'] = 'events';
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'type',
                'field' => 'id',
                'terms' => $zm_type_ids_preference
                )
            );
        zm_ev_user_type_pref( $zm_type_ids_preference );
    } elseif ( $zm_state_preference && $zm_venues_id_preference ) {
        $tmp_venues_ids = zm_venues_by_region_tmp( $zm_state_preference );
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

        zm_ev_user_state_pref( $zm_state_preference );
        zm_ev_user_venue_pref( $zm_venues_id_preference );
    } elseif ( $zm_state_preference && $cpt == 'venues' ){
        /**
         * If we have no state pref. and our post type is Venues
         * Select Venues by user state pref.
         */
        $args['post_type'] = $cpt;
        $args['meta_query'] = array(
            array(
                'key' => 'venues_state',
                'value' => $zm_state_preference,
                'compare' => 'IN'
                )
            );
        zm_ev_user_state_pref( $zm_state_preference );
    } elseif ( $zm_state_preference && $cpt == 'events' ){
        /**
         * Since we can't query Events by state we need to query
         * Venues by state, returing a list of Venue IDs. Then
         * query Events by Venues IDs. So we build a meta query
         * by post_type of Venues where the meta key "venues_state"
         * is any value from our state pref.
         */
        $tmp_venues_ids = zm_venues_by_region_tmp( $zm_state_preference );

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
        zm_ev_user_state_pref( $zm_state_preference );
    } else {
        print "Nothing set in settigns.";
        /**
         * If we have no state or venue preference just return a query of
         * either Events or Venues.
         */
        return get_posts( array( 'post_type' => array( $cpt ), 'post_status' => 'publish' ) );
    }

    $my_query = New WP_Query( $args );
    return $my_query->posts;
}