<?php


//// This fetches language information from the database table mlomw_languages
function mlomw_front_fetch_languages_multisite(): array {

    global $wpdb;

    $languages = [];

    // Construct the table name for the main site's language table
    $table_name = $wpdb->base_prefix . 'mlomw_languages';

    // Check if the custom table exists in the main site's database
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {

        // Fetch all languages from the main site's language table
        $results = $wpdb->get_results("SELECT lang_id, lang_name, lang_locale, lang_code, lang_flag, lang_default, lang_site_id FROM `$table_name`");
        foreach ($results as $row) {
            $languages[$row->lang_id] = array(
                'id' => $row->lang_id,
                'name' => $row->lang_name,
                'locale' => $row->lang_locale,
                'code' => $row->lang_code,
                'flag' => $row->lang_flag,
                'default' => $row->lang_default,
                'siteid' => $row->lang_site_id,
            );
        }
    }

    return $languages;
}


//// This helps to rewrite inside url and allows an addition like /de/* XYZ names
function mlomw_front_rewrite_rules(): void {
    add_rewrite_rule('^([a-z]{2})/(.+)/?$', 'index.php?lang_code=$matches[1]&pagename=$matches[2]', 'top');
}

add_action('init', 'mlomw_front_rewrite_rules');


//// Check the language code from the URL if exists redirect to the site or if does not exist go to the default site
function mlomw_front_set_locale(): void {

    // Fetch languages value from the mlomw_languages table using the existing function
    $languages = mlomw_front_fetch_languages_multisite();

    // Get the language code from the URL
    $url_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $url_parts = explode('/', $url_path);

    if (!empty($url_parts[0]) && strlen($url_parts[0]) === 2) {
        $lang_code = strtolower($url_parts[0]);
        $lang_locale = '';
        $redirect_url = '';

        // Check if the language code exists in the fetched languages and get the associated site ID
        foreach ($languages as $language) {
            if ($language['code'] === $lang_code) {
                $lang_locale = $language['locale'];
                if (!empty($language['siteid']) && (int)$language['siteid'] !== get_current_blog_id()) {
                    $site_details = get_blog_details(['blog_id' => $language['siteid']]);
                    if ($site_details) {
                        $redirect_url = $site_details->siteurl;
                        break;
                    }
                }
            }
        }

        // Redirect to the specific site URL for the language if it's not the current site
        if (!empty($redirect_url)) {
            wp_redirect($redirect_url);
            exit;
        }

        // If the language code does not exist or is the default language, redirect to the main site's homepage or switch the locale
        if (empty($lang_locale)) {
            $default_language_siteid = array_reduce($languages, static function ($carry, $item) {
                if ($item['default']) {
                    return $item['siteid'];
                }
                return $carry;
            });

            $default_site_details = get_blog_details(['blog_id' => $default_language_siteid]);
            if ($default_site_details) {
                wp_redirect($default_site_details->siteurl);
                exit;
            }
        } else if (!is_admin()) {
            switch_to_locale($lang_locale);
        }
    }
    else {
        // Switch to the default language if no language code is present in the URL and it's not a specific language redirect
        foreach ($languages as $language) {
            if (!empty($language['default'])) {
                switch_to_locale($language['locale']);
                return;
            }
        }
    }
}

add_action('init', 'mlomw_front_set_locale');


//// With this, we load site language based on site id, which is linking inside /wp-admin/network/sites.php
function mlomw_front_load_site_language_based_on_site_id(): void {

    // Only run this logic if in a multisite environment
    if (!is_multisite()) {
        return;
    }

    // Fetch the current site ID
    $current_blog_id = get_current_blog_id();

    // Fetch languages value from the mlomw_languages table using the existing function
    $languages = mlomw_front_fetch_languages_multisite();

    // Find the current site's language locale from the fetched languages
    foreach ($languages as $language) {
        if ((int) $language['siteid'] === $current_blog_id) {
            $lang_locale = $language['locale'];

            // If a locale is found for the current site, set the site's locale accordingly
            if (!empty($lang_locale)) {
                switch_to_locale($lang_locale);
                return; // Exit the function once the locale is switched
            }
        }
    }
}

add_action('init', 'mlomw_front_load_site_language_based_on_site_id');


