<?php

/**
 * Plugin Name: Production Image Redirector
 * Plugin URI: https://github.com/KreigD/Production-Image-Redirector
 * Description: Redirects all image URLs on the current site to a production site URL. Useful for local/test environments to use production images without downloading the entire uploads folder.
 * Version: 1.0.0
 * Author: Kreig Durham
 * Author URI: https://kreigd.com
 * License: GPL v2 or later
 * Text Domain: production-image-redirector
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('PRODUCTION_IMAGE_REDIRECTOR_VERSION', '1.0.0');
define('PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Production_Image_Redirector
{

	/**
	 * Plugin instance
	 */
	private static $instance = null;

	/**
	 * Admin class instance
	 */
	private $admin;

	/**
	 * URL redirector class instance
	 */
	private $url_redirector;

	/**
	 * Get plugin instance
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->init();
	}

	/**
	 * Initialize the plugin
	 */
	private function init()
	{
		// Load required files
		$this->load_dependencies();

		// Initialize components
		$this->init_components();

		// Load text domain
		add_action('init', array($this, 'load_textdomain'));
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies()
	{
		// Include required files
		require_once PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_DIR . 'includes/class-admin.php';
		require_once PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_DIR . 'includes/class-url-redirector.php';
		require_once PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_DIR . 'includes/class-activator.php';
	}

	/**
	 * Initialize plugin components
	 */
	private function init_components()
	{
		// Initialize admin functionality
		$this->admin = new Production_Image_Redirector_Admin();

		// Initialize URL redirection functionality
		$this->url_redirector = new Production_Image_Redirector_URL_Redirector();
	}

	/**
	 * Load plugin text domain
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain(
			'production-image-redirector',
			false,
			dirname(plugin_basename(__FILE__)) . '/languages'
		);
	}
}

/**
 * Initialize the plugin
 */
function production_image_redirector_init()
{
	return Production_Image_Redirector::get_instance();
}

// Start the plugin
production_image_redirector_init();

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('Production_Image_Redirector_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Production_Image_Redirector_Activator', 'deactivate'));

// Register uninstall hook
register_uninstall_hook(__FILE__, array('Production_Image_Redirector_Activator', 'uninstall'));
