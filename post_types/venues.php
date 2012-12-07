<?php

$venues = new Venues();

$venues->models[] = 'venues';
$venues->asset_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/';

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
            'track_tags',
            'region'
            )
    )
);

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

$venues->meta_sections['social_media'] = array(
    'name' => 'social_media',
    'label' => __('Social Media','myplugin_textdomain'),
    'fields' => array(
        array(
            'label' => 'Face Book',
            'type' => 'text'
        ),
        array(
            'label' => 'Twitter',
            'type' => 'text'
        )
    )
);

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

    if ( ! is_object( $tmp_schedule ) || $tmp_schedule->post_count == 0 )
        return __("This Venue has no Events.", "zm_ev" );

    $html = null;
    foreach ( $tmp_schedule->posts as $schedule ) {
        $html .= '<p>';
        $html .= '<a href="' . admin_url('post.php?post=' . $schedule->ID . '&action=edit') . '">Edit</a> &nbsp;&nbsp;';
        $html .= $schedule->post_title;
        $html .= '</p>';
    }

    return $html;
}