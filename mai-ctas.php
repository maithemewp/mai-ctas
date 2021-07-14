<?php

/**
 * Plugin Name:     Mai CTAs
 * Plugin URI:      https://bizbudding.com/mai-theme/plugins/mai-ctas/
 * Description:     Display calls to action on posts, pages, and custom post types conditionally by category, tag, taxonomy, or entry title.
 * Version:         0.3.0
 *
 * Author:          BizBudding
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Mai_CTAs_Plugin Class.
 *
 * @since 0.1.0
 */
final class Mai_CTAs_Plugin {

	/**
	 * @var   Mai_CTAs_Plugin The one true Mai_CTAs_Plugin
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main Mai_CTAs_Plugin Instance.
	 *
	 * Insures that only one instance of Mai_CTAs_Plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    Mai_CTAs_Plugin::setup_constants() Setup the constants needed.
	 * @uses    Mai_CTAs_Plugin::includes() Include the required files.
	 * @uses    Mai_CTAs_Plugin::hooks() Activate, deactivate, etc.
	 * @see     Mai_CTAs_Plugin()
	 * @return  object | Mai_CTAs_Plugin The one true Mai_CTAs_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new Mai_CTAs_Plugin;
			// Methods.
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-ctas' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-ctas' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'MAI_CTAS_VERSION' ) ) {
			define( 'MAI_CTAS_VERSION', '0.3.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'MAI_CTAS_PLUGIN_DIR' ) ) {
			define( 'MAI_CTAS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Classes Path.
		// if ( ! defined( 'MAI_CTAS_CLASSES_DIR' ) ) {
		// 	define( 'MAI_CTAS_CLASSES_DIR', MAI_CTAS_PLUGIN_DIR . 'classes/' );
		// }

		// Plugin Includes Path.
		if ( ! defined( 'MAI_CTAS_INCLUDES_DIR' ) ) {
			define( 'MAI_CTAS_INCLUDES_DIR', MAI_CTAS_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'MAI_CTAS_PLUGIN_URL' ) ) {
			define( 'MAI_CTAS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'MAI_CTAS_PLUGIN_FILE' ) ) {
			define( 'MAI_CTAS_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'MAI_CTAS_BASENAME' ) ) {
			define( 'MAI_CTAS_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}

	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function includes() {
		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';
		// Classes.
		// foreach ( glob( MAI_CTAS_CLASSES_DIR . '*.php' ) as $file ) { include $file; }
		// Includes.
		foreach ( glob( MAI_CTAS_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
	}

	/**
	 * Run the hooks.
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function hooks() {

		add_action( 'admin_init', [ $this, 'updater' ] );
		add_action( 'init',       [ $this, 'register_content_types' ] );

		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}

	/**
	 * Setup the updater.
	 *
	 * composer require yahnis-elsts/plugin-update-checker
	 *
	 * @since 0.1.0
	 *
	 * @uses https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return void
	 */
	public function updater() {
		// Bail if current user cannot manage plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			return;
		}

		// Setup the updater.
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maithemewp/mai-ctas/', __FILE__, 'mai-ctas' );

