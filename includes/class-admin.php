<?php

/**
 * Admin functionality for Production Image Redirector
 *
 * @package Production_Image_Redirector
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

class Production_Image_Redirector_Admin
{

	private $option_name = 'production_image_redirector_settings';

	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'register_settings'));
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu()
	{
		add_options_page(
			__('Production Image Redirector', 'production-image-redirector'),
			__('Image Redirector', 'production-image-redirector'),
			'manage_options',
			'production-image-redirector',
			array($this, 'admin_page')
		);
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings()
	{
		register_setting(
			$this->option_name,
			$this->option_name,
			array($this, 'sanitize_settings')
		);

		add_settings_section(
			'production_image_redirector_section',
			__('Production Image Settings', 'production-image-redirector'),
			array($this, 'settings_section_callback'),
			'production-image-redirector'
		);

		add_settings_field(
			'production_url',
			__('Production Site URL', 'production-image-redirector'),
			array($this, 'production_url_callback'),
			'production-image-redirector',
			'production_image_redirector_section'
		);

		add_settings_field(
			'enable_redirect',
			__('Enable Image Redirect', 'production-image-redirector'),
			array($this, 'enable_redirect_callback'),
			'production-image-redirector',
			'production_image_redirector_section'
		);
	}

	/**
	 * Sanitize settings input
	 */
	public function sanitize_settings($input)
	{
		$sanitized = array();

		if (isset($input['production_url'])) {
			$sanitized['production_url'] = esc_url_raw(trim($input['production_url']));
		}

		$sanitized['enable_redirect'] = isset($input['enable_redirect']) ? 1 : 0;

		return $sanitized;
	}

	/**
	 * Settings section description
	 */
	public function settings_section_callback()
	{
		echo '<p>' . __('Configure the production site URL where images should be redirected from. This is useful for local/test environments.', 'production-image-redirector') . '</p>';
	}

	/**
	 * Production URL field callback
	 */
	public function production_url_callback()
	{
		$options = get_option($this->option_name);
		$production_url = isset($options['production_url']) ? $options['production_url'] : '';
		echo '<input type="url" id="production_url" name="' . $this->option_name . '[production_url]" value="' . esc_attr($production_url) . '" class="regular-text" placeholder="https://example.com" />';
		echo '<p class="description">' . __('Enter the full URL of your production site (e.g., https://yoursite.com)', 'production-image-redirector') . '</p>';
	}

	/**
	 * Enable redirect checkbox callback
	 */
	public function enable_redirect_callback()
	{
		$options = get_option($this->option_name);
		$enable_redirect = isset($options['enable_redirect']) ? $options['enable_redirect'] : 0;
		echo '<input type="checkbox" id="enable_redirect" name="' . $this->option_name . '[enable_redirect]" value="1" ' . checked(1, $enable_redirect, false) . ' />';
		echo '<label for="enable_redirect">' . __('Enable image URL redirection to production site', 'production-image-redirector') . '</label>';
	}

	/**
	 * Admin page HTML
	 */
	public function admin_page()
	{
?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields($this->option_name);
				do_settings_sections('production-image-redirector');
				submit_button();
				?>
			</form>

			<div class="card" style="max-width: 600px; margin-top: 20px;">
				<h2><?php _e('How it works', 'production-image-redirector'); ?></h2>
				<p><?php _e('This plugin redirects all image URLs on your current site to point to your production site. This is useful when you have a local or test environment and want to use the production images without downloading the entire uploads folder.', 'production-image-redirector'); ?></p>

				<h3><?php _e('Example:', 'production-image-redirector'); ?></h3>
				<p><?php _e('If your production URL is set to "https://yoursite.com" and you have an image at "/wp-content/uploads/2024/01/image.jpg", it will be redirected to "https://yoursite.com/wp-content/uploads/2024/01/image.jpg"', 'production-image-redirector'); ?></p>

				<h3><?php _e('Supported image types:', 'production-image-redirector'); ?></h3>
				<ul>
					<li><?php _e('WordPress attachment URLs', 'production-image-redirector'); ?></li>
					<li><?php _e('Images in post content', 'production-image-redirector'); ?></li>
					<li><?php _e('Widget images', 'production-image-redirector'); ?></li>
					<li><?php _e('Theme and plugin images', 'production-image-redirector'); ?></li>
				</ul>
			</div>
		</div>
<?php
	}
}
