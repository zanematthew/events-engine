<?php
/**
 * Our class
 *
 * @todo: class name should be mapped dir file name.
 * custom-post-type/bmx-race-schedule/
 * new BMX_Race_Shedule();
 * post-type/bmx-race-schedule/class.php
 */
class Events extends zMCustomPostTypeBase {

    private static $instance;
    private $my_cpt;
    public $my_path;

    /**
     * Every thing that is "custom" to our CPT goes here.
     */
    public function __construct() {

        self::$instance = $this;
        parent::__construct();

        $this->my_cpt = strtolower( get_class( self::$instance ) );

        if ( is_admin() ){
            add_action( 'add_meta_boxes', array( &$this, 'locationMetaField' ) );
            // add_action( 'date_save', array( &$this, 'eventDateSave') );
            add_action( 'save_post', array( &$this, 'myplugin_save_postdata' ) );

            add_action( 'wp_ajax_feedPreviewNew', array( &$this, 'feedPreviewNew' ) );
        }

        register_activation_hook( __FILE__, array( &$this, 'registerActivation') );

        add_action( 'wp_ajax_nopriv_postTypeSubmit', array( &$this, 'postTypeSubmit' ) );
        add_action( 'wp_ajax_postTypeSubmit', array( &$this, 'postTypeSubmit' ) );

        add_action( 'before_delete_post', array( &$this, 'beforeDeletePost') );
    }

    public function adminJsCss(){

        if ( isset( $_GET['post_type'] ) ){
            $post_type = $_GET['post_type'];
        } else {
            global $post_type;
        }

        if ( $this->my_cpt != $post_type ){
            return;
        }
    }

    /**
     * Activation Method -- Insert a sample BMX Race Schedule, a few terms
     * with descriptions and assign our sample Race Schedule to some terms.
     *
     * Note: This is completly optional BUT must be present! i.e.
     * public function registerActivation() {} is completly valid
     *
     * BEFORE! taxonomies are regsitered! therefore
     * these terms and taxonomies are NOT derived from our object!
     * Set to we know its been installed at least once before
     *
     * @uses get_option()
     * @uses get_current_user_id()
     * @uses wp_insert_term()
     * @uses wp_insert_post()
     * @uses term_exists()
     * @uses wp_set_post_terms()
     * @uses update_option()
     */
    public function registerActivation() {

        $installed = get_option( 'zm_brs_number_installed' );

        if ( $installed == '1' ) {
            return;
        }

        $this->registerTaxonomy( $_zm_taxonomies );

        $author_ID = get_current_user_id();

        $inserted_term = wp_insert_term( 'Triple Point',   'point-scale', array( 'description' => 'Normally a higher rider count and more higher races fees.', 'slug' => 'triple-point') );
        $inserted_term = wp_insert_term( 'Double Point',   'point-scale', array( 'description' => 'Larger turn out then a local race.', 'slug' => 'double-point') );
        $inserted_term = wp_insert_term( 'Single Point',   'point-scale', array( 'description' => 'A normal BMX race.', 'slug' => 'single-point') );
        $inserted_term = wp_insert_term( 'Chesapeake BMX', 'track',       array( 'description' => 'Marylands BMX track', 'slug' => 'chesapeake-bmx') );
        $inserted_term = wp_insert_term( 'Severn',         'city',        array( 'description' => 'my city', 'slug' => 'severn') );
        $inserted_term = wp_insert_term( 'Maryland',       'state',       array( 'description' => 'my state', 'slug' => 'maryland') );

        $post = array(
            'post_title'   => 'Maryland State Championship',
            'post_excerpt' => 'Come out and checkout out State Championship race!',
            'post_author'  => $author_ID,
            'post_type'    => $this->my_cpt,
            'post_status'  => 'publish'
        );

        $post_id = wp_insert_post( $post, true );

        if ( isset( $post_id ) ) {
            $term_id = term_exists( 'Double Point', 'point-scale' );
            wp_set_post_terms( $post_id, $term_id, 'point-scale' );

            $term_id = term_exists( 'Chesapeake BMX', 'track' );
            wp_set_post_terms( $post_id, $term_id, 'track' );

            $term_id = term_exists( 'Maryland', 'state' );
            wp_set_post_terms( $post_id, $term_id, 'state' );

            $term_id = term_exists( 'Severn', 'city' );
            wp_set_post_terms( $post_id, $term_id, 'city' );

            update_option( 'zm_brs_number_installed', '1' );
        }
    } // End 'registerActivation'

