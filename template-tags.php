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
