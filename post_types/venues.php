<?php

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
            'name' => 'primary_contact'
        ),
        array(
            'label' => 'Phone',
            'type' => 'text'
        )
    )
);

// @todo remove 'name', derive, see method 'metaSectionRender'
$venues->meta_sections['address'] = array(
    'name' => 'address',
    'label' => __('Address'),
    'fields' => array(
        array(
            'label' => 'Lat',
            'type' => 'text',
            'name' => 'lat'
            ),
        array(
            'label' => 'Long',
            'type' => 'text',
            'name' => 'long'
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