		// Maybe set github api token.
		if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
			$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
		}

		// Add icons for Dashboard > Updates screen.
		if ( function_exists( 'mai_get_updater_icons' ) && $icons = mai_get_updater_icons() ) {
			$updater->addResultFilter(
				function ( $info ) use ( $icons ) {
					$info->icons = $icons;
					return $info;
				}
			);
		}
	}

	/**
	 * Register content types.
	 *
	 * @return  void
	 */
	public function register_content_types() {

		/***********************
		 *  Custom Post Types  *
		 ***********************/

		register_post_type( 'mai_cta', [
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'labels'              => array(
				'name'               => _x( 'CTAs', 'CTA general name', 'mai-ctas' ),
				'singular_name'      => _x( 'CTA',  'CTA singular name', 'mai-ctas' ),
				'menu_name'          => _x( 'CTAs', 'CTA admin menu', 'mai-ctas' ),
				'name_admin_bar'     => _x( 'CTA',  'CTA add new on admin bar', 'mai-ctas' ),
				'add_new'            => _x( 'Add New', 'CTA', 'mai-ctas' ),
				'add_new_item'       => __( 'Add New CTA', 'mai-ctas' ),
				'new_item'           => __( 'New CTA', 'mai-ctas' ),
				'edit_item'          => __( 'Edit CTA', 'mai-ctas' ),
				'view_item'          => __( 'View CTA', 'mai-ctas' ),
				'all_items'          => __( 'CTAs', 'mai-ctas' ),
				'search_items'       => __( 'Search CTAs', 'mai-ctas' ),
				'parent_item_colon'  => __( 'Parent CTAs:', 'mai-ctas' ),
				'not_found'          => __( 'No CTAs found.', 'mai-ctas' ),
				'not_found_in_trash' => __( 'No CTAs found in Trash.', 'mai-ctas' )
			),
			'menu_icon'          => 'dashicons-megaphone',
			'public'             => false,
			'publicly_queryable' => false,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'show_ui'            => true,
			'rewrite'            => false,
			'supports'           => [ 'title', 'editor', 'page-attributes' ],
			'taxonomies'         => [ 'mai_cta_display' ],
		] );

		/***********************
		 *  Custom Taxonomies  *
		 ***********************/

		register_taxonomy( 'mai_cta_display', [ 'mai_cta' ], [
			'hierarchical' => false,
			'labels'       => [
				'name'                       => _x( 'CTA Display', 'CTA Display General Name', 'mai-ctas' ),
				'singular_name'              => _x( 'CTA Display', 'CTA Display Singular Name', 'mai-ctas' ),
				'menu_name'                  => __( 'CTA Display', 'mai-ctas' ),
				'all_items'                  => __( 'All Items', 'mai-ctas' ),
				'parent_item'                => __( 'Parent Item', 'mai-ctas' ),
				'parent_item_colon'          => __( 'Parent Item:', 'mai-ctas' ),
				'new_item_name'              => __( 'New Item Name', 'mai-ctas' ),
				'add_new_item'               => __( 'Add New Item', 'mai-ctas' ),
				'edit_item'                  => __( 'Edit Item', 'mai-ctas' ),
				'update_item'                => __( 'Update Item', 'mai-ctas' ),
				'view_item'                  => __( 'View Item', 'mai-ctas' ),
				'separate_items_with_commas' => __( 'Separate items with commas', 'mai-ctas' ),
				'add_or_remove_items'        => __( 'Add or remove items', 'mai-ctas' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'mai-ctas' ),
				'popular_items'              => __( 'Popular Items', 'mai-ctas' ),
				'search_items'               => __( 'Search Items', 'mai-ctas' ),
				'not_found'                  => __( 'Not Found', 'mai-ctas' ),
			],
			'meta_box_cb'       => true, // Hides metabox.
			'public'            => false,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_ui'           => false,
		] );
	}

	/**
	 * Plugin activation.
	 *
	 * @return  void
	 */
	public function activate() {
		$this->register_content_types();
		flush_rewrite_rules();
	}
}

/**
 * The main function for that returns Mai_CTAs_Plugin
 *
 * The main function responsible for returning the one true Mai_CTAs_Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Mai_CTAs_Plugin(); ?>
 *
 * @since 0.1.0
 *
 * @return object|Mai_CTAs_Plugin The one true Mai_CTAs_Plugin Instance.
 */
function mai_ctas_plugin() {
	return Mai_CTAs_Plugin::instance();
}

// Get Mai_CTAs_Plugin Running.
mai_ctas_plugin();

// Move the "example_cpt" Custom-Post-Type to be a submenu of the "example_parent_page_id" admin page.
add_action( 'admin_menu', 'fix_admin_menu_submenu', 15 );
function fix_admin_menu_submenu() {
	add_submenu_page(
		'mai-theme',
		esc_html__( 'CTAs', 'mai-engine' ),
		esc_html__( 'CTAs', 'mai-engine' ),
		'edit_posts',
		'edit.php?post_type=mai_cta',
		'',
		30
	);
}

add_filter( 'parent_file', 'fix_admin_parent_file' );
function fix_admin_parent_file( $parent_file ) {
	global $submenu_file, $current_screen;

	if ( 'mai_cta' !== $current_screen->post_type ) {
		return $parent_file;
	}

	$submenu_file = 'edit.php?post_type=mai_cta';
	$parent_file = 'mai-theme';

	return $parent_file;
}
