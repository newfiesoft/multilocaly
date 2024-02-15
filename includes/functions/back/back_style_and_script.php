<?php

//// Load back side wp-admin plugin CSS / Script files and library files that can be loaded when the plugin is active
function mlomw_back_load_plugin_style_and_script(): void {

    // Get plugin dir url name
    $plugin_dir_url = get_multilocaly_directory_url();

    // Enqueue styles
    wp_enqueue_style('mlomw-back', $plugin_dir_url . 'style.css');
    wp_enqueue_style('fontawesome', $plugin_dir_url . 'assets/css/library/fontawesome-all.css');
    wp_enqueue_style('datatables', $plugin_dir_url . 'assets/css/library/datatables.min.css');

    // Enqueue scripts
    wp_enqueue_script('sweetalert2', $plugin_dir_url . 'assets/js/library/sweetalert2.js');
    wp_enqueue_script('dataTables', $plugin_dir_url . 'assets/js/library/datatables.min.js');

    // Load custom script on admin back side only
    //wp_enqueue_script('mlomw-back-js', $plugin_dir_url . 'assets/js/back-side.js', array('jquery'), '1.0.0', true);

}

add_action('admin_enqueue_scripts', 'mlomw_back_load_plugin_style_and_script');