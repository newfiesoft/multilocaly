<?php

//////// This part of the code is used on page /wp-admin/network/site-new.php ////////


//// Check if WP_ALLOW_MULTISITE is enabled or not, if it is shown inside the wpadminbar, the current site name with options to switch to another site with the same current address location
function mlomw_back_check_and_display_multisite_status($wp_admin_bar): void {

    global $wpdb;

    // Get plugin dir url name
    $plugin_dir_url = get_multilocaly_directory_url();

    if (is_multisite() && !is_network_admin()) {
        $current_blog_id = get_current_blog_id();
        $current_admin_page = remove_query_arg('switched_off', wp_unslash($_SERVER['REQUEST_URI']));
        $current_admin_page = preg_replace('#^(/[a-z]{2})?/wp-admin/#', '', $current_admin_page);
        $sites = get_sites();
        $languages_table = $wpdb->base_prefix . 'mlomw_languages'; // Define the table name

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            $site_id = $site->blog_id;
            $site_name = esc_html(get_bloginfo('name'));

            // Query to get the lang_flag based on lang_site_id
            $flag_img_url = $plugin_dir_url . "assets/img/flags/";
            $lang_flag = $wpdb->get_var($wpdb->prepare("SELECT lang_flag FROM $languages_table WHERE lang_site_id = %d", $site_id));

            if (!empty($lang_flag)) {
                $site_name .= ' <img src="' . esc_url($flag_img_url) . $lang_flag . '.png" alt="' . esc_attr($lang_flag) . '" class="current-site-name-img">'; // Append lang_flag to site name if available
            }

            if ((int) $site_id === $current_blog_id) {
                // Display the current site's name, ID, and flag if available
                $wp_admin_bar->add_node([
                    'id'    => 'current-site-info',
                    'title' => __('This is the site', 'multilocaly') . ' ' . '<span class="current-site-name">' . $site_name . '</span>',
                ]);
            } else {
                // Add other sites as submenus
                $wp_admin_bar->add_node([
                    'parent' => 'current-site-info',
                    'id'     => 'site-' . $site_id,
                    'title'  => $site_name,
                    'href'   => esc_url(get_admin_url(null, $current_admin_page)),
                ]);
            }

            restore_current_blog();
        }
    }
}

add_action('admin_bar_menu', 'mlomw_back_check_and_display_multisite_status', 100);


//// Fetch language codes from the mlomw_languages table
function mlomw_back_fetch_language_codes($lang_site_id = null): array {

    global $wpdb;

    $table_name = $wpdb->base_prefix . 'mlomw_languages';

    if ($lang_site_id === null) {
        // Query for new site (lang_site_id being NULL or zero)
        $query = "SELECT lang_code FROM `$table_name` WHERE lang_site_id IS NULL OR lang_site_id = 0";

    }
    else {
        // Query for existing site (specific lang_site_id)
        $lang_site_id = (int) $lang_site_id; // Ensure $lang_site_id is an integer
        $query = $wpdb->prepare("SELECT lang_code FROM `$table_name` WHERE lang_site_id = %d OR lang_site_id IS NULL OR lang_site_id = 0", $lang_site_id);
    }

    return $wpdb->get_col($query);
}

//// Enqueue JavaScript in a set specific page in wp-admin
function mlomw_back_enqueue_custom_admin_js($hook): void {

    // Get plugin dir url name
    $plugin_dir_url = get_multilocaly_directory_url();

    // Load script only on site-new.php and site-info.php pages into wp-admin/network/
    if ($hook === 'site-new.php' || $hook === 'site-info.php') {
        wp_enqueue_script('back-eca-script', $plugin_dir_url . 'assets/js/back-eca-script.js', array('jquery'), '1.0', true);
        // Pass the language codes to JavaScript
        wp_localize_script('back-eca-script', 'languageCodes', array(
            'codes' => mlomw_back_fetch_language_codes()
        ));
    }

    // Load script only on new post and post edit
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_script('back-pp-script', $plugin_dir_url . 'assets/js/back-pp-script.js', array('jquery'), '1.0', true);

        // Ensure the handle 'back-pp-script' matches the one used in wp_enqueue_script
        wp_localize_script('back-pp-script', 'myPluginAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            // Pass the nonce here if needed
            'mlomwAutocompleteNonce' => wp_create_nonce('mlomw_autocomplete_nonce')
            // Add any other data you wish to pass to the script
        ));
    }
}

