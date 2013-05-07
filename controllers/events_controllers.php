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

        $this->my_cpt = strtolower( __CLASS__ );


        register_activation_hook( __FILE__, array( &$this, 'registerActivation') );

        add_action( 'wp_ajax_nopriv_postTypeSubmit', array( &$this, 'postTypeSubmit' ) );
        add_action( 'wp_ajax_postTypeSubmit', array( &$this, 'postTypeSubmit' ) );

        add_action( 'before_delete_post', array( &$this, 'beforeDeletePost') );

        add_action( 'admin_init', array( &$this, 'admin_init' ) );
    }


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

        if ( (int)$_POST['venues_id'] ){
            $this->updateVenue( $events_id, $_POST['venues_id'] );
            $venues = New Venues;
            $venues->updateSchedule( $_POST['venues_id'], $events_id, $current_venues_id );
        }
    }

    public function locationMetaField(){
        add_meta_box(
            'tracks_address',
            __( 'Venue', 'myplugin_textdomain' ),
            function(){
                global $post;
                $venues = New Venues;
                $venues_id = Events::getVenueId( $post->ID );
                print $venues->locationDropDown( $venues_id );
                if ( ! empty( $venues_id ) ) print '<a href="' . admin_url() . 'post.php?post='.$venues_id.'&action=edit">Edit this Venue</a>';
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

    public function getVenueTitle( $event_id=null ){
        $post = get_post( get_post_meta( $event_id, 'venues_id', true ) );
        return $post->post_title;
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
            $type = false;

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

    public function getDate( $event_id=null ){

        if ( is_null( $event_id ) ){
            global $post;
            $event_id = $post->ID;
        }
        return get_post_meta( $event_id, 'events_start-date', true );
    }

    /**
     * @todo Narrow down results to show from today on
     * @todo $end_date support
     */
    public function getMonth( $start_date=null, $end_date=null, $type=null, $limit=5 ){

        if ( is_null( $start_date ) ){
            $date = date('Y-m');
        } else {
            // $date = date( 'Y-n', strtotime( $start_date ) );
            $date = $start_date;
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
            'posts_per_page' => 5,
            'order' => 'ASC',
            'orderby' => 'events_start-date',
            'meta_key' => 'events_start-date'
            );

        if ( ! empty( $type ) ){
            $tax_query = array(
                array(
                    'taxonomy' => 'type',
                    'field' => 'slug',
                    'terms' => $type
                    )
                );

            $args['tax_query'] = $tax_query;
        }

        $events = new WP_Query( $args );

        $tmp['date']  = $date;
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

    public function typeSelectBox( $current=null ){

        $items = array();
        foreach( get_terms('type') as $term ){
            $tmp_items['id'] = $term->term_id;
            $tmp_items['name'] = $term->name;
            $items[] = $tmp_items;
        }
        $args = array(
            'extra_data' => 'data-allows-new-values="true" style="width: 700px;" data-placeholder="Choose a Type..."',
            'extra_class' => 'chzn-select',
            'taxonomy' => 'type',
            'label' => 'Type',
            'multiple' => true,
            'current' => $current, // list of IDs
            'items' => $items,
            'key' => 'type'
        );

        zm_base_build_select( $args );
    }

    public function customHeader($columns) {
        return $columns
             + array('events_start-date' => __('Start Date'),
                     'events_end-date' => __('End Date'));
    }


    public function customContent( $column, $post_id ) {
        switch ( $column ) {
          case 'events_start-date':
            echo get_post_meta( $post_id , 'events_start-date' , true );
            break;

          case 'events_end-date':
            echo get_post_meta( $post_id , 'events_end-date' , true );
            break;
        }
    }


    public function admin_init(){

        add_action( 'add_meta_boxes', array( &$this, 'locationMetaField' ) );
        add_action( 'save_post', array( &$this, 'myplugin_save_postdata' ) );
        add_action( 'wp_ajax_feedPreviewNew', array( &$this, 'feedPreviewNew' ) );

        add_filter( 'manage_edit-events_columns', array( &$this, 'customHeader' ) );
        add_action( 'manage_events_posts_custom_column' , array( &$this, 'customContent' ), 10, 2 );

        wp_register_script( 'zm-ev-date-time-script', dirname( plugin_dir_url( __FILE__ ) ) . '/vendor/jquery-timepicker/jquery-ui-timepicker-addon.js'  );

        $this->dependencies_js = array(
            'jquery-ui-datepicker',
            'jquery-ui-slider',
            'zm-ev-date-time-script'
            );

        $this->dependencies_css = array();

    }
} // End 'CustomPostType'