//// Catch all available information in a moment where it is located
function mlomw_front_get_current_id(): string {

    global $wpdb, $post;

    // Get the current site ID
    $current_site_id = get_current_blog_id();

    // Get the current post type
    $current_type = isset($post) ? get_post_type($post) : '';

    // Get the current post/page ID
    $current_page_id = isset($post) ? $post->ID : '';

    // Check if it's the main site, use $current_page_id directly
    if ($current_site_id === 1) {
        return (string) $current_page_id;
    }

    // Define table name based on post type
    $table_name = $wpdb->base_prefix . 'mlomw_' . ($current_type === 'page' ? 'pages' : 'posts');

    // Define columns based on post type
    $ppid_column = ($current_type === 'page') ? 'pages_id' : 'posts_id';

    // Determine main column based on post type
    $main_column = ($current_type === 'page') ? 'main_pages_id' : 'main_posts_id';

    // Build the SQL query dynamically
    $query = $wpdb->prepare("SELECT $main_column FROM $table_name WHERE site_id = %d AND $ppid_column = %d", $current_site_id, $current_page_id);

    // Get the query result
    $result = $wpdb->get_var($query);

    return $result ?: '';
}


//// Based on the information that has that moment available and on the request redirect to another page or post that is only linked.
function mlomw_front_redirect_based_on_id(): void {

    if (!empty($_GET['p'])) {
        global $wpdb;
        $main_id = sanitize_text_field($_GET['p']);
        $current_site_id = get_current_blog_id();

        // Attempt to find a linked ID in the target site's tables
        $linked_id = $wpdb->get_var($wpdb->prepare(
            "(SELECT posts_id FROM {$wpdb->base_prefix}mlomw_posts WHERE main_posts_id = %d AND site_id = %d) 
            UNION 
            (SELECT pages_id FROM {$wpdb->base_prefix}mlomw_pages WHERE main_pages_id = %d AND site_id = %d)
            LIMIT 1",
            $main_id, $current_site_id, $main_id, $current_site_id
        ));

        if ($linked_id) {
            wp_redirect(home_url('/?p=' . $linked_id));
            exit;
        }

        // If $linked_id is not found, use $main_id
        wp_redirect(home_url('/?p=' . $main_id));
        exit;
    }
}

add_action('template_redirect', 'mlomw_front_redirect_based_on_id');


///////////////////////////////////////////////////////////////////////////////////////////////////////////


//// This is a testing function to check if the correct loading shows
function mlomw_front_testing_language_content(): string {

    // Fetch languages value from the mlomw_languages table using the existing function
    $languages = mlomw_front_fetch_languages_multisite();

    // Get the current language code (e.g., 'en_US', 'de_DE')
    $current_locale = get_locale();

    // Initialize variable to hold the language name
    $current_language_name = "Unknown Language";

    // Find the current language's name
    foreach ($languages as $lang) {
        if ($lang['locale'] === $current_locale) {
            $current_language_name = $lang['name'];
            break;
        }
    }

    // Translate the string and then apply the HTML tags
    return '<div>' . __('Current Language that you are using:', 'multilocaly') . ' <b>' . esc_html($current_language_name) . '</b></div>';
}

// Use shortcode [language_content]
add_shortcode('language_content', 'mlomw_front_testing_language_content');


//// Display current site ID post/page info and the type and whether it is Linked or not, this is more for testing not for production
function mlomw_front_display_current_site_post_info_and_type(): string {

    global $wpdb, $post;

    // Fetch languages value from the mlomw_languages table using the existing function
    $languages = mlomw_front_fetch_languages_multisite();

    // Fetch the current site's ID.
    $current_blog_id = get_current_blog_id();
    $display = "<div class='current-site-info'>";
    $display .= "<p>Current Site ID: <strong>$current_blog_id</strong></p>";

    // Attempt to find the language name for the current site
    $language_info = $languages[$current_blog_id] ?? null;

    if ($language_info) {
        $display .= "<p>Current Language: <strong>{$language_info['name']} ({$language_info['locale']})</strong></p>";
    } else {
        $display .= "<p>Language Information: <strong>Not Set</strong></p>";
    }

    if (is_singular()) {
        $current_post_id = $post->ID;
        $post_type = get_post_type($post);

        // Replace nested ternary operators with conditional statements
        if ($post_type === 'post') {
            $type_display = 'Post';
        } elseif ($post_type === 'page') {
            $type_display = 'Page';
        } else {
            $type_display = ucfirst($post_type);
        }

        $display .= "<p>Current Type: <strong>$type_display</strong></p>";

        $display .= "<p>Current $type_display ID: <strong>$current_post_id</strong></p>";

        $table_name = $post_type === 'page' ? $wpdb->base_prefix . 'mlomw_pages' : $wpdb->base_prefix . 'mlomw_posts';
        $column_name = $post_type === 'page' ? 'pages_id' : 'posts_id';
        $main_column_name = $post_type === 'page' ? 'main_pages_id' : 'main_posts_id';

        $main_post_id = $wpdb->get_var($wpdb->prepare("SELECT $main_column_name FROM $table_name WHERE site_id = %d AND $column_name = %d", $current_blog_id, $current_post_id));

        if ($main_post_id !== null) {
            $display .= "<p>Linked with Main $type_display ID: <strong>$main_post_id</strong></p>";
        } else {
            $display .= "<p>No linked or this is main $type_display.</p>";
        }
    } else {
        $display .= "<p>Not a singular post or page.</p>";
    }

    $display .= "</div>";
    return $display;
}

