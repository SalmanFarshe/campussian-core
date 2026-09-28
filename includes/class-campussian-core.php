<?php
/**
 * Campussian Core — main controller class.
 *
 * Responsibilities:
 *  - Role registration & management
 *  - Default-role removal & user migration
 *  - Access control (wp-admin gate, login redirects, admin bar)
 *  - Page provisioning (Login + Dashboard)
 *  - Public helpers (guarded with function_exists)
 *
 * @package Campussian
 * @author  Salman Farshe
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Campussian_Core
 */
final class Campussian_Core {

	/**
	 * Instance.
	 *
	 * @var Campussian_Core|null
	 */
	private static $instance = null;

	/**
	 * Roles managed by this plugin.
	 *
	 * @var string[]
	 */
	private $managed_roles = array(
		'system_admin',
		'school_admin',
		'principal',
		'teacher',
		'guardian',
		'student',
	);

	/**
	 * Default WP roles that get removed after migration.
	 *
	 * @var string[]
	 */
	private $default_roles = array(
		'administrator',
		'editor',
		'author',
		'contributor',
		'subscriber',
	);

	/**
	 * Caps stripped from every non-system-admin user.
	 *
	 * @var string[]
	 */
	private $system_admin_caps = array(
		'manage_options',
		'edit_users',
		'delete_users',
		'list_users',
		'promote_users',
		'remove_users',
		'create_users',
		'activate_plugins',
		'deactivate_plugins',
		'install_plugins',
		'update_plugins',
		'delete_plugins',
		'switch_themes',
		'edit_themes',
		'edit_theme_options',
		'update_core',
		'install_themes',
		'update_themes',
		'delete_themes',
		'import',
		'export',
		'unfiltered_html',
	);

	/**
	 * Singleton.
	 *
	 * @since 1.0.0
	 * @return Campussian_Core
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_roles' ), 5 );
		add_action( 'admin_init', array( $this, 'restrict_admin_access' ) );
		add_action( 'admin_menu', array( $this, 'prune_admin_menus' ), 999 );
		add_filter( 'map_meta_cap', array( $this, 'protect_system_admins' ), 10, 4 );
		add_filter( 'user_has_cap', array( $this, 'filter_user_caps' ), 10, 4 );
		add_filter( 'editable_roles', array( $this, 'filter_editable_roles' ) );
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
		add_filter( 'show_admin_bar', array( $this, 'show_admin_bar' ) );
		add_action( 'template_redirect', array( $this, 'redirect_logged_in_from_login' ) );
	}

	/* ----------------------------------------------------------------------
	 * Roles
	 * ------------------------------------------------------------------- */

	/**
	 * Register the Campussian roles and converge their capabilities.
	 *
	 * Hooked to 'init'. Runs on every request but is cheap: the role rebuild
	 * and the user migration only happen on a fresh install or version
	 * upgrade, never on ordinary page loads.
	 *
	 * Note: WordPress's own roles (administrator, editor, ...) are left
	 * untouched. Deleting core roles would break other plugins and themes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_roles() {
		$stored_version = get_option( 'cmpsian_core_version', '' );
		$needs_setup    = ( CMPSIAN_CORE_VERSION !== $stored_version );

		// Rebuild the managed roles only on install / upgrade.
		if ( $needs_setup ) {
			foreach ( $this->managed_roles as $role_slug ) {
				if ( get_role( $role_slug ) ) {
					remove_role( $role_slug );
				}
			}
		}

		foreach ( $this->role_definitions() as $role_slug => $role_definition ) {
			// Create the role when missing; add_role() is a no-op if it exists.
			add_role( $role_slug, $role_definition['name'], $role_definition['capabilities'] );

			// Always converge capabilities so an already-existing role can never
			// be left stuck with a stale (incomplete) permission set.
			$role = get_role( $role_slug );
			if ( $role ) {
				foreach ( $role_definition['capabilities'] as $cap => $grant ) {
					if ( $grant && ! $role->has_cap( $cap ) ) {
						$role->add_cap( $cap );
					}
				}
			}
		}

		if ( $needs_setup ) {
			// One-time: move existing users onto the school roles.
			$this->migrate_users();
			update_option( 'cmpsian_core_version', CMPSIAN_CORE_VERSION );
		}

		// Legacy role from before this plugin existed.
		if ( get_role( 'campussian_principal' ) ) {
			remove_role( 'campussian_principal' );
		}
	}

	/**
	 * Full capability set for a custom-capability post type.
	 *
	 * Given a base such as "cmpsian_notice" this returns every primitive
	 * capability WordPress needs to *fully* manage that post type's posts
	 * (create, edit own/others/private/published, publish, delete, ...).
	 *
	 * @since 1.0.3
	 * @param string $base Capability base (e.g. "cmpsian_notice").
	 * @return array<string,bool> Capability map.
	 */
	private function cpt_caps( $base ) {
		return array(
			"edit_{$base}"              => true,
			"read_{$base}"              => true,
			"delete_{$base}"            => true,
			"edit_{$base}s"             => true,
			"edit_others_{$base}s"      => true,
			"edit_published_{$base}s"   => true,
			"edit_private_{$base}s"     => true,
			"publish_{$base}s"          => true,
			"read_private_{$base}s"     => true,
			"delete_{$base}s"           => true,
			"delete_others_{$base}s"    => true,
			"delete_published_{$base}s" => true,
			"delete_private_{$base}s"   => true,
		);
	}

