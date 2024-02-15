<?php

/***
This is part of #Languages# configuration
 ***/

function mlomw_languages_page(): void {
	add_settings_section(
		'mlomw_languages_section',
		'',
		'',
		'mlomw_languages_page'
	);
}

add_action( 'admin_init', 'mlomw_languages_page' );


//// Checking only shows language that is not added inside the database table mlomw_languages
function mlomw_check_is_language_in_table($locale): bool {

	global $wpdb;

// Table name
	$table_name = $wpdb->prefix . 'mlomw_languages';

// Prepare and execute the query
	$result = $wpdb->get_var(
		$wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE lang_locale = %s", $locale)
	);

// If the count is greater than 0, the language is in the database
	return (int) $result > 0;
}


//// Catch and get url links how can download site language translation information
function mlomw_get_translation_info($language_code): array {

	check_ajax_referer('get_translation_info_nonce', 'nonce');

	$lang_code_sel = isset($_POST['lang_package_locale']) ? sanitize_text_field($_POST['lang_package_locale']) : '';
	$translation_info = null;

	if ($lang_code_sel) {
		require_once(ABSPATH . 'wp-admin/includes/translation-install.php');

		$language_list = wp_get_available_translations();

		if (isset($language_list[$lang_code_sel]['package'])) {
			$package_url = $language_list[$lang_code_sel]['package'];
			$translation_info = [
				'success'      => true,
				'language'     => $lang_code_sel,
				'package'      => $language_list[$lang_code_sel]['package'],
				'package_url'  => esc_url($package_url), // Full URL to the translation package
			];
		} else {
			$translation_info = ['success' => false];
		}
	} else {
		$translation_info = ['success' => false];
	}

	wp_send_json($translation_info);

	// Return the translation info (an associative array) or null if not found
	return $translation_info;

	// To show select result you this part of code inside page from <div id='translation-info-container'></div>
}

add_action('wp_ajax_get_translation_info', 'mlomw_get_translation_info');


//// Download and extract the catch *.zip file that gets from the function mlomw_get_translation_info
function mlomw_download_language_pack($locale): void {

	require_once(ABSPATH . 'wp-admin/includes/translation-install.php');

	$translations = wp_get_available_translations();
	if (isset($translations[$locale]['package'])) {
		$package = $translations[$locale]['package'];
		$downloaded = download_url($package);

		if (!is_wp_error($downloaded)) {
			$destination = WP_CONTENT_DIR . '/languages/' . basename($package);

			if (!rename($downloaded, $destination)) {
				// Implement error handling here if needed
				return;
			}

			// Extract the zip file
			$zip = new ZipArchive();
			if ($zip->open($destination) === TRUE) {
				$zip->extractTo(WP_CONTENT_DIR . '/languages/');
				$zip->close();
				unlink($destination); // Optionally delete the zip file after extraction
			}
		}
	}
}


//// Trigger action for editing language
function mlomw_edit_selected_language(): void {

	// Verify nonce and perform additional security checks
	check_ajax_referer('edit_language_nonce', 'nonce');

	// Debugging statement
	//error_log('AJAX request to mlomw_edit_selected_language');

	// Get the language ID and form data from the AJAX request
	$lang_id = isset($_POST['lang_id']) ? (int) $_POST['lang_id'] : 0;
	$form_data = $_POST['formData'] ?? array();

	// Validate $lang_id and $form_data as needed

	// Prepare the data for database update
	$update_data = array();
	foreach ($form_data as $field_name => $field_value) {
		// Sanitize and validate each field value as needed
		$update_data[$field_name] = sanitize_text_field($field_value);
	}

	// Update the language details in the database
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlomw_languages';

	$result = $wpdb->update(
		$table_name,
		$update_data,
		compact( 'lang_id' )
	);

	if ($result !== false) {
		// Send a JSON response, indicating success
		wp_send_json(['success' => true]);
	} else {
		// Send a JSON response, indicating failure
		wp_send_json(['success' => false]);
	}
}

add_action('wp_ajax_edit_language', 'mlomw_edit_selected_language');


