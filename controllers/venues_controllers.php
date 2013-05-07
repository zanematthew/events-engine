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
    public $state_list = array(
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
        parent::__construct();

        if ( is_admin() ){
            add_filter( 'manage_edit-'.$this->cpt.'_columns', array( &$this, 'customHeader' ) );
            add_action( 'manage_'.$this->cpt.'_posts_custom_column' , array( &$this, 'customContent' ), 10, 2 );
        }
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

        $venue_ids = get_post_meta( $venues_id, 'events_id', true );

        if ( empty( $venue_ids ) )
            return false;

        $args = array(
            'post_type' => 'events',
            'post__in' => $venue_ids,
            'posts_per_page' => -1,
            'order' => 'ASC',
            'post_status' => 'publish',
            'orderby' => 'meta_value'
            );

        if ( $past ){
            $args['meta_key'] = 'events_start-date';
        } else {
            $args['meta_query'] = array(
                'relation' => 'AND',
                    array(
                        'key' => 'events_start-date',
                        'value' => date('Y-m-d'),
                        'type' => 'CHAR',
                        'compare' => '>='
                    )
                );
        }

        $query = new WP_Query( $args );

        return $query;
    }

    public function scheduleCount( $venues_id=null ){
        $count = Venues::getSchedule( $venues_id );
        if ( $count )
            $html = $count->post_count;
        else
            $html = 0;
        print '<span class="count">' . $html . '</span>';
    }

    public function allQuery(){
        $query = new WP_Query( array(
            'post_type' => self::$instance->cpt,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
            )
        );
        return $query->posts;
    }

    /**
     * Return a drop down of local tracks
     *
     * @todo transient
     */
    public function locationDropDown( $current_id=null ){

        $venues = $this->allQuery();

        $html = '<option>-- Choose a Venue --</option>';
        foreach( $venues as $posts ) {
            $html .= '<option value="'.$posts->ID.'" '.selected($current_id, $posts->ID, false).'>' . $posts->post_title.'</optoin>';
        }
        // return '<select name="venues_id" class="chzn-select"  data-placeholder="Choose a Venue..." style="width: 700px;">'.$html.'</select>';
        return '<select name="venues_id" class="chzn-select"  data-placeholder="Choose a Venue...">'.$html.'</select>';
    }

    /**
     * Prints out the html needed for a multi-select of
     * locations.
     */
    public function locationSelect( $current=null ){

        $venues_obj = $this->allQuery();
        foreach( $venues_obj as $v ){
            $tmp['id'] = $v->ID;
            $tmp['name'] = $v->post_title;
            $items[] = $tmp;
        }
        $key = 'venues';

        $args = array(
            'extra_data' => 'data-allows-new-values="true" style="width: 700px;" data-placeholder="Choose a Venue..."',
            'extra_class' => 'chzn-select',
            'label' => ucfirst( $key ),
            'multiple' => true,
            'current' => $current, // list of IDs
            'items' => $items,
            'key' => $key
        );

        zm_base_build_select( $args );
    }

    /**
     * Retrive the total number of Events
     *
     * @param $echo Either return the results or print them
     * @return Count of events (or prints)
     * @todo transient
     */
    public function venueCount( $echo=true ){
        $count_posts = wp_count_posts( self::$instance->cpt );
        if ( $echo ){
            print $count_posts->publish . __( ' venues', 'zm_events_venue' );
        } else {
            return $count_posts->publish;
        }
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


    public function getTags( $track_id=null ){

        if ( is_null( $track_id ) ) {
            global $post;
            $track_id = $post->ID;
        }

        return Helpers::getTaxTerm( array( 'post_id' => $track_id, 'taxonomy' => 'tracks_tags' ) );
    }

    /**
     * You give me state, I give you abbreviation!
     * You give me state abbr, I give you state!
     */
    public function stateByAbbreviation( $abbr=null ){

        if ( is_null( $abbr ) )
            die('need abbr');

        if ( strlen( $abbr ) == 2 ){
            $abbr = strtoupper( $abbr );

            if ( ! empty( $this->state_list[$abbr] ) ) {
                $state = $this->state_list[$abbr];
            }
        } else {
            $state = array_search( $abbr, $this->state_list );
        }

        return $state;
    }

    /**
     * Removes an Event from a Venues schedule
     *
     * @param $venues_id (int) The Venues to derive the schedule from.
     * @param $events_id (int) The Event to be removed.
     */
    public function removeEventFromSchedule( $venues_id=null, $events_id=null ){

        $current_schedule = get_post_meta( $venues_id, 'events_id', true );

        if ( in_array( $events_id, $current_schedule) ){
            $index = array_search( $events_id, $current_schedule );

            unset( $current_schedule[ $index ] );
            $current_schedule = array_values( $current_schedule );

            return update_post_meta( $venues_id, 'events_id', $current_schedule );
        }
    }


    /**
     * Add or change an Event to the Venues schedule
     *
     * @param $venues_id (int) The Venues ID
     * @param $events_id (int) The Events ID
     * @param $previous_venues_id (int) The previous Venues ID, this is used when
     * an Event is changing Venues.
     */
    public function updateSchedule( $venues_id=null, $events_id=null, $previous_venues_id=null ){

        // If we have a previous venues ID, we assume this event has CHANGED
        // VENUES! And we remove it from the previous venues schedule!
        if ( ! empty( $previous_venues_id ) ){
            $this->removeEventFromSchedule( $previous_venues_id, $events_id );
        }

        $current_schedule = get_post_meta( $venues_id, 'events_id', true );

        // This event is in our schedule already do nothing
        if ( $current_schedule && in_array( $events_id, $current_schedule ) ){
            return;
        }

        // Do we have a current schedule?
        // Add the new event to our current schedule
        if ( $current_schedule ){
            $current_schedule[] = $events_id;
            $schedule = $current_schedule;
        } else {
            // Create our schedule and add our event to it
            $new_schedule = array();
            $new_schedule[] = $events_id;
            $schedule = $new_schedule;
        }

        return update_post_meta( $venues_id, 'events_id', $schedule );
    }

    /**
     * Returns ALL Venues in a given region, i.e. coast
     *
     * @param $region (string/array) i.e., east|west|central
     * @todo transient
     */
    public function getVenueByRegion( $region=null ){

        $args = array(
            'post_type' => $this->cpt,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'region',
                    'field' => 'slug',
                    'terms' => $region
                    )
                )
            );


        $query = new WP_Query( $args );

        if ( $query->post_count == 0 )
            return false;
        else
            return $query->posts;
    }

    /**
     * Returns all Venues in a given state.
     *
     * @param $state_abbr (string/array)
     * @todo transient
     */
    public function getVenueByState( $state=null ){

        if ( ! is_array( $state ) && strlen( $state ) != 2 ){
            $state_abbr = $this->stateByAbbreviation( $state );
        } else {
            $state_abbr = $state;
        }

        $args = array(
            'post_type' => $this->cpt,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                 array(
                     'key' => 'venues_state',
                     'value' => $state_abbr,
                     'compare' => 'IN'
                     )
                 ),
            'orderby' => 'meta_value',
            'meta_key' => 'venues_state'
            );

        $query = new WP_Query( $args );
        wp_reset_postdata();

        if ( $query->post_count == 0 )
            return false;
        else
            return $query->posts;
    }

    public function getVenueIdByState( $state_abbr=null ){
        /**
         * Once we have the arguments build we run the query and
         * build an array of post IDs.
         */
        $tmp_venues_ids = array();
        foreach( $this->getVenueByState( $state_abbr ) as $venues ){
            $tmp_venues_ids[] = $venues->ID;
        }

        return $tmp_venues_ids;
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

    public function getAttribute( $params=array() ){

        extract( $params );

        if ( empty( $key ) ) die( "Keys open doors!" );

        // If the venues_id is not passed in we assume that are our global
        // post is an Event, therefore we must get the venues_id for the
        // current post.
        global $post;

        if ( ! empty( $post ) && $post->post_type == 'events' ){
            $venues_id = Events::getVenueId( $post->ID );
        } elseif( ! empty( $post ) && $post->post_type == 'venues' ) {
            $venues_id = $post->ID;
        } else {
            $venues_id = $venues_id;
        }

        switch ( $key ) {
            case 'city':
            case 'state':
            case 'email':
            case 'website':
            case 'street':
            case 'phone':
            case 'zip':
            case 'lat':
            case 'long':
                $field = get_post_meta( $venues_id, self::$instance->cpt . '_' . $key, true );
                break;
            case 'LatLong':
                $lat = get_post_meta( $venues_id, 'lat', true );
                $long = get_post_meta( $venues_id, 'long', true );
                $field = $lat . ',' . $long;
                break;
            case 'title':
                $field = get_the_title( $venues_id );
                break;
            case 'region': // Really is "coast"
                $field = zm_ev_get_tax_term( array( 'post_id' => $venues_id, 'taxonomy' => 'region' ) );
                break;
            default:
                # code...
                break;
        }

        if ( empty( $echo ) )
            return $field;
        else
            print '<span class="'.$key.'">' . $field . '</span>';
    }


    /**
     * Return array of meta keys from the Database, not based
     * on naming convention! "{$post_type}_{$meta_key}"
     */
    static public function getMetaKeys(){

        global $wpdb;

        $cpt = self::$instance->cpt;

        $results = $wpdb->get_results( "select distinct( meta_key ) from {$wpdb->prefix}postmeta where meta_key like '%venues%' ORDER BY meta_key ASC;" );
        $tmp = array();

        foreach( $results as $result ){
            $tmp[] = $result->meta_key;
        }

        return $tmp;
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
            $tmp_region = $tracks_obj->getAttribute( array( 'venue_id' => $post->ID ) );
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

    /**
     * Prints out an select box of states.
     *
     * @param $current (string/array) The current item.
     * @param $all (bool) Either print states that have no Veneus.
     * @uses zm_base_build_select() To build select
     */
    public function stateSelect( $current=null, $all=true ){

        $key = 'state';
        $items = array();

        if ( $all ){
            foreach( $this->state_list as $abbr => $state ){
                $tmp_items['id'] = $abbr;
                $tmp_items['name'] = $state;
                $items[] = $tmp_items;
            }
        } else {
            global $wpdb;
            $tmp_r = $wpdb->get_col( "SELECT distinct( upper( `meta_value` ) ) FROM {$wpdb->prefix}postmeta WHERE `meta_key` LIKE '{$this->cpt}_state' ORDER BY `meta_value` ASC;" );
            foreach( $tmp_r as $abbr ){
                if ( ! in_array( $abbr, $this->state_list ) ) {
                    if ( $this->stateByAbbreviation( $abbr ) == "Unknown" ) continue;
                    $tmp_items['id'] = $abbr;
                    $tmp_items['name'] = $this->stateByAbbreviation( $abbr );
                    $items[] = $tmp_items;
                }
            }
        }

        $args = array(
            'extra_data' => 'data-allows-new-values="true" style="width: 700px;" data-placeholder="Choose a State..."',
            'extra_class' => 'chzn-select',
            'current' => $current,
            'multiple' => true,
            'items' => $items,
            'key' => $key,
            'label' => 'State'
            );

        zm_base_build_select( $args );
    }

    public function customHeader($columns) {
        return $columns + array('venues_event_count' => __('Event Count') );
    }


    public function customContent( $column, $post_id ) {
        switch ( $column ) {
        case 'venues_event_count':
            print $this->scheduleCount( $post_id );
            break;
        }
    }

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