	/**
	 * Full capability set for posts (covers CPTs registered against 'post').
	 *
	 * @since 1.0.3
	 * @return array<string,bool> Capability map.
	 */
	private function post_caps() {
		return array(
			'edit_posts'             => true,
			'edit_others_posts'      => true,
			'edit_published_posts'   => true,
			'edit_private_posts'     => true,
			'publish_posts'          => true,
			'read_private_posts'     => true,
			'delete_posts'           => true,
			'delete_others_posts'    => true,
			'delete_published_posts' => true,
			'delete_private_posts'   => true,
		);
	}

	/**
	 * Full capability set for pages.
	 *
	 * @since 1.0.3
	 * @return array<string,bool> Capability map.
	 */
	private function page_caps() {
		return array(
			'edit_pages'             => true,
			'edit_others_pages'      => true,
			'edit_published_pages'   => true,
			'edit_private_pages'     => true,
			'publish_pages'          => true,
			'read_private_pages'     => true,
			'delete_pages'           => true,
			'delete_others_pages'    => true,
			'delete_published_pages' => true,
			'delete_private_pages'   => true,
		);
	}

	/**
	 * Full content capability set shared by the content-managing roles.
	 *
	 * Covers every theme CPT (both the 'post' based ones and the two custom
	 * capability types used by Notices and Students).
	 *
	 * @since 1.0.3
	 * @return array<string,bool> Capability map.
	 */
	private function content_caps() {
		return array_merge(
			$this->post_caps(),
			$this->cpt_caps( 'cmpsian_notice' ),
			$this->cpt_caps( 'cmpsian_student' )
		);
	}


