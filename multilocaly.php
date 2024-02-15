<?php

/**
Plugin Name: Multilocaly
Plugin URI: https://newfiesoft.com/wp-plugins/multilocaly/

Description: Based on WordPress multisite creates an independent multilingual website that can be linked to each other and switched on an easy way to use

Version: 1.0.0
Author: NewfieSoft
Author URI: https://www.newfiesoft.com
Donate link: https://newfiesoft.com/donate

Text Domain: multilocaly
Domain Path: /languages/
Network: true

License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


defined('ABSPATH') or exit();

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}


//// Get plugin dirname basename how can just call this short in all current and future functions
if (!function_exists('get_multilocaly_directory_name')) {
	function get_multilocaly_directory_name(): string {
		return dirname(plugin_basename(__FILE__));
	}
}

$plugin_dirname = get_multilocaly_directory_name();
//echo $plugin_dirname . "\n"; //==> result is multilocaly


//// Get plugin basename how can just call this short in all current and future functions
if (!function_exists('get_multilocaly_directory')) {
	function get_multilocaly_directory(): string {
		return plugin_basename(__FILE__);
	}
}

$plugin_basename = get_multilocaly_directory();
//echo $plugin_basename . "\n"; //==> result is multilocaly/multilocaly.php


//// Get plugin dir name how can just call this short in all current and future functions
if (!function_exists('get_multilocaly_plugin_dir_path')) {
	function get_multilocaly_plugin_dir_path(): string {
		return plugin_dir_path( __FILE__ );
	}
}

$plugin_dir_path = get_multilocaly_plugin_dir_path();
//echo $plugin_dir_path . "\n"; //==> /home/username/public_html/wp-content/plugins/multilocaly/


//// Get plugin dir url name how can just call this short in all current and future functions
if (!function_exists('get_multilocaly_directory_url')) {
	function get_multilocaly_directory_url(): string {
		return plugin_dir_url(__FILE__);
	}
}

$plugin_dir_url = get_multilocaly_directory_url();
//echo $plugin_dir_url . "\n"; //==> result is https://newfiesoft.com/wp-content/plugins/multilocaly/


//// Get plugin data how can just call this short in all current and future functions
if (!function_exists('get_multilocaly_plugin_data')) {
	function get_multilocaly_plugin_data(): array {

		$plugin_main_file = get_multilocaly_plugin_dir_path() . 'multilocaly.php';

		return get_plugin_data($plugin_main_file);
	}
}

$plugin_plugin_data = get_multilocaly_plugin_data();
//var_dump($plugin_plugin_data); //==> sho plugin informations like Name, PluginURI, Version and many more


/////////////////////////////////////////////////////////////////////////////////////////////////////////


//// Function to run on activation and all that is triggered when clicking on Activate inside wp-admin/plugins.php

function mlomw_on_activation(): void {

    // Define the languages directory path
    $languages_dir = WP_CONTENT_DIR . '/languages';

    // Check if the directory doesn't exist
    if (!is_dir($languages_dir)) {
        // If the directory doesn't exist, attempt to create it
        wp_mkdir_p($languages_dir);
    }

    // Call the function to create tables when the plugin is activated
    mlomw_check_plugin_database_tables();
}

register_activation_hook(__FILE__, 'mlomw_on_activation');


//// Check whether it is WP_ALLOW_MULTISITE enabled or not. If it is not enabled, display this notice as long as until has been enabled
function mlomw_require_multisite_notice(): void {
    echo '<div class="notice notice-error is-dismissible">';
    echo '<p>' . __('Multilocaly requires WordPress Multisite to be enabled. <a href="https://wordpress.org/support/article/create-a-network/" target="_blank">Please read this article</a> for guidance on enabling Multisite.', 'multilocaly') . '</p>';
    echo '</div>';
}

function mlomw_setup_multisite_notice(): void {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'plugins' && !is_multisite()) {

        add_action('admin_notices', 'mlomw_require_multisite_notice');
    }
}

add_action('current_screen', 'mlomw_setup_multisite_notice');


//// Load plugin Text Domain for multi-language support
function mlomw_plugin_load_text_domain(): void {

	// Get plugin dirname basename
	$plugin_dirname = get_multilocaly_directory_name();

	// Load the plugin text domain
	load_plugin_textdomain('multilocaly', false, $plugin_dirname . '/languages');
}

add_action('plugins_loaded', 'mlomw_plugin_load_text_domain');


//// Configures menu names and sub-menu names.
function mlomw_active_admin_menu(): void {

    // Check If Multisite is not enabled, add an action to display an admin notice
    if (!is_multisite()) {
        add_action('admin_notices', 'mlomw_require_multisite_notice');
        return; // Exit the function early if multisite is not enabled
    }

    // Always add the main menu page and General submenu, which are visible on all sites
    add_menu_page(
        'Multilocaly',
        __('Multilocaly', 'multilocaly'),
        'activate_plugins',
        'multilocaly',
        'mlomw_render_general_page',
        'multilocaly-icon', // Make sure this icon slug matches your custom icon's registration.
        999
    );

    add_submenu_page(
        'multilocaly',
        'General',
        __('General', 'multilocaly'),
        'activate_plugins',
        'multilocaly',
        'mlomw_render_general_page'
    );

    // Only add Languages and Manage submenus if on the main site
    if (is_main_site()) {
        add_submenu_page(
            'multilocaly',
            'Languages',
            __('Languages', 'multilocaly'),
            'activate_plugins',
            'mlomw_languages_page',
            'mlomw_render_languages_page'
        );
    }

    // Add Settings and Help submenus, which are visible on all sites
    add_submenu_page(
        'multilocaly',
        'Settings',
        __('Settings', 'multilocaly'),
        'activate_plugins',
        'mlomw_settings_page',
        'mlomw_render_settings_page'
    );

    add_submenu_page(
        'multilocaly',
        'Help',
        __('Help', 'multilocaly'),
        'activate_plugins',
        'mlomw_help_page',
        'mlomw_render_help_page'
    );

    // Check if the user is trying to access a restricted page on a subsite
    if (isset($_GET['page']) && !is_main_site()) {
        $allowed_pages = array('multilocaly', 'mlomw_settings_page', 'mlomw_help_page');
        if (!in_array($_GET['page'], $allowed_pages, true)) {
            wp_redirect(admin_url('admin.php?page=multilocaly')); // Redirect to the main admin page
            exit();
        }
    }
}

add_action('admin_menu', 'mlomw_active_admin_menu');


//// All functions on this part only can be loaded and triggered outside the admin panels
if ( ! is_admin() ) {

// Require functions for front-side displays outside the admin panels. Example domain.com/
	$allowed_front_function_files = glob($plugin_dir_path . "includes/functions/front/front_*.php");
	foreach ($allowed_front_function_files as $file) {

		// Perform additional checks if needed
		if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
			require_once $file;
		}
	}
}

//// Here all functions on this part only can be loaded and triggered inside the admin panels
else {

// Here load plugin pages
	$allowed_page_files = glob($plugin_dir_path . "pages/*.php");
	foreach ($allowed_page_files as $file) {

		// Perform additional checks if needed
		if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
			require_once $file;
		}
	}

// Require functions for the back side that displays inside the admin panels. Example /wp-admin/
	$allowed_back_function_files = glob($plugin_dir_path . "includes/functions/back/back_*.php");
	foreach ($allowed_back_function_files as $file) {

		// Perform additional checks if needed
		if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
			require_once $file;
		}
	}

//// This helps to create additional buttons after the Plugin is activated Installed in the page Plugins list
function mlomw_custom_link_options_plugin($actions): array {

    // Always add the "General" link if Multisite is enabled
    if (is_multisite()) {

        $support_url = esc_url(add_query_arg('page', 'multilocaly', get_admin_url() . 'admin.php'));

        // Name
        $support_name = __('General', 'multilocaly');

        $settings_url = '<a href="' . $support_url . '">' . $support_name . '</a>';

        $actions = array_merge(compact('settings_url'), $actions);
    }

    // Add "Network Setup" link only if WP_ALLOW_MULTISITE is true and the site is not a Multisite
    if (defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE && !is_multisite()) {

        $network_setup_url = esc_url(admin_url('network.php'));

        // Name
        $network_setup_name = __('Network Setup', 'multilocaly');

        $network_setup_link = '<a href="' . $network_setup_url . '">' . $network_setup_name . '</a>';

        // Insert the network setup link at the beginning of the array
        $actions = array_merge(compact('network_setup_link'), $actions);
    }

    return $actions;
}

add_filter('plugin_action_links_' . $plugin_basename, 'mlomw_custom_link_options_plugin', 10, 2);


//// This helps to create additional custom meta links in the sequel after Version By .... Installed Plugins list
function mlomw_custom_link_action_plugin($links_array, $mlomw_plugin_name) {

    // Get plugin basename
    $plugin_basename = get_multilocaly_directory();

    if ($mlomw_plugin_name === $plugin_basename) {

        // Build URL Links
        $support_url = 'https://wordpress.org/support/plugin/multilocaly/';
        $faq_url = 'https://wordpress.org/plugins/multilocaly/#faq';
        $rating_url = 'https://wordpress.org/support/plugin/multilocaly/reviews/#new-post';

        // Links name
        $support_name =__('Community Support', 'multilocaly');
        $faq_name =__('FAQ', 'multilocaly');
        $rating_name =__('Ratings', 'multilocaly');

        // Create buttons
        $links_array[] = '<a href="' . $support_url . '" class="help-style" target="_blank">' . $support_name . '</a>';
        $links_array[] = '<a href="' . $faq_url . '" class="help-style" target="_blank">' . $faq_name . '</a>';
        $links_array[] = '<a href="' . $rating_url . '" class="help-style" target="_blank">' . $rating_name . '</a>';

    }
    return $links_array;

}

add_filter('plugin_row_meta', 'mlomw_custom_link_action_plugin', 10, 4);


//// Create a custom footer only inside plugin pages
function mlomw_customize_admin_footer_script(): void {
    // Get plugin dir url name
    $plugin_dir_url = get_multilocaly_directory_url();

    // Get plugin data
    $plugin_plugin_data = get_multilocaly_plugin_data();

    // Get My plugin version
    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

    $plugin_data = $plugin_plugin_data;

    // Check if we are on one of your plugin's pages
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

    // Add page URL slug from any page what have
    $allowed_pages = array(
        'multilocaly',  // General
        'mlomw_languages_page',     // Languages
        'mlomw_manage_page',       // Manage
        'mlomw_settings_page',     // Settings
        'mlomw_help_page',         // Help
    );

    if (in_array($current_page, $allowed_pages)) {
        echo '<script type="text/javascript">';
        echo 'document.addEventListener("DOMContentLoaded", function() {';

        // Modify content inside "footer-thankyou" // Content Left //
        echo '   var footerThankYou = document.getElementById("footer-thankyou");';
        echo '   if (footerThankYou) {';
        echo '       footerThankYou.innerHTML = "<div class=\'power-by-info\'>' .
             __('Premium Tools for WordPress made by', 'multilocaly') .
             ' <a href=\'https://www.newfiesoft.com\' target=\'_blank\'>NewfieSoft</a> ' .
             __('with', 'multilocaly') .
             ' <i class=\"fa-solid fa-heart\"></i> ' . // FontAwesome heart icon
             __('in Zürich, Switzerland', 'multilocaly') . '</div>";';
        echo '   }';

        // Remove content inside "footer-upgrade" // Content Right //
        echo '   var footerUpgrade = document.getElementById("footer-upgrade");';
        echo '   if (footerUpgrade) {';
        echo '       footerUpgrade.innerHTML = "<div class=\'version\'>' . esc_html__('Version: ', 'multilocaly') . $plugin_data['Version'] . '</div>";';
        echo '   }';

        echo '});';
        echo '</script>';
    }
}

add_action('admin_footer', 'mlomw_customize_admin_footer_script');

}
