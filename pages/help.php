<?php

/***
This is part of #Help# configuration
 ***/

function mlomw_help_page(): void {
	add_settings_section(
		'mlomw_help_section',
		'',
		'',
		'multilocaly'
	);
}

add_action( 'admin_init', 'mlomw_help_page' );


/**********
Generate HTML code on this page
**********/
function mlomw_render_help_page(): void {

	?>
    <div class="license-container">
        <h3 class="license-title" style="margin:0;"><i class="dashicons fa-solid fa-circle-question"></i> <?php _e( 'Help', 'multilocaly' ); ?></h3>
        <hr>
        <br>
        <div><i class="fa-regular fa-circle-dot"></i> <b>[language_content]</b><br>
<?php _e( 'Use shortcode how can testing function to check if correct loading and slow correct language.', 'multilocaly' ); ?></div>
        <br>
        <div><i class="fa-regular fa-circle-dot"></i> <b>[show_current_info]</b><br>
<?php _e( 'Use shortcode how show current info based on on-site ID, post ID.', 'multilocaly' ); ?></div>
        <br>
        <div><i class="fa-regular fa-circle-dot"></i> <b>[multisite_dropbox_site_name]</b><br>
<?php _e( 'Use a shortcode to display just the Site Title and blog name inside the dropbox.', 'multilocaly' ); ?></div>
        <br>
        <div><i class="fa-regular fa-circle-dot"></i> <b>[multisite_dropbox_just_name]</b><br>
<?php _e( 'Use a shortcode to display just the available languages name inside the dropbox.', 'multilocaly' ); ?></div>
        <br>
        <div><i class="fa-regular fa-circle-dot"></i> <b>[multisite_dropbox_just_flag]</b><br>
<?php _e( 'Use a shortcode to display just the available languages flag inside the dropbox.', 'multilocaly' ); ?></div>
        <br>
        <div><i class="fa-regular fa-circle-dot"></i> <b>[multisite_inline_just_flag]</b><br>
<?php _e( 'Use a shortcode to display just the available languages flag inline only.', 'multilocaly' ); ?></div>

    </div>

	<?php
}
