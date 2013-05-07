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

function zm_user_setting_link( $text='Personalize' ){
    if ( is_user_logged_in() ){
        $current_user = wp_get_current_user();
        $href = site_url() .'/attendees/' . $current_user->user_login . '/settings/';
        $class = null;
    } else {
        $class = 'zm-login-handle';
        $href = null;
    }
    return '<a href="'.$href.'" class="'.$class.'"> ' . $text . ' </a>';
}

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