    /**
     * Assign the current directory into a variable
     */
    public function myPath(){
        return $this->my_path = plugin_dir_path( __FILE__ );
    }

    /**
     * Custom Post Submission, note we are overriding the default method
     * in zm-cpt/abstract.php
     *
     * @package Ajax
     *
     * @uses wp_insert_post();
     * @uses get_current_user_id()
     * @uses is_user_logged_in()
     * @uses is_wp_error()
     * @uses check_ajax_referer()
     */
    public function postTypeSubmit() {

        // Lame, we need this snippet cause this method is PUBLIC!
        // hence its called by everyone!
        if ( $_POST['post_type'] != $this->my_cpt )
            return;

        // Verify nonce
        zm_easy_cpt_verify_post_submission( $_POST['post_type'] );

        $html = null;

        if ( ! is_user_logged_in() ){
            $html .= '<div class="error-container">';
            $html .= '<div class="message">';
            $html .= '<p style="">Please <a href="#" class="login-handle" data-template="views/shared/login.html.php" data-original-title="">login</a> or <a href="#" class="register-handle" data-original-title="">register</a> to create an event.</p>';
            $html .= '</div>';
            $html .= '</div>';
            print $html;
            die();
        }

        $error = null;

        if ( empty( $_POST['post_title'] ) ) {
            $error .= '<div class="error-message">Please enter a <em>title</em>.</div>';
        }

        if ( ! is_null( $error ) ) {
            print '<div class="error-container">' . $error . '</div>';
            exit;
        }

        $author_ID = get_current_user_id();

        $post = array(
            'post_title' => $_POST['post_title'],
            'post_content' => $_POST['content'],
            'post_author' => $author_ID,
            'post_type' => $_POST['post_type'],
            'post_date' => date( 'Y-m-d H:i:s' ),
            'post_status' => 'publish'
        );

        $entry_fee = sprintf("%01.2f", $_POST['entry_fee']);

        $post_id = wp_insert_post( $post, true );

        // eventDateSave
        // do_action('date_save', $post_id);

        $title = $_POST['post_title'];
        $tracks_id = $_POST['venues_id'];
        $start_date = $_POST['events_start-date'];
        $end_date = $_POST['events_end-date'];

        // should be white listed
        // We'll trust anything left over is our tax => term
        unset( $_POST['action'] );
        unset( $_POST['security'] );
        unset( $_POST['post_type'] );

        unset( $_POST['start_date'] );
        unset( $_POST['end_date'] );

        unset( $_POST['content'] );

        unset( $_POST['post_title'] );
        unset( $_POST['entry_fee'] );
        unset( $_POST['excerpt'] );
        unset( $_POST['tracks_id'] );

        unset( $_POST['events_start-date'] );
        unset( $_POST['events_end-date'] );

        if ( is_wp_error( $post_id ) ) {
            print_r( $post_id->get_error_message() );
            print_r( $post_id->get_error_messages() );
            print_r( $post_id->get_error_data() );
            return;
        } else {
            $link = get_permalink( $post_id );

            $html = null;

            $twitter = '<!-- Twitter --><a href="https://twitter.com/share" class="twitter-share-button" data-url="'.$link.'" data-text="Check out! '.$title.'">Tweet</a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';

            $this->updateVenue( $post_id, $tracks_id );
            Venues::updateSchedule( $tracks_id, $post_id );

            $this->updateStartEndDate( $post_id, $start_date, $end_date );
            $this->updateEntryFee( $post_id, $entry_fee );


            // Associate this post with an attachment_id if we have one.
            if ( isset( $_POST['attachment_id'] ) ) {
                $this->updateAttachmentId( $post_id, $_POST['attachment_id'] );
                unset( $_POST['attachment_id'] );
            }

            $html .= '<div class="success-container">';
            $html .= '<div class="message">';
            $html .= '<p style="margin-bottom: 10px;">Saved!</p>';
            $html .= $twitter.'<input type="text" value="'.$link.'" class="share-link" />';
            $html .= '</div>';
            $html .= '</div>';

            print $html;
        }

        // Remember we "trust" whats left over from $_POST to be taxes
        // $v = term, $k = taxonomy
        foreach( $_POST as $taxonomy => $term ) {
            wp_set_post_terms( $post_id, $term, $taxonomy );
        }

        die();
    } // End 'postTypeSubmit'

