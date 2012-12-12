<?php
/**
 * Define parameters for our Custom Post Type.
 *
 */

$event = new Events();

$event->asset_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/';

$event->post_type = array(
    array(
        'name' => 'Event',
        'type' => 'events',
        'has_one' => 'tracks', // add support 'has_many' => 'other_cpt'
        'menu_name' => 'Events',
        'rewrite' => array(
            'slug' => 'events'
            ),
        'supports' => array(
            'title',
            'editor',
            'comments',
            'thumbnail'
        ),
        'taxonomies' => array(
            'type',
            'events_tag',
            'attendees'
        )
    )
);

$event->taxonomy = array(
     array(
         'name' => 'type',
         'post_type' => 'events',
         'menu_name' => 'Type'
         ),
    array(
        'name' => 'events_tag',
        'post_type' => 'events',
        'menu_name' => 'Tags',
        'slug' => 'events-tags',
        'hierarchical' => false
        ),
    array(
        'name' => 'Attendees',
        'post_type' => 'events'
        )
);

$event->meta_sections['date'] = array(
    'name' => 'date',
    'label' => __('Event Date'),
    'fields' => array(
        array(
            'label' => 'Start Date',
            'type' => 'text',
            'class' => 'datetime-picker-start',
            'placeholder' => 'yyyy-mm-dd'
            ),
        array(
            'label' => 'End Date',
            'type' => 'text',
            'class' => 'datetime-picker-end',
            'placeholder' => 'yyyy-mm-dd'
            )
    )
);

$event->meta_sections['fee'] = array(
    'name' => 'fee',
    'label' => __('Event Fee'),
    'fields' => array(
        array(
            'label' => 'Fee',
            'type' => 'text'
            )
    )
);