add_action('admin_enqueue_scripts', 'mlomw_back_enqueue_custom_admin_js');


//// Inject the dropdown into the /wp-admin/network/site-new.php page with a description
function mlomw_back_inject_language_dropdown_for_site_new(): void {
    global $pagenow;

    if ($pagenow === 'site-new.php' && is_network_admin()) {
        $language_codes = mlomw_back_fetch_language_codes();

        // Start of the wrapper
        echo '<div class="site-new-select-dropdown-language">';

        // Description
        echo '<p class="description">' . esc_html__('Select a language code for the new site:', 'multilocaly') . '</p>';

        // Dropdown with the first blank option
        echo '<select id="select-add-new-language-dropdown" name="select_language_code">';
        echo '<option>' . esc_html__('Select Language', 'multilocaly') . '</option>';
        foreach ($language_codes as $code) {
            echo '<option value="' . esc_attr($code) . '">' . esc_html($code) . '</option>';
        }
        echo '</select>';

        // End of the wrapper
        echo '</div>';
    }
}

add_action('in_admin_header', 'mlomw_back_inject_language_dropdown_for_site_new');


////  This updates the lang_code value and links with the new site when adding a new site from /wp-admin/network/
function mlomw_back_update_language_table_with_new_site($new_site, $meta): void {
    if (isset($_POST['select_language_code'])) {
        $selected_lang_code = sanitize_text_field($_POST['select_language_code']);

        global $wpdb;
        $table_name = $wpdb->base_prefix . 'mlomw_languages';

        // Update the table with the new site ID
        $wpdb->update(
            $table_name,
            ['lang_site_id' => $new_site->id], // data
            ['lang_code' => $selected_lang_code] // where
        );
    }
}

add_action('wp_initialize_site', 'mlomw_back_update_language_table_with_new_site', 10, 2);


//// Inject the dropdown into the /wp-admin/network/site-info.php page with a description
function mlomw_back_inject_language_dropdown_for_site_edit(): void {
    global $pagenow;

    if ($pagenow === 'site-info.php' && is_network_admin()) {
        $blog_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $language_codes = mlomw_back_fetch_language_codes($blog_id);

        // Start of the wrapper
        echo '<div class="site-edit-select-dropdown-language">';

        // Description
        echo '<p class="description">' . esc_html__('Link a language code with the site:', 'multilocaly') . '</p>';

        // Dropdown with the first blank option
        echo '<select id="link-select-language-dropdown" name="link_select_language_code">';
        echo '<option>' . esc_html__('Select Language', 'multilocaly') . '</option>';
        foreach ($language_codes as $code) {
            echo '<option value="' . esc_attr($code) . '">' . esc_html($code) . '</option>';
        }
        echo '</select>';
        echo "<input type='hidden' name='blog_id' value='$blog_id'>";

        // End of the wrapper
        echo '</div>';
    }
}

add_action('in_admin_header', 'mlomw_back_inject_language_dropdown_for_site_edit');


//// This trigger update when catch value from lang_site_id value and lang_code value
function mlomw_back_update_site_language_on_submission(): void {
    if (isset($_POST['submit'], $_POST['link_select_language_code'], $_POST['blog_id'])) {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'mlomw_languages';
        $blog_id = (int) $_POST['blog_id'];
        $code = sanitize_text_field($_POST['link_select_language_code']);

        $wpdb->query($wpdb->prepare(
            "UPDATE `$table_name` SET lang_site_id = %d WHERE lang_code = %s",
            $blog_id,
            $code
        ));
    }
}

add_action('admin_init', 'mlomw_back_update_site_language_on_submission');


