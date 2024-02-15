<?php

/***
This is part of #Settings# configuration
 ***/

function mlomw_settings_page(): void {
	add_settings_section(
		'mlomw_settings_section',
		'',
		'',
		'mlomw_settings_page'
	);

	add_settings_field(
		'mlomw_disable_gutenberg',
		__('', 'multilocaly'), // Disable Gutenberg Editor
		'mlomw_disable_gutenberg_callback',
		'mlomw_settings_page',
		'mlomw_settings_section'
	);

	register_setting( 'mlomw_settings_group', 'mlomw_disable_gutenberg' );


	add_settings_field(
		'mlomw_remove_wp_title',
		__('', 'multilocaly'), // Remove "WordPress" from title
		'mlomw_remove_wp_title_callback',
		'mlomw_settings_page',
		'mlomw_settings_section'
	);

	register_setting( 'mlomw_settings_group', 'mlomw_remove_wp_title' );


	add_settings_field(
		'mlomw_remove_meta_generator',
		__('', 'multilocaly'), // Remove meta generator
		'mlomw_remove_meta_generator_callback',
		'mlomw_settings_page',
		'mlomw_settings_section'
	);

	register_setting( 'mlomw_settings_group', 'mlomw_remove_meta_generator' );
}

add_action( 'admin_init', 'mlomw_settings_page' );


//// This function Disable Gutenberg style anywhere & Enable classic editor.

function mlomw_disable_gutenberg(): void {
	$disable_gutenberg = get_option( 'mlomw_disable_gutenberg' );
	if ( $disable_gutenberg ) {
		add_filter('use_block_editor_for_post', '__return_false' );
		add_filter('use_block_editor_for_post_type', '__return_false' );
		add_filter( 'use_widgets_block_editor', '__return_false' );
	}
}

add_action( 'init', 'mlomw_disable_gutenberg' );


//// This remove WordPress in title on wp-login.php and in WordPress Dashboard

function mlomw_remove_wp_title($title) {
	$disable_wp_title = get_option('mlomw_remove_wp_title', false);
	if ($disable_wp_title) {
		$wp_names = [
			__('WordPress' ), // English
			__('ወርድፕረስ' ), // Amharic
			__('ووردبريس' ), //Arabic
			__('ওয়ার্ডপ্রেস' ), // Bengali
			__('وۆردپرێس' ), // Kurdish
			__('وردپرس ورود' ), // Persian (Afghanistan) ////
			__('وردپرس' ), // Persian
			__('વર્ડપ્રેસ' ), // Gujarati
			__('וורדפרס' ), // Hebrew
			__('वर्डप्रेस' ), // Hindi
			__('ವರ್ಡ್ಪ್ರೆಸ್' ), // Kannada
			__('워드프레스' ), // Korean
			__('वर्डप्रेस' ), // Marathi
			__('വേഡ്പ്രസ്സ്' ), // Malayalam
			__('वर्डप्रेस' ), // Nepali
			__('ورڈپریس' ), // Saraiki
			__('ورڊپريس' ), // Sindhi
			__('Вордпрес' ), // Serbian
		];

		$to_replace = [];
		foreach($wp_names as $wp_name) {
			$to_replace[] = " — " . $wp_name;
			$to_replace[] = " &#8211; " . $wp_name;
			$to_replace[] = " &#8212; " . $wp_name;
			$to_replace[] = " &mdash; " . $wp_name;
			$to_replace[] = " " . $wp_name;
			$to_replace[] = $wp_name . " &lsaquo; ";
			$to_replace[] = " &#8212;";
		}

		$title = str_replace($to_replace, '', $title);
	}
	return $title;
}

// Remove on page wp-login.php
add_filter('login_title', static function($title) {return mlomw_remove_wp_title($title);});

// Remove on <title>
add_filter('admin_title', static function($title) {return mlomw_remove_wp_title($title);});


//// Remove meta generator content on the code side of website

function mlomw_remove_meta_generator(): void {
	$remove_meta_gen = get_option( 'mlomw_remove_meta_generator', false );
	if ( $remove_meta_gen ) {
		remove_action('wp_head', 'wp_generator');
	}
}

add_action('init', 'mlomw_remove_meta_generator');


/**********
Generate HTML code on this page
**********/

function mlomw_render_settings_page(): void {

	?>
    <div class="license-container">
        <h3 class="license-title" style="margin:0;"><i class="dashicons fa-solid fa-sliders"></i> <?php _e( 'Settings', 'multilocaly' ); ?></h3>
        <hr>
        <form action="options.php" method="post" class="page_mlomw_settings_page">
			<?php
			settings_fields( 'mlomw_settings_group' );
			do_settings_sections( 'mlomw_settings_page' );
			submit_button( __( 'Save', 'multilocaly' ) );
			?>
        </form>
    </div>

	<?php
}


function mlomw_disable_gutenberg_callback(): void {

	$remove_meta_gen = get_option( 'mlomw_disable_gutenberg', false );
	echo '<label for="mlomw_disable_gutenberg" class="function_name">';
	echo '<input type="checkbox" id="mlomw_disable_gutenberg" name="mlomw_disable_gutenberg" value="1" ' . checked( $remove_meta_gen, 1, false ) . ' />';
	echo __('Disable Gutenberg Editor', 'multilocaly');
	echo '</label><div class="function_description">';
	echo __('With this, you Disable Gutenberg on your site and back to the classic editor, no matter where you are.', 'multilocaly');
	echo '</div>';
}


function mlomw_remove_wp_title_callback(): void {

	$remove_meta_gen = get_option( 'mlomw_remove_wp_title', false );
	echo '<label for="mlomw_remove_wp_title" class="function_name">';
	echo '<input type="checkbox" id="mlomw_remove_wp_title" name="mlomw_remove_wp_title" value="1" ' . checked( $remove_meta_gen, 1, false ) . ' />';
	echo __('Remove WordPress from the title', 'multilocaly');
	echo '</label><div class="function_description">';
	echo __('From the website title displayed in a browser tab and dashboard, and wp-login.php for enhanced branding and security.', 'multilocaly');
	echo '</div>';
}


function mlomw_remove_meta_generator_callback(): void {

	$remove_meta_gen = get_option( 'mlomw_remove_meta_generator', false );
	echo '<label for="mlomw_remove_meta_generator" class="function_name">';
	echo '<input type="checkbox" id="mlomw_remove_meta_generator" name="mlomw_remove_meta_generator" value="1" ' . checked( $remove_meta_gen, 1, false ) . ' />';
	echo __('Remove meta generator', 'multilocaly');
	echo '</label><div class="function_description">';
	echo __('Removing the meta generator, helps you to hide the version of WordPress that you are using from potential attackers.', 'multilocaly');
	echo '</div>';
}