	/**
	 * Role definitions.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function role_definitions() {
		return array(
			'system_admin' => array(
				'name'         => __( 'System Admin', 'campussian-core' ),
				'capabilities' => array_merge(
					array(
						'read'                        => true,
						'upload_files'                => true,
						'manage_options'              => true,
						'edit_users'                  => true,
						'delete_users'                => true,
						'list_users'                  => true,
						'promote_users'               => true,
						'remove_users'                => true,
						'create_users'                => true,
						'activate_plugins'            => true,
						'deactivate_plugins'          => true,
						'install_plugins'             => true,
						'update_plugins'              => true,
						'delete_plugins'              => true,
						'switch_themes'               => true,
						'edit_themes'                 => true,
						'edit_theme_options'          => true,
						'update_core'                 => true,
						'install_themes'              => true,
						'update_themes'               => true,
						'delete_themes'               => true,
						'manage_categories'           => true,
						'moderate_comments'           => true,
						'import'                      => true,
						'export'                      => true,
						'unfiltered_html'             => true,
						'cmpsian_manage_applications' => true,
						'cmpsian_manage_students'     => true,
					),
					$this->content_caps(),
					// Pages are System-Admin exclusive.
					$this->page_caps()
				),
			),
			'principal'    => array(
				'name'         => __( 'Principal', 'campussian-core' ),
				'capabilities' => array_merge(
					array(
						'read'                        => true,
						'upload_files'                => true,
						'manage_categories'           => true,
						'moderate_comments'           => true,
						'list_users'                  => true,
						'create_users'                => true,
						'promote_users'               => true,
						'edit_users'                  => true,
						'remove_users'                => true,
						'add_users'                   => true,
						'cmpsian_manage_applications' => true,
						'cmpsian_manage_students'     => true,
					),
					$this->content_caps()
				),
			),
			'teacher'      => array(
				'name'         => __( 'Teacher', 'campussian-core' ),
				'capabilities' => array(
					'read'                        => true,
					'upload_files'                => true,
					// Notice CPT (full management).
					'edit_cmpsian_notice'         => true,
					'read_cmpsian_notice'         => true,
					'delete_cmpsian_notice'       => true,
					'edit_cmpsian_notices'        => true,
					'edit_others_cmpsian_notices' => true,
					'publish_cmpsian_notices'     => true,
					'read_private_cmpsian_notices'=> true,
					'delete_cmpsian_notices'      => true,
					'delete_private_cmpsian_notices' => true,
					'delete_published_cmpsian_notices' => true,
					'delete_others_cmpsian_notices' => true,
					'edit_private_cmpsian_notices' => true,
					'edit_published_cmpsian_notices' => true,
					// Student CPT (full management).
					'edit_cmpsian_student'        => true,
					'read_cmpsian_student'        => true,
					'delete_cmpsian_student'      => true,
					'edit_cmpsian_students'       => true,
					'edit_others_cmpsian_students'=> true,
					'publish_cmpsian_students'    => true,
					'read_private_cmpsian_students' => true,
					'delete_cmpsian_students'     => true,
					'delete_private_cmpsian_students' => true,
					'delete_published_cmpsian_students' => true,
					'delete_others_cmpsian_students' => true,
					'edit_private_cmpsian_students' => true,
					'edit_published_cmpsian_students' => true,
				),
			),
			'school_admin'  => array(
				'name'         => __( 'School Administrator', 'campussian-core' ),
				'capabilities' => array_merge(
					array(
						'read'                        => true,
						'upload_files'                => true,
						'manage_categories'           => true,
						'moderate_comments'           => true,
						'list_users'                  => true,
						'create_users'                => true,
						'promote_users'               => true,
						'edit_users'                  => true,
						'remove_users'                => true,
						'add_users'                   => true,
						'cmpsian_manage_applications' => true,
						'cmpsian_manage_students'     => true,
					),
					$this->content_caps()
				),
			),
			'guardian'     => array(
				'name'         => __( 'Guardian', 'campussian-core' ),
				'capabilities' => array(
					'read' => true,
				),
			),
			'student'      => array(
				'name'         => __( 'Student', 'campussian-core' ),
				'capabilities' => array(
					'read' => true,
				),
			),
		);
	}

	/**
	 * Migrate users from the old WP roles into the Campussian set.
	 *
	 * Mapping:
	 *  - administrator            -> system_admin
	 *  - editor / author / contributor -> teacher
	 *  - subscriber               -> guardian
	 *  - legacy campussian_principal -> principal
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function migrate_users() {
		global $wpdb;

		$map = array(
			'administrator'        => 'system_admin',
			'editor'               => 'teacher',
			'author'               => 'teacher',
			'contributor'          => 'teacher',
			'subscriber'           => 'guardian',
			'campussian_principal' => 'principal',
		);

		$cap_key = $wpdb->prefix . 'capabilities';
		$users   = get_users( array( 'number' => 500 ) );

		foreach ( $users as $user ) {
			// Exact restore after a deactivate/reactivate cycle.
			$previous = get_user_meta( $user->ID, '_cmpsian_previous_role', true );
			if ( $previous && get_role( $previous ) ) {
				update_user_meta(
					$user->ID,
					$cap_key,
					array( $previous => true )
				);
				delete_user_meta( $user->ID, '_cmpsian_previous_role' );
				continue;
			}

			/*
			 * Work on the RAW capabilities meta: WP_User::roles hides slugs
			 * whose roles are no longer registered (e.g. legacy defaults),
			 * which otherwise linger forever and keep re-adding junk.
			 */
			$raw = get_user_meta( $user->ID, $cap_key, true );
			if ( ! is_array( $raw ) ) {
				continue;
			}

			$changed = false;
			foreach ( $map as $old_slug => $new_slug ) {
				if ( isset( $raw[ $old_slug ] ) ) {
					$raw[ $new_slug ] = true;
					unset( $raw[ $old_slug ] );
					$changed = true;
				}
			}