//// This is html elements constructor
function mlomw_back_add_language_link_meta_box(): void {

    global $pagenow;

    // Check if the current site is the main site in the network
    if (is_main_site()) {
        return; // Exit the function if it's the main site
    }

    // Only add the meta box on post or page add/edit screens
    if (in_array($pagenow, array('post-new.php', 'post.php'))) {

        add_meta_box(
        // ID of the meta box
            'mlomw_language_link',

            // Title of the meta box
            __('Link to Language', 'multilocaly'),

            // Callback function that will display the box content
            'mlomw_back_language_link_meta_box_html',

            // Post type where the box should appear ('page', 'post', or an array of post types)
            null,

            // Context where to display the box ('side', 'normal', or 'advanced') // Priority within the context where the boxes should show ('high', 'low')
            'side',
        );
    }
}

add_action('add_meta_boxes', 'mlomw_back_add_language_link_meta_box');


//// There are html elements who is then shown inside any page or port type
function mlomw_back_language_link_meta_box_html($post): void {

    global $wpdb;

    // Nonce field for validation
    wp_nonce_field('mlomw_save_language_link', 'mlomw_language_link_nonce');

    // Get the current site ID
    $site_id = get_current_blog_id();

    // Define the table name for languages
    $languages_table_name = $wpdb->base_prefix . 'mlomw_languages';

    // Query to find the language code for the matched site ID
    $language_code = $wpdb->get_var($wpdb->prepare("SELECT lang_code FROM $languages_table_name WHERE lang_site_id = %d", $site_id));

    // Initialize language ID
    $language_id = null;

    // If a corresponding language code was found, find the language ID
    if ($language_code !== null) {
        $language_id = $wpdb->get_var($wpdb->prepare("SELECT lang_id FROM $languages_table_name WHERE lang_code = %s AND lang_site_id = %d", $language_code, $site_id));
    }

    // Determine the correct posts table name based on the current site
    $current_blog_id = get_current_blog_id();
    $posts_table_name = ($current_blog_id === 1) ? $wpdb->posts : $wpdb->prefix . 'posts';

    // Query to find the post type for the current post ID
    $post_type = null;
    if (!empty($post->ID)) {
        $post_type = $wpdb->get_var($wpdb->prepare("SELECT post_type FROM $posts_table_name WHERE ID = %d", $post->ID));
    }

    // Determine the table and column names based on post type
    $linked_table = ($post_type === 'page') ? $wpdb->base_prefix . 'mlomw_pages' : $wpdb->base_prefix . 'mlomw_posts';
    $id_column = ($post_type === 'page') ? 'pages_id' : 'posts_id';
    $main_id_column = ($post_type === 'page') ? 'main_pages_id' : 'main_posts_id';

    // Check if the post/page is already linked
    $linked_main_post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT $main_id_column FROM $linked_table WHERE $id_column = %d AND site_id = %d",
        $post->ID, $site_id
    ));

    // Fetch the title of the linked post/page from the main site, if linked
    $linked_post_title = '';
    if ($linked_main_post_id) {
        switch_to_blog(1); // Switch to the main site
        $linked_post_title = get_the_title($linked_main_post_id);
        restore_current_blog(); // Switch back
    }

    // Output the site ID, language code, language ID, current post/page ID, and post type information
    echo '<p>';
    _e('Current Site ID:', 'multilocaly');
    echo ' <strong>' . esc_html($site_id) . '</strong>';
    if ($language_code !== null) {
        echo ', ' . __('Language Code:', 'multilocaly') . ' <strong>' . esc_html($language_code) . '</strong>';
    }
    if ($language_id !== null) {
        echo ', ' . __('Language ID:', 'multilocaly') . ' <strong>' . esc_html($language_id) . '</strong>';
    }
    echo ', ' . __('Current Post/Page ID:', 'multilocaly') . ' <strong>' . esc_html($post->ID) . '</strong>';
    if ($post_type !== null) {
        echo ', ' . __('Post Type:', 'multilocaly') . ' <strong>' . esc_html($post_type) . '</strong>';
    }
    echo '</p>';

    // Output the form field with the linked post/page title
    echo '<label for="mlomw_linked_post_id">';
    _e('Enter the post/page name from the main site and link it with this language.', 'multilocaly');
    echo '</label> ';
    echo '<input type="text" id="mlomw_linked_post_search" class="mlomw-linked-post-search" size="25" value="' . esc_attr($linked_post_title) . '" />';
    echo '<input type="hidden" id="mlomw_linked_post_id" name="mlomw_linked_post_id" value="' . esc_attr($linked_main_post_id) . '" />';