// Use shortcode [show_current_info]
add_shortcode('show_current_info', 'mlomw_front_display_current_site_post_info_and_type');


///////////////////////////////////////////////////////////////////////////////////////////////////////////


//// Displays just the Site Title and blog name inside the dropbox
function mlomw_front_display_site_names_dropdown_shortcode(): string {
    // Fetch site names from your multisite installation
    $sites = get_sites();
    $current_blog_id = get_current_blog_id(); // Get the ID of the current site
    $current_site_name = get_blog_details($current_blog_id)->blogname;

    // Retrieve the main_id for the current post/page using mlomw_front_get_current_id
    $current_id = mlomw_front_get_current_id();

    $output = '<div class="mlomw-site-names-dropdown">';
    $output .= '<select onchange="handleSiteChange(this);">';

    // Include the current site as the first, non-selectable option
    $output .= '<option value="" disabled selected style="display:none;">' . esc_html($current_site_name) . '</option>';

    // Loop through each site to create an option in the dropdown
    foreach ($sites as $site) {
        $site_id = (int) $site->blog_id;
        if ($site_id !== $current_blog_id) {
            $site_details = get_blog_details($site_id);
            $site_url = $site_details->siteurl;

            // Include data-pp-id attribute with the current main ID and site ID
            $output .= sprintf('<option value="%s" data-pp-id="%s" data-site-id="%s">%s</option>', esc_url($site_url), esc_attr($current_id), esc_attr($site_id), esc_html($site_details->blogname));
        }
    }

    $output .= '</select>';
    $output .= '</div>';

    return $output;
}

add_shortcode('multisite_dropbox_site_name', 'mlomw_front_display_site_names_dropdown_shortcode');


//// Displays just the available languages name inside the dropbox
function mlomw_front_display_just_languages_name_dropbox_shortcode(): string {

    // Fetch languages value from the mlomw_languages table using the existing function
    $languages = mlomw_front_fetch_languages_multisite();

    $current_blog_id = get_current_blog_id(); // Get the current site ID as an integer

    // Retrieve the main_id for the current post/page using mlomw_front_get_current_id
    $current_id = mlomw_front_get_current_id();

    // Identify the current site's language
    $current_language_info = array_values(array_filter($languages, static function($lang) use ($current_blog_id) {
        return (int)$lang['siteid'] === $current_blog_id;
    }))[0] ?? null;

    // Start building the output
    $output = '<div class="mlomw-languages-dropdown">';
    // Display the current language name or "Select Language" if not found, as the first, non-selectable option
    $selectLanguageText = $current_language_info ? esc_html($current_language_info['name']) : esc_html__('Select Language', 'multilocaly');
    $output .= '<select onchange="handleSiteChange(this);">';
    // Using single quotes for HTML attribute values to avoid escaping double quotes
    $output .= '<option value="" disabled selected style="display:none;">' . $selectLanguageText . '</option>';

    // Loop through each language to populate the dropdown, excluding the current language
    foreach ($languages as $lang) {
        if ((int)$lang['siteid'] !== $current_blog_id) {
            $siteDetails = get_blog_details($lang['siteid']);
            if ($siteDetails) {
                $site_url = $siteDetails->siteurl;
                $output .= sprintf(
                    '<option value="%s" data-pp-id="%s" data-site-id="%s">%s</option>',
                    esc_url($site_url),
                    esc_attr($current_id),
                    esc_attr($lang['siteid']),
                    esc_html($lang['name'])
                );
            }
        }
    }

    $output .= '</select>';
    $output .= '</div>';

    return $output;
}

// Use shortcode [multisite_dropbox_just_name]
add_shortcode('multisite_dropbox_just_name', 'mlomw_front_display_just_languages_name_dropbox_shortcode');