			// Drop any other unknown/default leftovers entirely.
			foreach ( array_keys( $raw ) as $slug ) {
				if ( ! get_role( $slug ) ) {
					unset( $raw[ $slug ] );
					$changed = true;
				}
			}

			if ( $changed ) {
				update_user_meta( $user->ID, $cap_key, $raw );
			}
		}
	}

	/**
	 * Hide the WP default roles from any dropdown / role editor.
	 *
	 * @param array $roles Editable roles.
	 * @return array
	 */
	public function filter_editable_roles( $roles ) {
		foreach ( $this->default_roles as $slug ) {
			unset( $roles[ $slug ] );
		}
		return $roles;
	}

	/**
	 * Strip sensitive caps from non-system users (defence in depth).
	 *
	 * @param array  $allcaps All caps.
	 * @param string $cap     Cap.
	 * @param array  $args    Args.
	 * @param object $user    User.
	 * @return array
	 */
	public function filter_user_caps( $allcaps, $cap, $args, $user ) {
		if ( empty( $user->roles ) || ! is_array( $user->roles ) ) {
			return $allcaps;
		}

		// System admins keep everything.
		if ( in_array( 'system_admin', $user->roles, true ) ) {
			return $allcaps;
		}

		/*
		 * Only constrain users this plugin actually manages. Roles owned by
		 * WordPress core or by other plugins are left completely untouched so
		 * this filter can never break a third-party admin.
		 */
		if ( array_intersect( $this->managed_roles, $user->roles ) ) {
			unset( $allcaps['manage_options'] );
		}

		return $allcaps;
	}

	/* ----------------------------------------------------------------------
	 * Access control
	 * ------------------------------------------------------------------- */

	/**
	 * Block teachers/guardians/students from /wp-admin/.
	 *
	 * Allows profile.php and AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function restrict_admin_access() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && function_exists( 'is_admin' ) && 'profile' === $screen->id ) {
			return; // Let users edit their own profile.
		}

		$user = wp_get_current_user();
		$role = cmpsian_core_get_user_role( $user );

		if ( in_array( $role, array( 'student', 'guardian' ), true ) ) {
			wp_safe_redirect( cmpsian_core_get_dashboard_url() );
			exit;
		}
	}

	/**
	 * Hide the admin bar for front-end roles.
	 *
	 * @param bool $show Show bar?
	 * @return bool
	 */
	public function show_admin_bar( $show ) {
		if ( is_user_logged_in() ) {
			$role = cmpsian_core_get_user_role();
			if ( in_array( $role, array( 'student', 'guardian' ), true ) ) {
				return false;
			}
		}
		return $show;
	}
