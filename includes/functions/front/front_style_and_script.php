<?php

//// Load front side public plugin CSS / Script files and library files that can be loaded when the plugin is active
function mlomw_front_load_plugin_style_and_script(): void {

    // Get plugin dir url name
    $plugin_dir_url = get_multilocaly_directory_url();

    // Enqueue styles
    wp_enqueue_style( 'mlomw-front', $plugin_dir_url . 'assets/css/front-style.css' );
    wp_enqueue_style('fontawesome', $plugin_dir_url . 'assets/css/library/fontawesome-all.css');


    // Load plugin script on the public front side only with jQuery as a dependency
    wp_enqueue_script( 'mlomw-front-js', $plugin_dir_url . 'assets/js/front-side.js', array('jquery'), '1.0.0', true);

}

add_action( 'wp_enqueue_scripts', 'mlomw_front_load_plugin_style_and_script' );