//// Displays just the available languages flag inside the dropbox
function mlomw_front_display_just_languages_flag_dropbox_shortcode(): string {

    // Get plugin dir URL
    $plugin_dir_url = get_multilocaly_directory_url();

    // Fetch languages from the mlomw_languages table
    $languages = mlomw_front_fetch_languages_multisite();

    // Get the current site ID
    $current_blog_id = get_current_blog_id();

    $output = '<div class="mlomw-dropdown-flags">';

    // Display the current language's flag or a placeholder if not found
    $current_language_info = array_values(array_filter($languages, static function($lang) use ($current_blog_id) {
        return (int)$lang['siteid'] === $current_blog_id;
    }))[0] ?? null;

    if ($current_language_info) {
        $current_flag_img_url = $plugin_dir_url . "/assets/img/flags/" . $current_language_info['flag'] . ".png";
        $output .= '<div class="mlomw-dropdown-selected"><img src="' . $current_flag_img_url . '" alt="' . $current_language_info['flag'] . '"><i class="fa-solid fa-angle-down"></i></div>';
    }

    $output .= '<div class="mlomw-dropdown-options" style="display: none;">';

    foreach ($languages as $lang) {
        if ((int)$lang['siteid'] !== $current_blog_id) {
            $site_url = get_site_url($lang['siteid']);
            $flag_img_url = $plugin_dir_url . "/assets/img/flags/" . $lang['flag'] . ".png";
            $output .= sprintf(
                '<div class="mlomw-dropdown-results" data-value="%s" data-site-id="%s"><img src="%s" alt="%s"></div>',
                esc_url($site_url),
                esc_attr($lang['siteid']),
                esc_url($flag_img_url),
                esc_attr($lang['flag'])
            );
        }
    }

    $output .= '</div></div>';

    ob_start(); // Start output buffering to capture the script
    ?>
    <script>
        jQuery(document).ready(function ($) {
            $('.mlomw-dropdown-results').on('click', function () {
                const url = $(this).data('value');
                const siteId = $(this).data('site-id');
                const currentPath = window.location.pathname;

                // Check if URL contains a query string, adjust accordingly
                const separator = url.includes('?') ? '&' : '?';
                const targetUrl = url + separator + 'p=' + <?php try {
                    echo json_encode(mlomw_front_get_current_id(), JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                } ?> + '/' + siteId;

                // Redirect to the target URL with the current page's path appended
                window.location.href = targetUrl + currentPath;
            });
        });
    </script>
    <?php
    $script = ob_get_clean(); // Get the buffered script content and stop output buffering

    // Print the script just before the closing body tag
    echo $script;

    return $output;
}

// Use shortcode [multisite_dropbox_just_flag]
add_shortcode('multisite_dropbox_just_flag', 'mlomw_front_display_just_languages_flag_dropbox_shortcode');




//// Displays just the available languages flag inline only
function mlomw_front_display_just_languages_flag_inline_shortcode(): string {

    // Get plugin dir URL
    $plugin_dir_url = get_multilocaly_directory_url();

    // Fetch languages from the mlomw_languages table
    $languages = mlomw_front_fetch_languages_multisite();

    // Get the current site ID
    $current_blog_id = get_current_blog_id();

    $output = '<div class="mlomw-inline-flags">';
    $output .= '<div class="mlomw-inline-options">';

    foreach ($languages as $lang) {
        if ((int)$lang['siteid'] !== $current_blog_id) {
            $site_url = get_site_url($lang['siteid']);
            $flag_img_url = $plugin_dir_url . "/assets/img/flags/" . $lang['flag'] . ".png";
            $output .= sprintf(
                '<div class="mlomw-inline-results" data-value="%s" data-site-id="%s"><img src="%s" alt="%s"></div>',
                esc_url($site_url),
                esc_attr($lang['siteid']),
                esc_url($flag_img_url),
                esc_attr($lang['flag'])
            );
        }
    }

    $output .= '</div></div>';

    ob_start(); // Start output buffering to capture the script
    ?>
    <script>
        jQuery(document).ready(function ($) {
            $('.mlomw-inline-results').on('click', function () {
                const url = $(this).data('value');
                const siteId = $(this).data('site-id');
                const currentPath = window.location.pathname;

                // Check if URL contains a query string, adjust accordingly
                const separator = url.includes('?') ? '&' : '?';
                const targetUrl = url + separator + 'p=' + <?php try {
                    echo json_encode(mlomw_front_get_current_id(), JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                } ?> + '/' + siteId;

                // Redirect to the target URL with the current page's path appended
                window.location.href = targetUrl + currentPath;
            });
        });
    </script>
    <?php
    $script = ob_get_clean(); // Get the buffered script content and stop output buffering

    // Print the script just before the closing body tag
    echo $script;

    return $output;
}

// Use shortcode [multisite_inline_just_flag]
add_shortcode('multisite_inline_just_flag', 'mlomw_front_display_just_languages_flag_inline_shortcode');