    /**
     * Updates the 'utiltily', i.e. taxonomies and date.
     *
     * NOTE we are overriding the default method
     *
     * @package Ajax
     *
     * @param (int)post id, (array)taxonomies
     *
     * @uses is_user_logged_in()
     * @uses current_user_can()
     * @uses wp_set_post_terms()
     *
     * @todo add chcek_ajax_refer()
     */
    public function defaultUtilityUpdate( $post_id=null, $taxonomies=null) {

        if ( !is_user_logged_in() )
            return false;

        if ( current_user_can( 'publish_posts' ) )
            $status = 'publish';
        else
            $status = 'pending';

        $post_id = (int)$_POST['PostID'];

        // $date = strtotime( $_POST['my_month'] . ' ' . $_POST['my_day'] . ' ' . $_POST['my_year'] . ' ' . $_POST['my_time']);
        // $date = date( 'Y-m-d H:i:s', $date );

        do_action('date_save', $_POST['PostID'] );

        $current_user = wp_get_current_user();
        $author = $current_user->ID;

        $post = array(
            'ID'            => $post_id,
            'post_author'   => wp_get_current_user()->ID,
            'post_date' => date( 'Y-m-d H:i:s' ),
            'post_modified' => current_time('mysql')
        );

        $update = wp_update_post( $post );

        unset( $_POST['action'] );
        unset( $_POST['PostID'] );
        unset( $_POST['my_month'] );
        unset( $_POST['my_year'] );
        unset( $_POST['my_day'] );
        unset( $_POST['my_time'] );

        $taxonomies = $_POST;

        foreach( $taxonomies as $taxonomy => $term ) {
            print "wp_set_post_terms( {$post_id}, {$term}, {$taxonomy} )";
            $e = wp_set_post_terms( $post_id, $term, $taxonomy );
            print_r( $e );
        }

        die();
    } // entryUtilityUpdate

    /**
     * When the post is saved, call our custom action
     */
    public function myplugin_save_postdata( $events_id ) {

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! isset( $_POST['venues_id'] ) )
            return;

        if ( isset( $_POST['post_type'] ) && $_POST['post_type'] != $this->my_cpt )
            return;

        $current_venues_id = get_post_meta( $events_id, 'venues_id', true );