//// Trigger to update select language
function mlomw_update_selected_language(): void {

	// Verify nonce for security
	check_ajax_referer('update_language_nonce', 'nonce');

	// Get POSTed data
	$lang_id = isset($_POST['lang_id']) ? (int) $_POST['lang_id'] : 0;
	$updatedData = $_POST['data'] ?? [];

	// Sanitize and validate the data
	$lang_name = sanitize_text_field($updatedData['lang_name']);
	$lang_code = sanitize_text_field($updatedData['lang_code']);
	$lang_default = sanitize_text_field($updatedData['lang_default']);

	// Add other fields as necessary and perform necessary validation

	// Update database logic
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlomw_languages';

	$result = $wpdb->update(
		$table_name,
		compact( 'lang_name', 'lang_code', 'lang_default' ),
		compact( 'lang_id' ) // Where condition
	);

	if ($result !== false) {
		wp_send_json(['success' => true]);
	} else {
		wp_send_json(['success' => false, 'message' => 'Database update failed']);
	}
}

add_action('wp_ajax_update_language', 'mlomw_update_selected_language');


//// Trigger to delete select language
function mlomw_delete_select_language(): void {

// Verify nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_language_nonce')) {
		die('Permission error');
	}

	$lang_id = isset($_POST['lang_id']) ? absint($_POST['lang_id']) : 0;

// Perform deletion logic here (use $wpdb or any other method)
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlomw_languages';

	$wpdb->delete(
		$table_name,
		compact('lang_id'), // Correct column name is 'lang_id'
		array('%d')
	);
}

// Add AJAX action for deleting language
add_action('wp_ajax_delete_language', 'mlomw_delete_select_language');


/**********
Generate HTML code on this page
 **********/