// Inline JavaScript for autocomplete and submission handling
    echo '
<div id="mlomw_data_attributes" 
     data-langid="' . esc_attr($language_id) . '"
     data-siteid="' . esc_attr($site_id) . '"
     data-currentpostid="' . esc_attr($post->ID) . '"
     data-posttype="' . esc_attr($post_type) . '"
     data-nonce="' . esc_attr(wp_create_nonce('mlomw_back_update_linked_content_nonce')) . '"
     data-ajaxurl="' . esc_url(admin_url('admin-ajax.php')) . '"
     style="display:none;">
</div>
';

}


//// This checking in the background title name then fetch the names and connects them
function mlomw_back_fetch_names_and_connect(): void {

    global $wpdb;

    // Switch to the main site
    switch_to_blog(1);

    $search_term = sanitize_text_field($_POST['term']);
    $current_post_type = sanitize_text_field($_POST['post_type']);

    // Query to include both 'publish' and 'draft' post statuses
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_title FROM $wpdb->posts WHERE post_title LIKE %s AND post_type = %s AND (post_status = 'publish' OR post_status = 'draft') LIMIT 10",
            '%' . $wpdb->esc_like($search_term) . '%',
            $current_post_type
        )
    );

    $formatted_results = [];
    foreach ($results as $post) {
        $formatted_results[] = ['label' => $post->post_title, 'value' => $post->ID];
    }

    restore_current_blog(); // Switch back to the current site
    wp_send_json($formatted_results);
}

add_action('wp_ajax_mlomw_back_fetch_names_and_connect', 'mlomw_back_fetch_names_and_connect');


//// This update and linked content with main site type pages, and posts, inside our database table
function mlomw_back_update_linked_content(): void {

    check_ajax_referer('mlomw_back_update_linked_content_nonce', 'nonce');

    global $wpdb;

    $langId = (int) $_POST['langId'];
    $siteId = (int) $_POST['siteId'];
    $mainPostId = (int) $_POST['mainPostId'];
    $currentPostId = (int) $_POST['currentPostId'];
    $postType = sanitize_text_field($_POST['postType']);

    $tableName = ($postType === 'page') ? $wpdb->base_prefix . 'mlomw_pages' : $wpdb->base_prefix . 'mlomw_posts';

    // Correct the column names based on your table structure
    $mainColumn = ($postType === 'page') ? 'main_pages_id' : 'main_posts_id';
    $idColumn = ($postType === 'page') ? 'pages_id' : 'posts_id';

    // Prepare data and where arrays
    $data = [
        'lang_id' => $langId,
        'site_id' => $siteId,
        $mainColumn => $mainPostId,
        $idColumn => $currentPostId
    ];
    $where = [
        'lang_id' => $langId,
        'site_id' => $siteId,
        $idColumn => $currentPostId
    ];

    // Check if a record with the same lang_id, site_id, and currentPostId already exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tableName WHERE lang_id = %d AND site_id = %d AND $idColumn = %d",
        $langId,
        $siteId,
        $currentPostId
    ));

    // Update or insert data accordingly
    if ($exists) {
        $result = $wpdb->update($tableName, $data, $where);
    } else {
        $result = $wpdb->insert($tableName, $data);
    }

    if ($result !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Database update failed']);
    }
}

add_action('wp_ajax_mlomw_back_update_linked_content', 'mlomw_back_update_linked_content');



///////////////////////////////////////////////////////////////////////////////////////////////
