<?php

/**
 * Plugin Name: Events Engine
 * Plugin URI: http://zanematthew.com/blog/events-venues-plugin/
 * Description: Used to create Events and relate theme with Venues.
 * Version: 1.0.0
 * Author: Zane M. Kolnik
 * Author URI: http://zanematthew.com/
 * License: GPL
 */


require_once 'template-tags.php';

/**
 * Auto load our events.php, events_controller.php, etc.
 * and enqueue our admin and front end asset files.
 */
require_once plugin_dir_path( __FILE__ ) . 'library/zm-easy-cpt/plugin.php';
zm_easy_cpt_reqiure( plugin_dir_path(__FILE__) );