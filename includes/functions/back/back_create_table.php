<?php

function mlomw_check_plugin_database_tables(): void {

	global $wpdb;

	// Table names
	$mlomw_languages = $wpdb->prefix . 'mlomw_languages';
	$mlomw_pages = $wpdb->prefix . 'mlomw_pages';
	$mlomw_posts = $wpdb->prefix . 'mlomw_posts';
	$mlomw_posts_categories = $wpdb->prefix . 'mlomw_posts_categories';
	$mlomw_posts_tags = $wpdb->prefix . 'mlomw_posts_tags';

	// Check if tables exist
	$mlomw_languages_exists = $wpdb->get_var("SHOW TABLES LIKE '$mlomw_languages'");
	$mlomw_pages_exists = $wpdb->get_var("SHOW TABLES LIKE '$mlomw_pages'");
	$mlomw_posts_exists = $wpdb->get_var("SHOW TABLES LIKE '$mlomw_posts'");
	$mlomw_posts_categories_exists = $wpdb->get_var("SHOW TABLES LIKE '$mlomw_posts_categories'");
	$mlomw_posts_tags_exists = $wpdb->get_var("SHOW TABLES LIKE '$mlomw_posts_tags'");

	// If tables exist, skip the creation process
	if ($mlomw_languages_exists && $mlomw_pages_exists &&$mlomw_posts_exists && $mlomw_posts_categories_exists && $mlomw_posts_tags_exists) {
		return;
	}

	// If tables don't exist, create them //

	// Create mlomw_languages
	$sql_mlomw_languages = "CREATE TABLE IF NOT EXISTS $mlomw_languages (
	    `lang_id` int NOT NULL AUTO_INCREMENT,
		`lang_name` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NULL DEFAULT NULL,
		`lang_locale` varchar(20) DEFAULT NULL,
		`lang_code` varchar(20) DEFAULT NULL,
		`lang_flag` varchar(20) DEFAULT NULL,
		`lang_default` bigint(20) DEFAULT NULL,
		`lang_site_id` int NOT NULL,
	    PRIMARY KEY (`lang_id`)
	)";

	// Create mlomw_pages
	$sql_mlomw_pages = "CREATE TABLE IF NOT EXISTS $mlomw_pages (
	    `id` int NOT NULL AUTO_INCREMENT,
	    `lang_id` int NOT NULL,
	    `site_id` int NOT NULL,
	    `main_pages_id` int NOT NULL,
	    `pages_id` int NOT NULL,
	    PRIMARY KEY (`id`),
	    UNIQUE KEY `unique_lang_site_page` (`lang_id`, `site_id`, `pages_id`)
	)";

	// Create mlomw_posts
	$sql_mlomw_posts = "CREATE TABLE IF NOT EXISTS $mlomw_posts (
	    `id` int NOT NULL AUTO_INCREMENT,
	    `lang_id` int NOT NULL,
	    `site_id` int NOT NULL,
	    `main_posts_id` int NOT NULL,
	    `posts_id` int NOT NULL,
	    PRIMARY KEY (`id`),
	    UNIQUE KEY `unique_lang_site_post` (`lang_id`, `site_id`, `posts_id`)
	)";

	// Create mlomw_posts_categories
	$sql_mlomw_posts_categories = "CREATE TABLE IF NOT EXISTS $mlomw_posts_categories (
        `id` int NOT NULL AUTO_INCREMENT,
	    `lang_id` int NOT NULL,
	    `site_id` int NOT NULL,
	    `main_categories_id` int NOT NULL,
	    `categories_id` int NOT NULL,
	    PRIMARY KEY (`id`),
	    UNIQUE KEY `unique_lang_site_post` (`lang_id`, `site_id`, `categories_id`)
	)";

	// Create mlomw_posts_tags
	$sql_mlomw_posts_tags = "CREATE TABLE IF NOT EXISTS $mlomw_posts_tags (
        `id` int NOT NULL AUTO_INCREMENT,
	    `lang_id` int NOT NULL,
	    `site_id` int NOT NULL,
	    `main_tags_id` int NOT NULL,
	    `tags_id` int NOT NULL,
	    PRIMARY KEY (`id`),
	    UNIQUE KEY `unique_lang_site_post` (`lang_id`, `site_id`, `tags_id`)
	)";

	// Execute the creation queries
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql_mlomw_languages);
	dbDelta($sql_mlomw_posts);
	dbDelta($sql_mlomw_posts_categories);
	dbDelta($sql_mlomw_posts_tags);
	dbDelta($sql_mlomw_pages);
}