/**
	 * Route users to their portal after login.
	 *
	 * @since 1.0.0
	 * @param  string $redirect_to        Default redirect.
	 * @param  string $requested_redirect Requested redirect.
	 * @param  object $user               WP_User.
	 * @return string
	 */
	/**
	 * Send every user to the front-end portal Dashboard after login.
	 *
	 * Staff can still reach wp-admin via the account dropdown ("WP Admin").
	 *
	 * @since 1.1.0
	 * @param  string $redirect_to        Default redirect.
	 * @param  string $requested_redirect Requested redirect.
	 * @param  object $user               WP_User.
	 * @return string
	 */
	public function login_redirect( $redirect_to, $requested_redirect, $user ) {
		if ( ! $user || is_wp_error( $user ) ) {
			return $redirect_to;
		}

		return cmpsian_core_get_dashboard_url();
	}

	/**
	 * Send logged-in users away from the Login page to their Dashboard.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function redirect_logged_in_from_login() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! function_exists( 'is_page' ) || ! is_page( 'login' ) ) {
			return;
		}
		wp_safe_redirect( cmpsian_core_get_dashboard_url() );
		exit;
	}

	/**
	 * Hide system-level menus from School Admins & Principals.
	 *
	 * They keep content menus (Notices, Events, News, Gallery, Facility,
	 * Teachers, Alumni, Applications, Students, Pages, Media) and Users,
	 * but never see Plugins / Appearance / Tools / Settings.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function prune_admin_menus() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$role = cmpsian_core_get_user_role();

		if ( in_array( $role, array( 'school_admin', 'principal' ), true ) ) {
			remove_menu_page( 'plugins.php' );
			remove_menu_page( 'themes.php' );
			remove_menu_page( 'tools.php' );
			remove_menu_page( 'options-general.php' );
			remove_submenu_page( 'options-general.php', 'options-general.php' );
			// Pages are System-Admin exclusive.
			remove_menu_page( 'edit.php?post_type=page' );
			remove_submenu_page( 'edit.php?post_type=page', 'post-new.php?post_type=page' );
		}

		// Teachers: keep only the CPTs they are allowed to manage
		// (Students + Notices). Everything else is off-limits.
		if ( 'teacher' === $role ) {
			remove_menu_page( 'edit.php?post_type=page' );
			remove_menu_page( 'upload.php' );
			remove_menu_page( 'edit.php?post_type=cmpsian_teacher' );
			remove_menu_page( 'edit.php?post_type=cmpsian_alumni' );
			remove_menu_page( 'edit.php?post_type=cmpsian_application' );
			remove_menu_page( 'edit.php?post_type=cmpsian_event' );
			remove_menu_page( 'edit.php?post_type=cmpsian_facility' );
			remove_menu_page( 'edit.php?post_type=cmpsian_gallery' );
			remove_menu_page( 'users.php' );
			// NOTE: cmpsian_student + cmpsian_notice are intentionally kept.
		}
	}

	/**
	 * Prevent non-system-admins from editing/deleting System Admin accounts.
	 *
	 * @param array  $caps    Required caps.
	 * @param string $cap     Meta capability being mapped.
	 * @param int    $user_id Current user ID.
	 * @param array  $args    Extra args; [0] = target user ID for user caps.
	 * @return array
	 */
	public function protect_system_admins( $caps, $cap, $user_id, $args ) {
		$target_caps = array( 'edit_user', 'delete_user', 'promote_user', 'remove_user' );
		if ( ! in_array( $cap, $target_caps, true ) || empty( $args[0] ) ) {
			return $caps;
		}

		$target = get_userdata( $args[0] );
		if ( ! $target || ! in_array( 'system_admin', (array) $target->roles, true ) ) {
			return $caps;
		}

		$actor = get_userdata( $user_id );
		if ( $actor && ! in_array( 'system_admin', (array) $actor->roles, true ) ) {
			$caps = array( 'manage_options' ); // Only system admins have it.
		}

		return $caps;
	}

	/**
	 * On activation: register roles, migrate, hide defaults, provision pages.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate() {
		/*
		 * Force a full role rebuild and user migration on every activation.
		 * Clearing the stored version makes register_roles() treat this as a
		 * fresh setup, which also restores users after a
		 * deactivate -> reactivate cycle.
		 */
		delete_option( 'cmpsian_core_version' );

		$this->register_roles();
		$this->provision_pages();
		flush_rewrite_rules();
	}

	/**
	 * On deactivation: remove the Campussian roles so they "don't show".
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate() {
		/*
		 * Safe-teardown mode: the theme must stay fully usable without this
		 * plugin ("distinct capable"). So before removing the Campussian
		 * roles, remap every user onto standard WP fallback roles and make
		 * sure those fallback roles exist (with sane capabilities).
		 */
		$fallbacks = array(
			'system_admin' => 'administrator',
			'school_admin' => 'editor',
			'principal'    => 'editor',
			'teacher'      => 'subscriber',
			'guardian'     => 'subscriber',
			'student'      => 'subscriber',
		);

		// Seed caps for any missing fallback role from its campussian twin.
		$seeds = array(
			'administrator' => 'system_admin',
			'editor'        => 'school_admin',
			'subscriber'    => 'guardian',
		);
		foreach ( $seeds as $fallback_slug => $donor_slug ) {
			if ( ! get_role( $fallback_slug ) ) {
				$donor = get_role( $donor_slug );
				add_role(
					$fallback_slug,
					ucfirst( $fallback_slug ),
					$donor ? $donor->capabilities : array( 'read' => true )
				);
			}
		}

		// Remap users onto the fallbacks (remembering their real role).
		global $wpdb;
		$cap_key = $wpdb->prefix . 'capabilities';
		$users   = get_users( array( 'number' => 500 ) );

		foreach ( $users as $user ) {
			$raw = get_user_meta( $user->ID, $cap_key, true );
			if ( ! is_array( $raw ) ) {
				continue;
			}

			$target_role = '';
			foreach ( $this->managed_roles as $role_slug ) {
				if ( isset( $raw[ $role_slug ] ) ) {
					update_user_meta( $user->ID, '_cmpsian_previous_role', $role_slug );
					$target_role = isset( $fallbacks[ $role_slug ] ) ? $fallbacks[ $role_slug ] : 'subscriber';
					unset( $raw[ $role_slug ] );
				}
			}

			if ( '' === $target_role ) {
				continue;
			}

			// Drop any other unregistered leftovers while we're here.
			foreach ( array_keys( $raw ) as $slug ) {
				if ( ! get_role( $slug ) && ! isset( $seeds[ $slug ] ) ) {
					unset( $raw[ $slug ] );
				}
			}

			$raw[ $target_role ] = true;
			update_user_meta( $user->ID, $cap_key, array( $target_role => true ) );
		}

		// Now the Campussian roles can safely disappear.
		foreach ( $this->managed_roles as $role_slug ) {
			remove_role( $role_slug );
		}
		// Also remove the legacy role from the pre-plugin era.
		remove_role( 'campussian_principal' );
		flush_rewrite_rules();
	}
