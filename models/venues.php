<?php

/**
 * This file is used to create our post type, custom taxonomies, assign our
 * assets url and assign "basic" meta fields.
 *
 * This file MUST be named the same name as the CONTROLLER!
 * It is used to create our Events post type.
 */


/**
 * Build custom post type
 */
$venues = new Venues();
$venues->post_type = array(
    array(
        'name' => 'Venue',
        'type' => 'venues',
        'menu_name' => 'Venues',
        'rewrite' => array(
            'slug' => 'venues'
            ),
        'supports' => array(
            'title',
            'editor',
            'comments',
            'thumbnail'
        ),
        'taxonomies' => array(
            'venues_tags',
            'region'
            )
    )
);


/**
 * Assign assets url
 */
$venues->asset_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/';


/**
 * Build custom taxonomies
 */
$venues->taxonomy = array(
    array(
         'name' => 'region',
         'post_type' => 'venues',
         'menu_name' => 'Region'
         ),
    array(
         'name' => 'venues_tags',
         'post_type' => 'venues',
         'menu_name' => 'Tags'
         )
    );


/**
 * Contact meta section
 */
$venues->meta_sections['contact'] = array(
    'name' => 'contact',
    'label' => __('Contact','myplugin_textdomain'),
    'fields' => array(
        array(
            'label' => 'Email',
            'type' => 'text'
        ),
        array(
            'label' => 'Primary Phone',
            'type' => 'text',
        ),
        array(
            'label' => 'Phone',
            'type' => 'text'
        )
    )
);


/**
 * Address meta section
 */
$venues->meta_sections['address'] = array(
    'name' => 'address',
    'label' => __('Address'),
    'fields' => array(
        array(
            'label' => 'Lat',
            'type' => 'text'
            ),
        array(
            'label' => 'Long',
            'type' => 'text'
            ),
        array(
            'label' => __('City'),
            'type' => 'text'
            ),
        array(
            'label' => __('State'),
            'type' => 'text'
            ),
        array(
            'label' => __('Zip'),
            'type' => 'text'
            ),
        array(
            'label' => __('Full Address/Street'),
            'type' => 'text',
            'name' => 'venues_street',
            ),
        array(
            'label' => __('Website'),
            'type' => 'text'
            )
    )
);



/**
 * Social Media meta section
 */
$venues->meta_sections['social_media'] = array(
    'name' => 'social_media',
    'label' => __('Social Media','myplugin_textdomain'),
    'fields' => array(
        array(
            'label' => 'Facebook',
            'type' => 'text'
        ),
        array(
            'label' => 'Twitter',
            'type' => 'text'
        )
    )
);


/**
 * Schedule meta section
 */
$venues->meta_sections['schedule'] = array(
    'name' => 'schedule',
    'label' => __('Schedule','myplugin_textdomain'),
    'fields' => array(
        array(
            'type' => 'html',
            'value' => tmp_make_schedule()
        )
    )
);


function tmp_make_schedule(){

    if ( empty( $_GET['post'] ) ) return;

    $tmp_schedule = Venues::getSchedule( $_GET['post'] );
    $html = null;

    if ( ! is_object( $tmp_schedule ) || $tmp_schedule->post_count == 0 ){
        $html = __("This Venue has no Events.", "zm_ev" );
    } else {
        foreach ( $tmp_schedule->posts as $schedule ) {
            $html .= '<p><a href="' . admin_url('post.php?post=' . $schedule->ID . '&action=edit') . '">Edit</a> &nbsp;&nbsp;' . $schedule->post_title . '</p>';
        }
    }

    return $html;
}