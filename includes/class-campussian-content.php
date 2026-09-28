<?php
/**
 * Custom post types and taxonomies for Campussian.
 *
 * The school content types (Notices, Events, News, Gallery, Facilities,
 * Teachers, Alumni, Applications, Students, Settings) and their category
 * taxonomy live here rather than in the theme: registering post types and
 * taxonomies is plugin territory under the WordPress.org Theme Review
 * guidelines.
 *
 * The Campussian theme reads these types through WP_Query and provides the
 * presentation templates.
 *
 * @package Campussian
 * @author  WhyCodeBD
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Notice and Event custom post types.
 *
 * Hooked to 'init'.
 *
 * @since 1.0.0
 * @return void
 */
function cmpsian_register_cpts() {

	/* ---- Notices ------------------------------------------------------ */
	$notice_labels = array(
		'name'               => esc_html__( 'Notices', 'campussian-core' ),
		'singular_name'      => esc_html__( 'Notice', 'campussian-core' ),
		'add_new'            => esc_html__( 'Add New', 'campussian-core' ),
		'add_new_item'       => esc_html__( 'Add New Notice', 'campussian-core' ),
		'edit_item'          => esc_html__( 'Edit Notice', 'campussian-core' ),
		'new_item'           => esc_html__( 'New Notice', 'campussian-core' ),
		'view_item'          => esc_html__( 'View Notice', 'campussian-core' ),
		'search_items'       => esc_html__( 'Search Notices', 'campussian-core' ),
		'not_found'          => esc_html__( 'No notices found.', 'campussian-core' ),
		'not_found_in_trash' => esc_html__( 'No notices found in Trash.', 'campussian-core' ),
		'all_items'          => esc_html__( 'All Notices', 'campussian-core' ),
		'menu_name'          => esc_html__( 'Notices', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_notice',
		array(
			'labels'        => $notice_labels,
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-megaphone',
			'menu_position' => 20,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			// Slug "notice" (singular) so it does not collide with the
			// "Notices" page (/notices/) which uses page-notice.php.
			'rewrite'       => array( 'slug' => 'notice' ),
			'show_in_rest'  => true,
			// Custom caps so teachers can be granted Notice access in isolation.
			'capability_type' => 'cmpsian_notice',
			'capabilities'    => array(
				'edit_post'            => 'edit_cmpsian_notice',
				'read_post'            => 'read_cmpsian_notice',
				'delete_post'          => 'delete_cmpsian_notice',
				'edit_posts'           => 'edit_cmpsian_notices',
				'edit_others_posts'    => 'edit_others_cmpsian_notices',
				'publish_posts'        => 'publish_cmpsian_notices',
				'read_private_posts'   => 'read_private_cmpsian_notices',
				'create_posts'         => 'edit_cmpsian_notices',
				'delete_posts'         => 'delete_cmpsian_notices',
				'delete_private_posts' => 'delete_private_cmpsian_notices',
				'delete_published_posts' => 'delete_published_cmpsian_notices',
				'delete_others_posts'  => 'delete_others_cmpsian_notices',
				'edit_private_posts'   => 'edit_private_cmpsian_notices',
				'edit_published_posts' => 'edit_published_cmpsian_notices',
			),
			'map_meta_cap' => true,
		)
	);

	// Notice category taxonomy.
	register_taxonomy(
		'cmpsian_notice_cat',
		'cmpsian_notice',
		array(
			'labels'            => array(
				'name'          => esc_html__( 'Notice Categories', 'campussian-core' ),
				'singular_name' => esc_html__( 'Notice Category', 'campussian-core' ),
			),
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'notice-category' ),
		)
	);

	/* ---- Events ------------------------------------------------------- */
	$event_labels = array(
		'name'               => esc_html__( 'Events', 'campussian-core' ),
		'singular_name'      => esc_html__( 'Event', 'campussian-core' ),
		'add_new'            => esc_html__( 'Add New', 'campussian-core' ),
		'add_new_item'       => esc_html__( 'Add New Event', 'campussian-core' ),
		'edit_item'          => esc_html__( 'Edit Event', 'campussian-core' ),
		'new_item'           => esc_html__( 'New Event', 'campussian-core' ),
		'view_item'          => esc_html__( 'View Event', 'campussian-core' ),
		'search_items'       => esc_html__( 'Search Events', 'campussian-core' ),
		'not_found'          => esc_html__( 'No events found.', 'campussian-core' ),
		'not_found_in_trash' => esc_html__( 'No events found in Trash.', 'campussian-core' ),
		'all_items'          => esc_html__( 'All Events', 'campussian-core' ),
		'menu_name'          => esc_html__( 'Events', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_event',
		array(
			'labels'        => $event_labels,
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-calendar-alt',
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			// Slug "event" (singular) so it does not collide with the
			// "Events" page (/events/) which uses page-events.php.
			'rewrite'       => array( 'slug' => 'event' ),
			'show_in_rest'  => true,
		)
	);

	/* ---- Gallery ------------------------------------------------------ */
	$gallery_labels = array(
		'name'               => esc_html__( 'Gallery', 'campussian-core' ),
		'singular_name'      => esc_html__( 'Gallery Image', 'campussian-core' ),
		'add_new'            => esc_html__( 'Add New', 'campussian-core' ),
		'add_new_item'       => esc_html__( 'Add New Image', 'campussian-core' ),
		'edit_item'          => esc_html__( 'Edit Image', 'campussian-core' ),
		'new_item'           => esc_html__( 'New Image', 'campussian-core' ),
		'view_item'          => esc_html__( 'View Image', 'campussian-core' ),
		'search_items'       => esc_html__( 'Search Gallery', 'campussian-core' ),
		'not_found'          => esc_html__( 'No images found.', 'campussian-core' ),
		'not_found_in_trash' => esc_html__( 'No images found in Trash.', 'campussian-core' ),
		'all_items'          => esc_html__( 'All Images', 'campussian-core' ),
		'menu_name'          => esc_html__( 'Gallery', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_gallery',
		array(
			'labels'        => $gallery_labels,
			'public'        => true,
			'has_archive'   => false,
			'menu_icon'     => 'dashicons-format-gallery',
			'menu_position' => 22,
			'supports'      => array( 'title', 'thumbnail' ),
			'rewrite'       => array( 'slug' => 'gallery-image' ),
			'show_in_rest'  => true,
		)
	);

	/* ---- Facilities --------------------------------------------------- */
	$facility_labels = array(
		'name'               => esc_html__( 'Facilities', 'campussian-core' ),
		'singular_name'      => esc_html__( 'Facility', 'campussian-core' ),
		'add_new'            => esc_html__( 'Add New', 'campussian-core' ),
		'add_new_item'       => esc_html__( 'Add New Facility', 'campussian-core' ),
		'edit_item'          => esc_html__( 'Edit Facility', 'campussian-core' ),
		'new_item'           => esc_html__( 'New Facility', 'campussian-core' ),
		'view_item'          => esc_html__( 'View Facility', 'campussian-core' ),
		'search_items'       => esc_html__( 'Search Facilities', 'campussian-core' ),
		'not_found'          => esc_html__( 'No facilities found.', 'campussian-core' ),
		'not_found_in_trash' => esc_html__( 'No facilities found in Trash.', 'campussian-core' ),
		'all_items'          => esc_html__( 'All Facilities', 'campussian-core' ),
		'menu_name'          => esc_html__( 'Facilities', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_facility',
		array(
			'labels'        => $facility_labels,
			'public'        => true,
			'has_archive'   => false,
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 23,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'rewrite'       => array( 'slug' => 'facility' ),
			'show_in_rest'  => true,
		)
	);

	/* ---- Teachers ----------------------------------------------------- */
	$teacher_labels = array(
		'name'               => esc_html__( 'Teachers', 'campussian-core' ),
		'singular_name'      => esc_html__( 'Teacher', 'campussian-core' ),
		'add_new'            => esc_html__( 'Add New', 'campussian-core' ),
		'add_new_item'       => esc_html__( 'Add New Teacher', 'campussian-core' ),
		'edit_item'          => esc_html__( 'Edit Teacher', 'campussian-core' ),
		'new_item'           => esc_html__( 'New Teacher', 'campussian-core' ),
		'view_item'          => esc_html__( 'View Teacher', 'campussian-core' ),
		'search_items'       => esc_html__( 'Search Teachers', 'campussian-core' ),
		'not_found'          => esc_html__( 'No teachers found.', 'campussian-core' ),
		'not_found_in_trash' => esc_html__( 'No teachers found in Trash.', 'campussian-core' ),
		'all_items'          => esc_html__( 'All Teachers', 'campussian-core' ),
		'menu_name'          => esc_html__( 'Teachers', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_teacher',
		array(
			'labels'        => $teacher_labels,
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-businessperson',
			'menu_position' => 24,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'rewrite'       => array( 'slug' => 'teachers', 'with_front' => false ),
			'show_in_rest'  => true,
		)
	);

	/* ---- Alumni ------------------------------------------------------- */
	$alumni_labels = array(
		'name'               => esc_html__( 'Alumni', 'campussian-core' ),
		'singular_name'      => esc_html__( 'Alumni', 'campussian-core' ),
		'add_new'            => esc_html__( 'Add New', 'campussian-core' ),
		'add_new_item'       => esc_html__( 'Add New Alumni', 'campussian-core' ),
		'edit_item'          => esc_html__( 'Edit Alumni', 'campussian-core' ),
		'new_item'           => esc_html__( 'New Alumni', 'campussian-core' ),
		'view_item'          => esc_html__( 'View Alumni', 'campussian-core' ),
		'search_items'       => esc_html__( 'Search Alumni', 'campussian-core' ),
		'not_found'          => esc_html__( 'No alumni found.', 'campussian-core' ),
		'not_found_in_trash' => esc_html__( 'No alumni found in Trash.', 'campussian-core' ),
		'all_items'          => esc_html__( 'All Alumni', 'campussian-core' ),
		'menu_name'          => esc_html__( 'Alumni', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_alumni',
		array(
			'labels'        => $alumni_labels,
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-groups',
			'menu_position' => 25,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'rewrite'       => array( 'slug' => 'alumni', 'with_front' => false ),
			'show_in_rest'  => true,
		)
	);

	/* ---- News --------------------------------------------------------- */
	$news_labels = array(
		'name'               => esc_html__( 'News', 'campussian-core' ),
		'singular_name'      => esc_html__( 'News', 'campussian-core' ),
		'add_new'            => esc_html__( 'Add New', 'campussian-core' ),
		'add_new_item'       => esc_html__( 'Add New News', 'campussian-core' ),
		'edit_item'          => esc_html__( 'Edit News', 'campussian-core' ),
		'new_item'           => esc_html__( 'New News', 'campussian-core' ),
		'view_item'          => esc_html__( 'View News', 'campussian-core' ),
		'search_items'       => esc_html__( 'Search News', 'campussian-core' ),
		'not_found'          => esc_html__( 'No news found.', 'campussian-core' ),
		'not_found_in_trash' => esc_html__( 'No news found in Trash.', 'campussian-core' ),
		'all_items'          => esc_html__( 'All News', 'campussian-core' ),
		'menu_name'          => esc_html__( 'News', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_news',
		array(
			'labels'        => $news_labels,
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-welcome-write-blog',
			'menu_position' => 26,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'rewrite'       => array( 'slug' => 'news', 'with_front' => false ),
			'show_in_rest'  => true,
		)
	);

	/* ---- Admission Applications -------------------------------------- */
	$application_labels = array(
		'name'               => esc_html__( 'Admission Applications', 'campussian-core' ),
		'singular_name'      => esc_html__( 'Application', 'campussian-core' ),
		'add_new'            => esc_html__( 'Add New', 'campussian-core' ),
		'add_new_item'       => esc_html__( 'Add New Application', 'campussian-core' ),
		'edit_item'          => esc_html__( 'Edit Application', 'campussian-core' ),
		'new_item'           => esc_html__( 'New Application', 'campussian-core' ),
		'view_item'          => esc_html__( 'View Application', 'campussian-core' ),
		'search_items'       => esc_html__( 'Search Applications', 'campussian-core' ),
		'not_found'          => esc_html__( 'No applications found.', 'campussian-core' ),
		'not_found_in_trash' => esc_html__( 'No applications found in Trash.', 'campussian-core' ),
		'all_items'          => esc_html__( 'All Applications', 'campussian-core' ),
		'menu_name'          => esc_html__( 'Applications', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_application',
		array(
			'labels'        => $application_labels,
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'menu_icon'     => 'dashicons-clipboard',
			'menu_position' => 27,
			'supports'      => array( 'title' ),
			'show_in_rest'  => true,
			'has_archive'   => false,
			'exclude_from_search' => true,
			'capability_type' => 'post',
		)
	);

	/* ---- Students ----------------------------------------------------- */
	$student_labels = array(
		'name'               => esc_html__( 'Students', 'campussian-core' ),
		'singular_name'      => esc_html__( 'Student', 'campussian-core' ),
		'add_new'            => esc_html__( 'Add New', 'campussian-core' ),
		'add_new_item'       => esc_html__( 'Add New Student', 'campussian-core' ),
		'edit_item'          => esc_html__( 'Edit Student', 'campussian-core' ),
		'new_item'           => esc_html__( 'New Student', 'campussian-core' ),
		'view_item'          => esc_html__( 'View Student', 'campussian-core' ),
		'search_items'       => esc_html__( 'Search Students', 'campussian-core' ),
		'not_found'          => esc_html__( 'No students found.', 'campussian-core' ),
		'not_found_in_trash' => esc_html__( 'No students found in Trash.', 'campussian-core' ),
		'all_items'          => esc_html__( 'All Students', 'campussian-core' ),
		'menu_name'          => esc_html__( 'Students', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_student',
		array(
			'labels'          => $student_labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-id-alt',
			'menu_position'   => 28,
			'supports'        => array( 'title' ),
			'show_in_rest'    => true,
			'has_archive'     => false,
			'exclude_from_search' => true,
			// Custom caps so teachers can be granted Student access in isolation.
			'capability_type' => 'cmpsian_student',
			'capabilities'    => array(
				'edit_post'            => 'edit_cmpsian_student',
				'read_post'            => 'read_cmpsian_student',
				'delete_post'          => 'delete_cmpsian_student',
				'edit_posts'           => 'edit_cmpsian_students',
				'edit_others_posts'    => 'edit_others_cmpsian_students',
				'publish_posts'        => 'publish_cmpsian_students',
				'read_private_posts'   => 'read_private_cmpsian_students',
				'create_posts'         => 'edit_cmpsian_students',
				'delete_posts'         => 'delete_cmpsian_students',
				'delete_private_posts' => 'delete_private_cmpsian_students',
				'delete_published_posts' => 'delete_published_cmpsian_students',
				'delete_others_posts'  => 'delete_others_cmpsian_students',
				'edit_private_posts'   => 'edit_private_cmpsian_students',
				'edit_published_posts' => 'edit_published_cmpsian_students',
			),
			'map_meta_cap' => true,
		)
	);

	/* ---- Settings ----------------------------------------------------- */
	$setting_labels = array(
		'name'          => esc_html__( 'Campussian Settings', 'campussian-core' ),
		'singular_name' => esc_html__( 'Settings', 'campussian-core' ),
		'edit_item'     => esc_html__( 'Edit Theme Settings', 'campussian-core' ),
		'add_new'       => esc_html__( 'Add Settings', 'campussian-core' ),
		'add_new_item'  => esc_html__( 'Add Theme Settings', 'campussian-core' ),
		'search_items'  => esc_html__( 'Search Settings', 'campussian-core' ),
		'not_found'     => esc_html__( 'No settings found.', 'campussian-core' ),
		'menu_name'     => esc_html__( 'Campussian Settings', 'campussian-core' ),
	);

	register_post_type(
		'cmpsian_setting',
		array(
			'labels'          => $setting_labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-admin-generic',
			'menu_position'   => 59,
			'supports'        => array( 'title', 'editor' ),
			'show_in_rest'    => true,
			'has_archive'     => false,
			'exclude_from_search' => true,
			'capability_type' => 'post',
			'capabilities'    => array(
				'create_posts' => 'do_not_allow', // Single instance.
			),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'cmpsian_register_cpts' );

/**
 * Flush rewrite rules on plugin activation so the CPT permalinks resolve.
 *
 * The types above are registered on 'init' (priority 10) earlier in the same
 * request, so the rules are already built when activation runs.
 *
 * @since 1.0.0
 * @return void
 */
function cmpsian_core_flush_content_rules() {
	flush_rewrite_rules();
}