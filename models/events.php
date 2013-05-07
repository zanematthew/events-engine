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
$event = new Events();
$event->post_type = array(
    array(
        'name' => 'Event',
        'type' => 'events',
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


/**
 * Assign assets url
 */
$event->asset_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/';


/**
 * Build custom taxonomies
 */
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


/**
 * Date meta section
 */
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


/**
 * Fee meta section
 */
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