/**
	 * Ensure the Login and Dashboard pages exist with correct templates.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function provision_pages() {
		$this->provision_page( 'login', 'Login', 'page-login.php' );

		// Dashboard: reuse existing "dashboard" page if it exists.
		$dash = get_page_by_path( 'dashboard' );
		if ( $dash && $dash instanceof WP_Post ) {
			update_post_meta( $dash->ID, '_wp_page_template', 'page-dashboard.php' );
		} else {
			$this->provision_page( 'dashboard', 'Dashboard', 'page-dashboard.php' );
		}
	}

	/**
	 * Create (or update) a page with a given slug and template.
	 *
	 * @since 1.0.0
	 * @param string $slug     Page slug.
	 * @param string $title    Page title.
	 * @param string $template Template file name.
	 * @return int Page ID.
	 */
	private function provision_page( $slug, $title, $template ) {
		$existing = get_page_by_path( $slug );

		if ( $existing && $existing instanceof WP_Post ) {
			update_post_meta( $existing->ID, '_wp_page_template', $template );
			if ( 'publish' !== $existing->post_status ) {
				wp_update_post(
					array(
						'ID'          => $existing->ID,
						'post_status' => 'publish',
					)
				);
			}
			return $existing->ID;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => '',
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_wp_page_template', $template );
		}

		return (int) $page_id;
	}
}

/**
 * ----------------------------------------------------------------------------
 * Public helper functions (safe to call from the theme).
 * ----------------------------------------------------------------------------
 */

/**
 * Whether the Campussian Core plugin is active.
 *
 * Always true here; the theme guards with function_exists() so it degrades
 * gracefully when the plugin is missing / deactivated.
 *
 * @since 1.0.0
 * @return bool
 */
function cmpsian_core_active() {
	return true;
}

/**
 * Get the best role slug for a user (priority order for multi-role users).
 *
 * @since 1.0.0
 * @param WP_User|null $user User (defaults to current).
 * @return string Role slug or empty string.
 */
function cmpsian_core_get_user_role( $user = null ) {
	if ( null === $user || ! ( $user instanceof WP_User ) ) {
		$user = wp_get_current_user();
	}
	if ( ! $user instanceof WP_User ) {
		return '';
	}

	$priority = array(
		'system_admin' => 10,
		'school_admin' => 20,
		'principal'    => 30,
		'teacher'      => 40,
		'guardian'     => 50,
		'student'      => 60,
	);

	$best_slug = '';
	$best_rank = 999;
	foreach ( (array) $user->roles as $role_slug ) {
		if ( isset( $priority[ $role_slug ] ) && $priority[ $role_slug ] < $best_rank ) {
			$best_rank = $priority[ $role_slug ];
			$best_slug = $role_slug;
		}
	}

	return $best_slug;
}

/**
 * URL to the Dashboard page.
 *
 * @since 1.0.0
 * @return string
 */
function cmpsian_core_get_dashboard_url() {
	$dash = get_page_by_path( 'dashboard' );
	if ( $dash && $dash instanceof WP_Post && 'publish' === $dash->post_status ) {
		return get_permalink( $dash );
	}
	return home_url( '/' );
}

/**
 * URL to the Login page.
 *
 * @since 1.0.0
 * @return string
 */
function cmpsian_core_get_login_url() {
	$login = get_page_by_path( 'login' );
	if ( $login && $login instanceof WP_Post && 'publish' === $login->post_status ) {
		return get_permalink( $login );
	}
	return wp_login_url();
}
