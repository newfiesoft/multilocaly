<?php

/***
This is part of #General# configuration
 ***/

function mlomw_general_page(): void {
	add_settings_section(
		'mlomw_general_section',
		'',
		'',
		'multilocaly'
	);
}

add_action( 'admin_init', 'mlomw_general_page' );


/**********
Generate HTML code on this page
**********/
function mlomw_render_general_page(): void {

	?>
    <div class="license-container">
        <h3 class="license-title" style="margin:0;"><i class="dashicons fa-solid fa-circle-info"></i> <?php _e( 'Welcome', 'multilocaly' ); ?></h3>
        <hr>
        <p style="padding-top:10px;"><?php _e( 'Hi,', 'multilocaly' ); ?></p>
        <p><?php _e( 'We are honored that you decided to use our plugin.', 'multilocaly' ); ?></p>
        <p><?php _e( 'As you read before installing and activating this plugin, with him based on WordPress multisite you can create an independent multilingual website that can be linked to each other if you want in an easy way to use.', 'multilocaly' ); ?></p>
        <p><?php _e( 'If you like this plugin it would be nice from your side to give us your rating and feedback.', 'multilocaly' ); ?>
            <a href="https://wordpress.org/support/plugin/multilocaly/reviews/#new-post" target="_blank" class="rate-star-filled">
                <i class="fa-regular fa-star"></i>
                <i class="fa-solid fa-star-half-stroke"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
            </a>.
        </p>
        <p>
			<?php _e( 'For help, you have our ', 'multilocaly' ); ?>
            <a href="https://wordpress.org/plugins/multilocaly/#faq" target="_blank" class="faq-filled"><?php _e( 'FAQ', 'multilocaly' ); ?> <i class="fa-solid fa-comments"></i></a>
			<?php _e( 'part, or if that does not help you can write your question in the', 'multilocaly' ); ?>
            <a href="https://wordpress.org/support/plugin/multilocaly/" target="_blank" class="help-filled"><?php _e( 'support sections.', 'multilocaly' ); ?> <i class="fa-regular fa-life-ring"></i></a>.
        </p>
        <p>
			<?php _e( 'We would be very pleased if you supported the advancement of this plugin with your ', 'multilocaly' ); ?>
            <a href="https://newfiesoft.com/donate/" target="_blank" class="donate-filled"><?php _e( 'donation', 'multilocaly' ); ?> <i class="fa-solid fa-heart"></i></a>.
        </p>

        <br>
		<?php _e( 'Enjoy your work', 'multilocaly' ); ?>... <i class="fa-solid fa-handshake"></i>
    </div>

	<?php
}