        $this->updateVenue( $events_id, $_POST['venues_id'] );
        $venues = New Venues;
        $venues->updateSchedule( $_POST['venues_id'], $events_id, $current_venues_id );
    }

    public function locationMetaField(){
        add_meta_box(
            'tracks_address',
            __( 'Venue', 'myplugin_textdomain' ),
            function(){
                global $post;
                print Venues::locationDropDown( Events::getVenueId( $post->ID ) ) . '&nbsp;&nbsp;<a href="' . admin_url() . 'post.php?post='.Events::getVenueId( $post->ID ).'&action=edit">Edit this Venue</a>';
            },
            $this->my_cpt
        );
    }

    // since we are in our Events object it is assumed
    // that we are getting the track id by event id!
    public static function getVenueId( $post_id=null ){
        return get_post_meta( $post_id, 'venues_id', true );
    }

    /**
     * @todo add track_id as postmeta for events
     * @todo remove as much markup as possible?
     */
    public function getTrackLink( $post_id=null, $title=null, $anchor=null ){

        $track_id = self::$instance->getVenueId( $post_id );

        $post = get_post( $track_id );

        if ( is_null( $title ) )
            $title = $post->post_title;
        else
            $title = $title;

        if ( is_null( $anchor ) )
            $anchor = '';
        else
            $anchor = '#'.$anchor;

        $html = '<a href="'.get_permalink( $track_id ).$anchor.'" title="View track info for: '.$post->post_title.' ">'.$title.'</a>';

        return $html;
    }

    /**
     * Returns ONLY the URI for a Venue
     */
    public function getVenueURI( $post_id=null ){
        $track_id = self::$instance->getVenueId( $post_id );

        $post = get_post( $track_id );

        return '/venues/'.basename( $post->guid );
    }

    /**
     * Retrive the number of Events
     *
     * @param $echo Either return the results or print them
     * @todo transient
     * @return Count of events (or prints)
     */
    public function eventCount( $echo=true ){
        $count_posts = wp_count_posts( self::$instance->my_cpt );
        if ( $echo ){
            print $count_posts->publish . __( ' events', 'zm_events_venue' );
        } else {
            return $count_posts->publish;
        }
    }

    public function getTrackTitle( $event_id=null ){

        $track_id = get_post_meta( $event_id, 'venues_id', true );

        $post = get_post( $track_id );
        if ( $post )
            print $post->post_title;
    }

    public function getTags( $event_id=null ){

        if ( is_null( $event_id ) ) {
            global $post;
            $event_id = $post->ID;
        }

        return Helpers::getTaxTerm( array( 'post_id' => $event_id, 'taxonomy' => 'bmx_rs_tag' ) );
    }

    public function getType( $event_id=null ){
        $type = wp_get_post_terms( $event_id, 'type', array("fields" => "names") );
        if ( ! empty( $type ) && isset( $type[0] ) )
            $type = $type[0];
        else
            $type = '&ndash;';

        return $type;
    }

    // post_id == events_id
    public function updateVenue( $post_id=null, $venues_id=null ){
        return update_post_meta( $post_id, 'venues_id', $venues_id );
    }

    public function updateStartEndDate( $post_id=null, $start_date=null, $end_date=null ){
        $tmp[$this->my_cpt . '_start-date'] = update_post_meta( $post_id, $this->my_cpt . '_start-date', $start_date );
        $tmp[$this->my_cpt . '_end-date'] = update_post_meta( $post_id, $this->my_cpt . '_end-date', $end_date );
        return $tmp;
    }

    /**
     * Update/associate the event with the attachment
     * @todo all post meta keys come from one location
     * @todo Class Attachment
     */
    public function updateAttachmentId( $post_id=null, $attachment_id=null ){
        return update_post_meta( $post_id, '_zm_attachement_id', $attachment_id );
    }


    /**
     * Add or Update the Events Entry fee. note our post type "events" is
     * ALWAYS derived!.
     */
    public function updateEntryFee( $post_id=null, $entry_fee=null ){
        return update_post_meta( $post_id, $this->my_cpt . '_fee', $entry_fee );
    }

    /**
     * Retreive the attachement Id used for an event
     * @todo Class Attachment
     */
    public function getAttachmentId( $post_id=null ){
        return get_post_meta( $post_id, '_zm_attachement_id', true );
    }

    /**
     * @todo Class Attachment
     * @todo add $meta_field support for arrays(arrays),
     * i.e. getAttachmentMeta( 238, 'main' ), would return meta ['zm_sizes']['main']
     */
    public function getAttachmentMeta( $attachment_id=null, $meta_field=null ){
        return maybe_unserialize( get_post_meta( $attachment_id, '_wp_attachment_metadata', true ) );
    }

    /**
     * Return the full html img tag to an attachment based on
     * attachment_id and size.
     *
     * @todo Class Attachment
     * @todo remove uri stuff in place of the method getAttachmentImageURI
     * @param $size See your db f*cker or hunt for it.
     */
    public function getAttachmentImage( $post_id=null, $size='thumb', $uri=false ){

        // weird hack for something? post_id MUST be an (int)
        if ( is_string( $post_id ) ){
            global $post;
            $size = $post_id;
            $post_id = $post->ID;
        }

        $attachment_id = $this->getAttachmentId( $post_id );
        $meta = $this->getAttachmentMeta( $attachment_id );

        // @todo zm_sizes not hardcoded
        if ( isset( $meta['zm_sizes'][ $size ] ) && $uri ){
            return site_url() . '/wp-content/uploads'.$meta['zm_sizes'][ $size ];
        } else {
            if ( $meta['zm_sizes'][ $size ] )
                return '<img src="/wp-content/uploads'.$meta['zm_sizes'][ $size ].'"/>';
        }
        return false;
    }

    /**
     * @todo Class Attachment
     */
    public function getAttachmentImageURI(){}

    public function getDate( $event_id=null ){

        if ( is_null( $event_id ) ){
            global $post;
            $event_id = $post->ID;
        }
        return get_post_meta( $event_id, 'events_start-date', true );
    }

    static public function events( $preview=true ){

        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'events',
            'post_status' => 'publish',
            'order' => 'ASC',
            'orderby' => 'meta_value',
            'meta_key' => 'events_start-date'
            );

        $query = new WP_Query( $args );

        $events = array();
        $i = 0;
        $event_obj = New Events;

        foreach( $query->posts as $posts ) {
            $this_event = array();

            // just for fucking sanity
            $event_id = $posts->ID;
            $track_id = Events::getVenueId( $event_id );

            $this_event['ID'] = $event_id;
            $this_event['t'] = $posts->post_title;
            $this_event['tr'] = Events::getTrackTitle( $event_id );
            $this_event['ta'] = Events::getTags( $event_id );

            $this_event['c'] = Venues::getAttribute( array( 'key' => 'city', 'venues_id' => $track_id ) );
            $this_event['s'] = Venues::getAttribute( array( 'key' => 'state', 'venues_id' => $track_id ) );
            // $this_event['r'] = Venues::getAttribute( array( 'venue_id' => $track_id ) ); ??

            $this_event['u'] = '/events/'.$posts->post_name . '/';

            $map_image = Venues::getMapImage( $track_id, 'small', true );

            if ( $map_image )
                $tmp_image = $map_image;
            else
                $tmp_image = $event_obj->getAttachmentImage( $posts->ID, 'thumb', true );

            if ( $tmp_image )
                $this_event['s_u'] = $tmp_image;

            $tmp_this_event = array_merge( $this_event, get_event_meta_date( $event_id, 'events_start-date' ) );

            if ( ! empty( $tmp_this_event ) )
                $this_event = $tmp_this_event;

            $events[] = $this_event;
        }

        if ( $preview ) {
            print 'File size <strong>154kb</strong><br />';
            print 'Number of items <strong>666</strong>';
            print_r( $events );
        } else {
            $file = file_put_contents( TMP_RACES_DIR . 'events.json', json_encode( $events ) );
            if ( $file )
                print "File created, size: {$file}\n";
        }
    }

    public function adminMenu(){
        $permission = 'manage_options';
        add_submenu_page( 'edit.php?post_type='.$this->my_cpt, __('Settings', 'bmx_re'), __('Settings', 'bmx_re'),  $permission, $this->my_cpt.'_settings', function(){
        print '<div class="wrap">
            <h2>Feeds</h2>
            <p>
                <a href="#" class="button preview-events-feed-handle">Preview New Feed</a>
                <a href="#" class="button create-events-feed-handle">Create New Feed</a>
            </p>
            <div class="events-feed-target"></div>
            <h3>Current Feed Link</h3>
            <a href="#">http://bmxraceschedules.dev/races/events.json</a>
            <h3>Current Feed as Array</h3>
            <pre>
                ' . print_r( json_decode( file_get_contents( '/opt/local/apache2/htdocs/bmxraceschedules/html/races/events.json' ) ) ) . '</pre>
                Preview Current Feed<br />
                Preview New Feed<br />
                Create Feed<br />
        </div>';
        });
    }

    public function feedPreviewNew(){
        Events::events();
        die('here');
    }


    /**
     * @todo Narrow down results to show from today on
     * @todo $end_date support
     */
    public function getMonth( $start_date=null, $end_date=null ){

        if ( is_null( $start_date ) ){
            $date = date('Y-n');
        } else {
            $date = date( 'Y-n', strtotime( $start_date ) );
        }

        global $wpdb;

        $query = "SELECT distinct( post_id )
        FROM {$wpdb->prefix}postmeta
        WHERE meta_key LIKE 'events_start-date'
        AND meta_value LIKE '{$date}-%'";

        // Should return ALL events going on this month regardless of Location.

        $result = $wpdb->get_results( $query );

        if ( empty( $result ) )
            return;

        $event_ids = array();
        foreach ( $result as $wtf ){
            $event_ids[] = $wtf->post_id;
        }

        $args = array(
            'post_type' => 'events',
            'post_status' => 'publish',
            'post__in' => $event_ids,
            'posts_per_page' => -1,
            'order' => 'ASC',
            'orderby' => 'events_start-date',
            'meta_key' => 'events_start-date'
            );

        $events = new WP_Query( $args );

        $tmp['items'] = $events->posts;
        $tmp['count'] = $events->post_count;
        return $tmp;
    }


    public function beforeDeletePost( $postid ){

        global $post_type;
        if ( $post_type != $this->my_cpt ) return;

        $events_id = $postid;
        $venues_id = get_post_meta( $events_id, 'venues_id', true );
        $venues = New Venues;

        $venues->removeEventFromSchedule( $venues_id, $events_id );

    }
} // End 'CustomPostType'