function mlomw_render_languages_page(): void {

	?>

	<?php

	// Get plugin dir url name
	$plugin_dir_url = get_multilocaly_directory_url();

	// Get plugin dir name
	$plugin_dir_path = get_multilocaly_plugin_dir_path();

	$available_languages = [];

	try {
		// Include the PHP file to define $available_languages flag name and other information
		require $plugin_dir_path . 'includes/available_languages.php';

		// Output the evaluated $available_languages for debugging
		// var_dump($available_languages);

	} catch (Exception $e) {
		// Handle any errors that occur during evaluation
		echo '<p>Error evaluating available_languages.php: ' . $e->getMessage() . '</p>';
	}

	if (!$available_languages) {

		// If no languages are available, set a default empty array
		$available_languages = [
			'en_US' => ['name' => 'No available languages file includes/available_languages.php checking file'],
		];
	}

	// Handle form submission
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

		// Make sure to sanitize and validate input data before inserting into the database
		$langName    = sanitize_text_field( $_POST['name'] );
		$langLocale  = sanitize_text_field( $_POST['locale'] );
		$langCode    = sanitize_text_field( $_POST['code'] );
		$langFlag    = sanitize_text_field( $_POST['flag'] );
		$langDefault = sanitize_text_field( $_POST['default'] );

		global $wpdb;

		$table_name = $wpdb->prefix . 'mlomw_languages';

		// Check if a default language is already set
		$defaultLanguageExists = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE lang_default = 1");

		if ($defaultLanguageExists && $langDefault === '1') {
			// Default language already set
			echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error!', 'A default language is already set. Only one language can be set as the default.', 'error'); });</script>";
		} elseif ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE lang_locale = %s", $langLocale)) > 0) {
			// Language with the same locale already exists
			echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error!', 'A language with the same locale already exists.', 'error'); });</script>";
		} else {
			// Insert the new language
			$result = $wpdb->insert(
				$table_name,
				array(
					'lang_name'     => $langName,
					'lang_locale'   => $langLocale,
					'lang_code'     => $langCode,
					'lang_flag'     => $langFlag,
					'lang_default'  => $langDefault,
				),
				array('%s', '%s', '%s', '%s', '%d')
			);

			if ($result !== false) {
				// Language added successfully
				mlomw_download_language_pack($langLocale);
				echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Success!', 'Language added successfully!', 'success').then(function() { window.location.href = '" . admin_url('admin.php?page=mlomw_languages_page') . "'; }); });</script>";
			} else {
				// Handle database insertion error
				echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error!', 'An error occurred while adding the language.', 'error').then(function() { window.location.href = '" . admin_url('admin.php?page=mlomw_languages_page') . "'; }); });</script>";
			}
		}
	}

	// Encode the available languages into JSON
	try {
		$available_languages_json = json_encode( $available_languages, JSON_THROW_ON_ERROR );
	} catch ( JsonException ) {
	}

	?>

    <!-- Add this script to the head section -->
    <script>

        // Declare langData as a global variable
        const langData = <?php echo $available_languages_json; ?>;

        // Define pluginDirUrl
        const pluginDirUrl = '<?php echo esc_url( $plugin_dir_url ); ?>';

        // Define updateFormFields function
        function updateFormFields() {
            const selectedLang = jQuery('#lang_list').val();

            // Use the global langData variable
            if (langData[selectedLang]) {
                const language = langData[selectedLang];

                // Assign values to form fields
                jQuery('#lang_name').val(language['name']);
                jQuery('#lang_package_locale').val(selectedLang);
                jQuery('#lang_locale').val(selectedLang);
                jQuery('#lang_code').val(language['code']);

                // Update the language flag dynamically
                const languageFlag = language['flag'] || 'default-flag';
                jQuery('#lang_flag_img').attr('src', `${pluginDirUrl}/assets/img/flags/${languageFlag}.png`);

                // Update the value of the hidden input field
                jQuery('#lang_flag').val(languageFlag);

                // Make an AJAX request to get translation information
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    method: 'POST',
                    data: {
                        action: 'get_translation_info',
                        nonce: '<?php echo wp_create_nonce('get_translation_info_nonce'); ?>',
                        lang_package_locale: selectedLang,
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log(response); // Add this line for debugging
                        if (response.success) {
                            jQuery('#translation-info-container')
                                .html(
                                    "<br>Language: " + response.language + "<br><br>Translation package URL: <br>" + response.package);
                        } else {
                            jQuery('#translation-info-container').html("Translation package URL not found for " + selectedLang);
                        }
                    },
                    error: function() {
                        console.error('Error fetching translation information');
                    }
                });
            }
        }

        // Function on page load
        jQuery(document).ready(function () {
            updateFormFields();
        });

        jQuery(document).ready(function ($) {

            // Trigger and Handle the click event for editing select language
            $('.edit-language').on('click', function (e) {
                e.preventDefault();
                const langId = $(this).data('lang-id');
                const row = $(this).closest('tr');

                // Toggle the visibility of the fields for editing
                row.find('.language-info').toggle();
                row.find('.edit-field').toggle();

                // If editing is enabled, populate the input fields with current values
                if (row.find('.edit-field:visible').length > 0) {
                    row.find('.edit-field').each(function () {
                        const field = $(this);
                        const fieldName = field.attr('name');

                        // Check if the field has a value to display
                        const fieldValue = fieldName === 'lang_default'
                            ? row.find(`[data-field="${fieldName}"] i`).hasClass('fa-language') ? '1' : '0'
                            : row.find(`[data-field="${fieldName}"]`).text().trim();

                        field.val(fieldValue);
                    });
                }

                // Toggle the icon between edit and save
                const icon = $(this).find('i');
                if (icon.hasClass('fa-pen-to-square')) {
                    // If the icon is for editing, change it to save
                    icon.removeClass('fa-pen-to-square').addClass('fa-floppy-disk');
                } else {
                    // If the icon is for saving, change it back to edit
                    icon.removeClass('fa-floppy-disk').addClass('fa-pen-to-square');

                    // Collect data from the input fields for editing
                    const formData = {};
                    row.find('.edit-field:visible').each(function () {
                        const field = $(this);
                        const fieldName = field.attr('name');
                        const fieldValue = field.val();
                        formData[fieldName] = fieldValue;

                        // Update the language info with the edited value
                        const languageInfo = row.find(`[data-field="${fieldName}"]`);
                        languageInfo.text(fieldValue);
                    });

                    // Make an AJAX request to update the language
                    const nonce = $('#edit-language-nonce').val(); // Assuming you have a hidden input with the nonce value
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'edit_language',
                            lang_id: langId,
                            formData: formData,
                            nonce: nonce,
                        },
                        success: function (response) {
                            // Handle success, e.g., show a confirmation message
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Language updated successfully!',
                                    icon: 'success',
                                });

                                // Delay the page reload to give SweetAlert time to show the message
                                setTimeout(function () {
                                    // Reload the page upon successful update
                                    window.location.reload();
                                }, 1500); // Adjust the delay as needed
                            } else {
                                // Handle failure
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to update language.',
                                    icon: 'error',
                                });
                            }
                        },
                        error: function (error) {
                            // Handle errors
                            console.error(error);
                        },
                    });
                }
            });

            // Trigger and Handle the click event for saveing select language
            $('.save-language').on('click', function (e) {
                e.preventDefault();
                const langId = $(this).data('lang-id');
                const row = $(this).closest('tr');

                // Collect updated data
                const updatedData = {
                    lang_name: row.find('input[name="lang_name"]').val(),
                    lang_code: row.find('input[name="lang_code"]').val(),
                    lang_default: row.find('select[name="lang_default"]').val(),
                    // Add other fields as necessary
                };

                // AJAX request to update language
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'update_language', // PHP function to handle this request
                        lang_id: langId,
                        data: updatedData,
                        nonce: '<?php echo wp_create_nonce('update_language_nonce'); ?>',
                    },
                    success: function (response) {
                        // Handle response, update UI
                        if (response.success) {
                            // Update the row with new data, switch back to view mode
                        } else {
                            // Handle errors
                        }
                    },
                    error: function (error) {
                        console.error('Update failed:', error);
                    }
                });
            });

            // Trigger and Handle the click event for deleting select language
            $('.delete-language').on('click', function (e) {
                e.preventDefault();

                // Get the language ID from the data attribute
                const langId = $(this).data('lang-id');

                // Show SweetAlert confirmation
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You are about to delete this language.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, cancel!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {

                        // Make an AJAX request to delete the language

                        // Add nonce to your script
                        const nonce = '<?php echo wp_create_nonce( 'delete_language_nonce' ); ?>';
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'delete_language',
                                lang_id: langId,
                                nonce: nonce, // Include nonce in the data
                            },
                            success: function () {
                                // Show SweetAlert success message
                                Swal.fire({
                                    title: 'Language deleted successfully',
                                    icon: 'success',
                                });

                                // Delay the page reload to give SweetAlert time to show the message
                                setTimeout(function () {
                                    // Reload the page upon successful deletion
                                    window.location.reload();
                                }, 1500); // Adjust the delay as needed
                            },
                            error: function (error) {
                                console.error(error);
                            },
                        });
                    }
                });
            });
        });

    </script>

    <script>
        // DataTable configuration
        jQuery(document).ready(function ($) {

            // Check if there are any items in the list
            if ($('#the-list tr').length > 1) {

                $('.wp-list-table').DataTable({
                    "language": {
                        "lengthMenu": "<?php esc_html_e( 'Show _MENU_ entries', 'multilocaly' ); ?>",
                        "search": "<?php esc_html_e( 'Search:', 'multilocaly' ); ?>"
                    },

                    "columnDefs": [
                        {
                            // Assuming the "Flag," "Edit," and "Delete" columns have specific classes
                            "orderable": false, "targets": ['column-flag', 'column-edit', 'column-delete']
                        }
                    ],

                    "order": [[3, 'desc']], // Assuming the "Default" column is the 3rd column (index 3)
                    "iDisplayLength": 25 // Set your custom default value here
                });
            }
        });
    </script>

    <div class="license-container">
        <h3 class="license-title" style="margin:0;"><i class="dashicons fa-solid fa-language"></i> <?php _e( 'Languages', 'multilocaly' ); ?></h3>
        <hr>

        <!-- Start right side -->
        <div id="col-right">

            <div class="col-wrap">

                <br class="clear">

                <table class="wp-list-table widefat fixed striped table-view-list languages">

                    <thead>
                    <tr>
                        <th scope="col" id="name" class="manage-column column-name column-primary sortable desc"><span>Name</span></th>
                        <th scope="col" id="locale" class="manage-column column-locale sortable desc"><span>Locale</span></th>
                        <th scope="col" id="slug" class="manage-column column-slug sortable desc"><span>Code</span></th>
                        <th scope="col" id="default" class="manage-column column-default sortable desc"><span>Default</span></th>
                        <th scope="col" id="flag" class="manage-column column-flag"><span>Flag</span></th>
                        <th scope="col" id="edit" class="manage-column column-edit"><span>Edit</span></th>
                        <th scope="col" id="delete" class="manage-column column-delete"><span>Delete</span></th>
                    </tr>
                    </thead>

                    <tbody id="the-list">
					<?php
					// Fetch data from the database table
					global $wpdb;
					$table_name = $wpdb->prefix . 'mlomw_languages';
					$languages = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

					// Check if there are any languages
					if ($languages) {
						foreach ($languages as $language) {
							?>
                            <tr>

                                <td class="name column-name column-primary">
                                    <span class="language-info" data-field="lang_name"><?php echo esc_html($language['lang_name']); ?></span>
                                    <label>
                                        <input type="text" class="edit-field" name="lang_name" style="display:none;">
                                    </label>
                                </td>

                                <td class="locale column-locale">
									<?php echo esc_html($language['lang_locale']); ?>
                                </td>

                                <td class="slug column-slug">
                                    <span class="language-info" data-field="lang_code"><?php echo esc_html($language['lang_code']); ?></span>
                                    <label>
                                        <input type="text" class="edit-field" name="lang_code" style="display:none;">
                                    </label>
                                </td>

                                <td class="default column-default">
									<?php if ($language['lang_default']) : ?>
                                        <span class="language-info" data-field="lang_default"><i class="fa-solid fa-language"></i></span>
                                        <label>
                                            <select class="edit-field" name="lang_default" style="display:none;">
                                                <option value="0">No</option>
                                                <option value="1" selected>Yes</option>
                                            </select>
                                        </label>
									<?php endif; ?>
                                </td>

                                <td class="flag column-flag">
									<?php
									// Get plugin dir url name
									$plugin_dir_url = get_multilocaly_directory_url();
									echo "<img src='{$plugin_dir_url}assets/img/flags/{$language['lang_flag']}.png' alt='{$language['lang_flag']}' />";
									?>
                                </td>

                                <td class="edit column-edit">
                                    <a href="#" class="edit-language" data-lang-id="<?php echo esc_attr($language['lang_id']); ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>

                                <td class="delete column-delete">
                                    <a href="#" class="delete-language" data-lang-id="<?php echo esc_attr($language['lang_id']); ?>">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>
							<?php
						}
					}
					else {
						// Display a row for no items found
						?>
                        <tr class="no-items">
                            <td class="colspanchange" colspan="8">No items found.</td>
                        </tr>
						<?php
					}
					?>
                    </tbody>

                    <tfoot>
                    <tr>
                        <th scope="col" id="name" class="manage-column column-name column-primary sortable asc"><span>Name</span></th>
                        <th scope="col" id="locale" class="manage-column column-locale sortable desc"><span>Locale</span></th>
                        <th scope="col" id="slug" class="manage-column column-slug sortable desc"><span>Code</span></th>
                        <th scope="col" id="default" class="manage-column column-default sortable desc"><span>Default</span></th>
                        <th scope="col" id="flag" class="manage-column column-flag"><span>Flag</span></th>
                        <th scope="col" id="edit" class="manage-column column-edit"><span>Edit</span></th>
                        <th scope="col" id="delete" class="manage-column column-delete"><span>Delete</span></th>
                    </tr>
                    </tfoot>

                </table>

            </div>

        </div>
        <!-- End right side -->

        <!-- Start left side -->
        <div id="col-left">

            <div class="col-wrap">

                <div class="form-wrap">

                    <form id="add-lang" method="post" class="validate">

                        <div class="form-field">
                            <label for="lang_list">Choose a language</label>
                            <select name="lang_list" id="lang_list" class="lang-list" onchange="updateFormFields()">
                                <option value="" <?php echo empty($_POST['lang_list']) ? 'selected' : ''; ?> disabled>Select a language</option>
								<?php
								foreach ($available_languages as $locale => $language) {
									$lang_code = substr($locale, 0, 2);
									$dir = isset($language['dir']) ? esc_attr($language['dir']) : 'ltr';
									$flag = isset($language['flag']) ? esc_attr($language['flag']) : 'default-flag';

									// Check if the language is already in the database
									$isLanguageInDatabase = mlomw_check_is_language_in_table($locale);

									// If the language is not in the database, display it in the dropdown
									if (!$isLanguageInDatabase) {
										$selected = selected($locale, $_POST['lang_list'] ?? '', false);
										echo '<option value="' . esc_attr($locale) . '" lang="' . esc_attr($lang_code) . '" dir="' . esc_attr($dir) . '" data-flag="' . esc_attr($flag) . '" ' . $selected . '>' . esc_html($language['name']) . '</option>';
									}
								}
								?>
                            </select>
                            <p>You can choose a language in the list and click on the button add and activate.</p>
                            <input type="hidden" name="nonce" id="nonce" value="<?php echo wp_create_nonce('get_translation_info_nonce'); ?>">
                        </div>

                        <div class="form-field form-required">
                            <label for="lang_name">Full name</label>
                            <input name="name" id="lang_name" type="text" value="" size="40" aria-required="true">
                            <p>The name is how it is displayed on your site for example Deutsch (Schweiz).</p>
                        </div>

                        <div class="form-field form-required">
                            <label for="lang_locale">Locale</label>
                            <input name="locale" id="lang_locale" type="text" value="" size="40" aria-required="true" readonly="readonly">
                            <p>WordPress Locale for the language (for example de_CH).</p>
                        </div>

                        <div class="form-field">
                            <label for="lang_code">Language code</label>
                            <input name="code" id="lang_code" type="text" value="" size="40">
                            <p>To get you to define the desired prefix for the language <?php echo get_site_url(); ?>/<b>de</b>/xyz</p>
                        </div>

                        <div class="form-field">
                            <label for="lang_flag">Language Flag</label>
							<?php
							// Assuming a default language if not set
							$selectedLang = isset($_POST['lang_list']) ? sanitize_text_field($_POST['lang_list']) : '';

							// Display the language flag always
							$languageFlag = 'default-flag';
							if (!empty($selectedLang)) {
								// Check if $languageFlag is empty or not a valid flag
								$languageFlag = $available_languages[$selectedLang]['flag'] ?? '';

								if (empty($languageFlag) && isset($available_languages[$selectedLang]['lang_code'])) {
									$languageFlag = strtolower(substr($available_languages[$selectedLang]['lang_code'], 0, 2));
								}

								// Fallback to the first two characters of the language code
								$languageFlag = $languageFlag ?: 'default-flag';
							}

							// Output the flag image only if $languageFlag is not empty
							if (!empty($languageFlag)) {
								// Use esc_url to sanitize the image URL
								$flagImageUrl = esc_url("$plugin_dir_url/assets/img/flags/$languageFlag.png");

								echo "<img id='lang_flag_img' src='$flagImageUrl' alt=''>";
							}

							// Update the hidden input value based on the selected language
							echo "<input type='hidden' name='flag' id='lang_flag' value='$selectedLang'>";
							?>
                            <p>This is the flag for the selected language.</p>
                        </div>

                        <div class="form-field">
                            <label for="lang_default">Default Language</label>
                            <select name="default" id="lang_default">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                            <p>Select whether this is the default language.</p>
                        </div>

                        <button class="submit button-add-lang">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Add new language">
                        </button>

                    </form>

                </div>

            </div>

        </div>
        <!-- End left side -->

	<?php
}
