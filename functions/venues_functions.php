<?php

/**
 *
 */
Class Venues extends zMCustomPostTypeBase {

    /**
     * @todo derive this?
     * yeah, i fucked up its in the db as track in post meta
     * but should be tracks
     */
    public $cpt;
    public static $state_list = array(
            'AL'=>"Alabama",
            'AK'=>"Alaska",
            'AZ'=>"Arizona",
            'AR'=>"Arkansas",
            'CA'=>"California",
            'CO'=>"Colorado",
            'CT'=>"Connecticut",
            'DE'=>"Delaware",
            'DC'=>"District Of Columbia",
            'FL'=>"Florida",
            'GA'=>"Georgia",
            'HI'=>"Hawaii",
            'ID'=>"Idaho",
            'IL'=>"Illinois",
            'IN'=>"Indiana",
            'IA'=>"Iowa",
            'KS'=>"Kansas",
            'KY'=>"Kentucky",
            'LA'=>"Louisiana",
            'ME'=>"Maine",
            'MD'=>"Maryland",
            'MA'=>"Massachusetts",
            'MI'=>"Michigan",
            'MN'=>"Minnesota",
            'MS'=>"Mississippi",
            'MO'=>"Missouri",
            'MT'=>"Montana",
            'NE'=>"Nebraska",
            'NV'=>"Nevada",
            'NH'=>"New Hampshire",
            'NJ'=>"New Jersey",
            'NM'=>"New Mexico",
            'NY'=>"New York",
            'NC'=>"North Carolina",
            'ND'=>"North Dakota",
            'OH'=>"Ohio",
            'OK'=>"Oklahoma",
            'OR'=>"Oregon",
            'PA'=>"Pennsylvania",
            'RI'=>"Rhode Island",
            'SC'=>"South Carolina",
            'SD'=>"South Dakota",
            'TN'=>"Tennessee",
            'TX'=>"Texas",
            'UT'=>"Utah",
            'VT'=>"Vermont",
            'VA'=>"Virginia",
            'WA'=>"Washington",
            'WV'=>"West Virginia",
            'WI'=>"Wisconsin",
            'WY'=>"Wyoming"
            );

    /**
     * @todo move this over to the abstract and model? as
     * part of the array in tracks.php?
     */
    public $has_many = 'events';

    static $instance;

    public function __construct(){

        // late static binding
        // allows use to use self::$instance->cpt when invoked
        // like Venues::someMethod();
        self::$instance = $this;
        $this->cpt = strtolower( __CLASS__ );
        /**
         * Our parent construct has the init's for register_post_type
         * register_taxonomy and many other usefullness.
         */
        parent::__construct();
    }

    /**
     * ALL Events for a given Event based on Track
     *
     * @param $venues_id == post id
     * @param $past wether to show past events, default returns all
     *
     * @todo getSchedule( $venue->ID, $show_past=true );
     *
     * @return queried object for ALL events based on a given venue
     */
    public function getSchedule( $venues_id=null, $past=true ){

        $venue_ids = json_decode( get_post_meta( $venues_id, 'bmx-race-event_id', true ) );

        if ( is_null( $venue_ids ) )
            return false;

        if ( $past ){
            $args = array(
            'post_type' => 'events',
            'post__in' => $venue_ids,
            'posts_per_page' => -1,
            'order' => 'ASC',
            'post_status' => 'publish',
            'orderby' => 'meta_value',
            'meta_key' => 'events_start-date'
            );
        } else {
            $args = array(
            'post_type' => 'events',
            'post__in' => $venue_ids,
            'posts_per_page' => -1,
            'order' => 'ASC',
            'post_status' => 'publish',
            'orderby' => 'meta_value',
            'meta_key' => 'events_start-date',
            'meta_query' => array(
                'relation' => 'AND',
                    array(
                        'key' => 'events_start-date',
                        'value' => date('Y-m-d'),
                        'type' => 'CHAR',
                        'compare' => '>='
                    )
                )
            );
        }

        $query = new WP_Query( $args );

        return $query;
    }

    /**
     * Return a drop down of local tracks
     *
     * @todo transient
     */
    public function locationDropDown( $current_id=null ){

        $query = new WP_Query( array(
            'post_type' => self::$instance->cpt,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
            )
        );

        $html = null;

        foreach( $query->posts as $posts ) {
            $html .= '<option value="'.$posts->ID.'" '.selected($current_id, $posts->ID, false).'>' . $posts->post_title.'</optoin>';
        }
        return '<select name="venues_id" class="chzn-select">'.$html.'</select>';
    }

    /**
     * Return Venue count
     * @todo transient
     */
    public function trackCount(){
        $count_posts = wp_count_posts( self::$instance->cpt );
        return $count_posts->publish;
    }

    /**
     * Return the number of states in the db
     * @todo transient
     * @todo derive 'tracks'_
     */
    public function stateCount(){

        global $wpdb;

        $sql = "SELECT count( distinct(`meta_value`) ) AS count FROM `{$wpdb->prefix}postmeta` WHERE `meta_key` LIKE '%venues_state%'";
        $count = $wpdb->get_results( $sql );

        return $count[0]->count ;
    }

    /**
     * Return the number of citys in the db
     * @todo transient
     * @todo derive 'tracks'_
     */
    public function cityCount() {
        global $wpdb;

        $sql = "SELECT count( distinct(`meta_value`) ) AS count FROM `{$wpdb->prefix}postmeta` WHERE `meta_key` LIKE '%venues_city%'";
        $count = $wpdb->get_results( $sql );

        return $count[0]->count ;
    }

    /**
     * @todo facade
     */
    public function getMetaField( $meta_field=null, $track_id=null, $echo=false ){
        if ( is_null( $track_id ) ) {
            global $post;

            if ( empty( $post ) )
                return;

            $track_id = $post->ID;
        }

        // @todo remove prefix $post_type from meta fields
        $field = self::$instance->cpt.'_'.$meta_field;
        $tmp = get_post_meta( $track_id, $field, true );

        // @todo -- yes, I really put "" into the db
        if ( empty( $tmp ) || $tmp == '""' || $tmp == '' ) {
            $tmp = get_post_meta( $track_id, $meta_field, true );
        }

        if ( $echo )
            print $tmp;
        else
            return $tmp;
    }

    public function getStreet( $track_id=null, $echo=false ){
        if ( is_null( $track_id ) ) {
            global $post;
            $track_id = $post->ID;
        }

        $field = self::$instance->cpt.'_street';

        if ( $echo )
            print get_post_meta( $track_id, $field, true );
        else
            return get_post_meta( $track_id, $field, true );
    }

    public function getWebsite( $track_id=null, $echo=false ){
        if ( is_null( $track_id ) ) {
            global $post;
            $track_id = $post->ID;
        }

        $field = self::$instance->cpt.'_website';

        if ( $echo )
            print get_post_meta( $track_id, $field, true );
        else
            return get_post_meta( $track_id, $field, true );
    }

    public static function getLatLon( $track_id=null, $echo=false ){

        $lat = self::$instance->getMetaField( 'lat', $track_id );

        if ( empty( $lat ) ) {
            return false;
        } else {
            $lon = self::$instance->getMetaField( 'long', $track_id );

            if ( $echo )
                print $lat . ',' . $lon;
            else
                return $lat . ',' . $lon;
        }
    }

    // Really needs to be called "coast"
    public function getRegion( $track_id=null ){

        if ( is_null( $track_id ) ) {
            global $post;
            $track_id = $post->ID;
        }

        return zm_ev_get_tax_term( array( 'post_id' => $track_id, 'taxonomy' => 'region' ) );
    }

    public function getState( $track_id=null ){

        global $post;

        if ( is_null( $track_id ) && $post->post_type == 'events' ){
            $track_id = Events::getTrackId( $post->ID );
        } else {
            $track_id = $post->ID;
        }

        return get_post_meta( $track_id, 'venues' . '_state', true );
    }

    public function getCity( $track_id=null ){

        global $post;
global $post_type;
// var_dump( $post_type );

        if ( is_null( $track_id ) && $post->post_type == 'events' ){
            $track_id = Events::getTrackId( $post->ID );
        } else {
            $track_id = $post->ID;
        }

        return get_post_meta( $track_id, 'venues' . '_city', true );
    }

    public function getTags( $track_id=null ){

        if ( is_null( $track_id ) ) {
            global $post;
            $track_id = $post->ID;
        }

        return Helpers::getTaxTerm( array( 'post_id' => $track_id, 'taxonomy' => 'tracks_tags' ) );
    }

    /**
     * You give me state, I give you abbreviation!
     */
    public function stateByAbbreviation( $abbr=null ){

        if ( is_null( $abbr ) )
            die('need abbr');

        $state_list = array(
            'AL'=>"Alabama",
            'AK'=>"Alaska",
            'AZ'=>"Arizona",
            'AR'=>"Arkansas",
            'CA'=>"California",
            'CO'=>"Colorado",
            'CT'=>"Connecticut",
            'DE'=>"Delaware",
            'DC'=>"District Of Columbia",
            'FL'=>"Florida",
            'GA'=>"Georgia",
            'HI'=>"Hawaii",
            'ID'=>"Idaho",
            'IL'=>"Illinois",
            'IN'=>"Indiana",
            'IA'=>"Iowa",
            'KS'=>"Kansas",
            'KY'=>"Kentucky",
            'LA'=>"Louisiana",
            'ME'=>"Maine",
            'MD'=>"Maryland",
            'MA'=>"Massachusetts",
            'MI'=>"Michigan",
            'MN'=>"Minnesota",
            'MS'=>"Mississippi",
            'MO'=>"Missouri",
            'MT'=>"Montana",
            'NE'=>"Nebraska",
            'NV'=>"Nevada",
            'NH'=>"New Hampshire",
            'NJ'=>"New Jersey",
            'NM'=>"New Mexico",
            'NY'=>"New York",
            'NC'=>"North Carolina",
            'ND'=>"North Dakota",
            'OH'=>"Ohio",
            'OK'=>"Oklahoma",
            'OR'=>"Oregon",
            'PA'=>"Pennsylvania",
            'RI'=>"Rhode Island",
            'SC'=>"South Carolina",
            'SD'=>"South Dakota",
            'TN'=>"Tennessee",
            'TX'=>"Texas",
            'UT'=>"Utah",
            'VT'=>"Vermont",
            'VA'=>"Virginia",
            'WA'=>"Washington",
            'WV'=>"West Virginia",
            'WI'=>"Wisconsin",
            'WY'=>"Wyoming"
            );
        if(!empty($state_list[$abbr])) {
            $state_name = $state_list[$abbr];
        } else {
            $state_name = "Unknown";
        }

        return $state_name;
    }

    /**
     * Retrive image from google and save it to assets/map/ dir
     *
     * @return file size on success false if not.
     */
    public function saveMapImage( $track_id=null, $google_image_url=null, $size=null ){
        $path =  '/var/www/html/images' . DS . 'maps' . DS . 'staticmap_'.$size.'_' . $track_id . '.png';

        $google_image = file_get_contents( $google_image_url );
        $my_image = file_put_contents( $path, $google_image );
// var_dump( $google_image );
// var_dump( $my_image );
        return $my_image;
    }

    public function updateMapImageMeta( $track_id=null, $url=null, $size=null ){
        return update_post_meta( $track_id, 'tracks_map_'.$size, $url );
    }

    /**
     * Returns the ID of all Venues in a given Region (full region)
     * @param $region = 'maryland'
     * @todo transient
     */
    public function getVenueByRegion( $region=null ){
        $args = array(
            'post_type' => $this->cpt,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'venues_state',
                    'value' => $region,
                    'compare' => '='
                    )
                )
            );
        $query = new WP_Query( $args );
        return $query->posts;
    }

    /**
     * Determine local venues based on current location.
     *
     * @return Full query_posts for local venues.
     */
    public function getLocalVenues(){
        $location = bmx_rs_get_user_location();
        return $this->getVenueByRegion( $location['region_full'] );
    }

    /**
     * Return a random Local Venue ID
     *
     * Useful for showing a random venue based on location.
     */
    public function randomId(){
        $obj_local_venues = new Venues;
        $local_venues = $obj_local_venues->getLocalVenues();

        $venue_ids = array();

        foreach( $local_venues as $venue ){
            $venue_ids[] = $venue->ID;
        }
        return Helpers::makeRandom( $venue_ids );
    }

    /**
     * Returns a object of ALL venues ids
     */
    public function IDs(){
        global $wpdb;

        $query = "select ID from {$wpdb->prefix}posts where post_type = 'venues' and post_status = 'publish'";
        $tmp = array();

        foreach ( $wpdb->get_results( $query ) as $wtf ){
            $tmp[] = $wtf->ID;
        }
        return $tmp;
    }

    /**
     * Return the name, i.e., title
     * @param $id
     */
    public function getName( $id ){
        return get_the_title( $id );
    }

    /**
     * Returns the contacts email address
     * @param $id
     */
    public function contactEmail( $id ){
        return filter_var( get_post_meta( $id, "venues_email", true ), FILTER_VALIDATE_EMAIL );
    }

    /**
     * Retrive ALL Tracks and order by Track Title ASC
     */
    static public function venues( $preview=true ){

        $post_type = 'venues';

        $args = array(
            'posts_per_page' => -1,
            'post_type' => $post_type,
            'post_status' => 'publish',
            'order' => 'ASC',
            'orderby' => 'title'
            );

        $query = new WP_Query( $args );
        $tracks_obj = new Venues;
        $tracks = array();

        foreach( $query->posts as $post ) {
            $this_event = array();

            $this_event['ID'] = $post->ID;
            $this_event['t'] = $post->post_title;
            $this_event['u'] = '/'.$post_type.'/'.$post->post_name . '/';

            $tmp_city = get_post_meta( $post->ID, $post_type . '_city', true );
            $tmp_state = get_post_meta( $post->ID, $post_type . '_state', true );
            $tmp_lat = get_post_meta( $post->ID, 'lat', true );
            $tmp_long = get_post_meta( $post->ID, 'long', true );
            $tmp_street = get_post_meta( $post->ID, $post_type . '_street', true );
            $tmp_region = $tracks_obj->getRegion( $post->ID );
            $tmp_tags = $tracks_obj->getTags( $post->ID );
            $tmp_schedule = $tracks_obj->getSchedule( $post->ID );
            $tmp_website = get_post_meta( $post->ID, $post_type . '_website', true );

            if ( $tmp_city )
                $this_event['c'] = $tmp_city;

            if ( $tmp_state )
                $this_event['s'] = $tmp_state;

            if ( $tmp_lat )
                $this_event['l'] = $tmp_lat;

            if ( $tmp_long )
                $this_event['lo'] = $tmp_long;

            if ( $tmp_street )
                $this_event['st'] = $tmp_street;

            if ( $tmp_website )
                $this_event['w'] = $tmp_website;

            if ( $tmp_region )
                $this_event['r'] = $tmp_region;

            if ( $tmp_tags )
                $this_event['ta'] = $tmp_tags;

            if ( $tmp_schedule )
                $event_count = $tmp_schedule->post_count;

            $this_event['ec'] = $event_count;
            $this_event['s_u'] = $tracks_obj->getMapImage( $post->ID, 'small', $uri=true );
            $this_event['m_u'] = $tracks_obj->getMapImage( $post->ID, 'medium', $uri=true );

            $tracks[] = $this_event;
        }

        if ( $preview ) {
            print '<pre>';
            print_r( $tracks );
            print '</pre>';
        } else {
            $file = file_put_contents( TMP_RACES_DIR . 'venues.json', json_encode( $tracks ) );
            if ( $file )
                print "File created, size: {$file}\n";
        }
    }
}