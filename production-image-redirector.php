<?php
/**
 * Plugin Name: Production Image Redirector
 * Plugin URI: https://github.com/KreigD/Production-Image-Redirector
 * Description: Redirects all image URLs on the current site to a production site URL. Useful for local/test environments to use production images without downloading the entire uploads folder.
 * Version: 1.1.0
 * Author: Kreig Durham
 * Author URI: https://kreigd.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: production-image-redirector
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package Production_Image_Redirector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/constants.php';

define( 'PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 *
 * @package Production_Image_Redirector
 * @since 1.0.0
 */
class Production_Image_Redirector {

	/**
	 * Plugin instance.
	 *
	 * @var Production_Image_Redirector|null
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Admin class instance.
	 *
	 * @var Production_Image_Redirector_Admin
	 * @since 1.0.0
	 */
	private $admin;

	/**
	 * URL redirector class instance.
	 *
	 * @var Production_Image_Redirector_URL_Redirector
	 * @since 1.0.0
	 */
	private $url_redirector;

	/**
	 * Get plugin instance.
	 *
	 * @return Production_Image_Redirector
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	private function init() {
		$this->load_dependencies();
		$this->init_components();
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load plugin dependencies.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		require_once PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_DIR . 'includes/class-admin.php';
		require_once PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_DIR . 'includes/class-url-redirector.php';
		require_once PRODUCTION_IMAGE_REDIRECTOR_PLUGIN_DIR . 'includes/class-activator.php';
	}

	/**
	 * Initialize plugin components.
	 *
	 * @since 1.0.0
	 */
	private function init_components() {
		$this->admin          = new Production_Image_Redirector_Admin();
		$this->url_redirector = new Production_Image_Redirector_URL_Redirector();
	}

	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'production-image-redirector',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
}

/**
 * Initialize the plugin.
 *
 * @return Production_Image_Redirector
 * @since 1.0.0
 */
function production_image_redirector_init() {
	return Production_Image_Redirector::get_instance();
}

production_image_redirector_init();

register_activation_hook( __FILE__, array( 'Production_Image_Redirector_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Production_Image_Redirector_Activator', 'deactivate' ) );

if ( is_multisite() ) {
	add_action( 'wpmu_new_blog', array( 'Production_Image_Redirector_Activator', 'activate_single_site' ), 10, 1 );
}
