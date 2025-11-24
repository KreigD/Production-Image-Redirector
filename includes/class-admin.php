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

		add_settings_field(
			'htpasswd_username',
			__('HTTP Authentication Username', 'production-image-redirector'),
			array($this, 'htpasswd_username_callback'),
			'production-image-redirector',
			'production_image_redirector_section'
		);

		add_settings_field(
			'htpasswd_password',
			__('HTTP Authentication Password', 'production-image-redirector'),
			array($this, 'htpasswd_password_callback'),
			'production-image-redirector',
			'production_image_redirector_section'
		);
	}

	/**
	 * Sanitize settings input
	 */
	public function sanitize_settings($input)
	{
		// Get existing options to preserve values if fields are not submitted
		$existing_options = get_option($this->option_name, array());

		$sanitized = array();

		if (isset($input['production_url'])) {
			$sanitized['production_url'] = esc_url_raw(trim($input['production_url']));
		}

		$sanitized['enable_redirect'] = isset($input['enable_redirect']) ? 1 : 0;

		// Handle htpasswd_username: preserve existing if not submitted
		if (isset($input['htpasswd_username'])) {
			$sanitized['htpasswd_username'] = sanitize_text_field(trim($input['htpasswd_username']));
		} elseif (isset($existing_options['htpasswd_username'])) {
			// Preserve existing username if field is not in POST data
			$sanitized['htpasswd_username'] = $existing_options['htpasswd_username'];
		}

		// Handle htpasswd_password: preserve existing if not submitted or empty
		if (isset($input['htpasswd_password'])) {
			// If password field is not empty, update it; otherwise keep existing password
			if (!empty($input['htpasswd_password'])) {
				$sanitized['htpasswd_password'] = sanitize_text_field($input['htpasswd_password']);
			} elseif (isset($existing_options['htpasswd_password'])) {
				// Keep existing password if field is left blank
				$sanitized['htpasswd_password'] = $existing_options['htpasswd_password'];
			}
		} elseif (isset($existing_options['htpasswd_password'])) {
			// Preserve existing password if field is not in POST data at all
			$sanitized['htpasswd_password'] = $existing_options['htpasswd_password'];
		}

		return $sanitized;
	}

	/**
	 * Settings section description
	 */
	public function settings_section_callback()
	{
		echo '<p>' . __('Configure the production site URL where images should be redirected from. This is useful for local/test environments.', 'production-image-redirector') . '</p>';
		echo '<p>' . __('If your production site is protected by HTTP Basic Authentication (htpasswd), you can enter your credentials below to allow images to load.', 'production-image-redirector') . '</p>';
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
	 * HTTP Authentication username field callback
	 */
	public function htpasswd_username_callback()
	{
		$options = get_option($this->option_name);
		$htpasswd_username = isset($options['htpasswd_username']) ? $options['htpasswd_username'] : '';
		echo '<input type="text" id="htpasswd_username" name="' . $this->option_name . '[htpasswd_username]" value="' . esc_attr($htpasswd_username) . '" class="regular-text" />';
		echo '<p class="description">' . __('Optional: Enter the username for HTTP Basic Authentication (htpasswd) if your production site requires it.', 'production-image-redirector') . '</p>';
	}

	/**
	 * HTTP Authentication password field callback
	 */
	public function htpasswd_password_callback()
	{
		$options = get_option($this->option_name);
		$htpasswd_password = isset($options['htpasswd_password']) ? $options['htpasswd_password'] : '';
		// Show placeholder text if password exists, but don't show actual password
		$placeholder = !empty($htpasswd_password) ? __('(Password is set)', 'production-image-redirector') : '';
		echo '<input type="password" id="htpasswd_password" name="' . $this->option_name . '[htpasswd_password]" value="" class="regular-text" placeholder="' . esc_attr($placeholder) . '" autocomplete="new-password" />';
		if (!empty($htpasswd_password)) {
			echo '<p class="description">' . __('Leave blank to keep current password, or enter a new password to change it.', 'production-image-redirector') . '</p>';
		} else {
			echo '<p class="description">' . __('Optional: Enter the password for HTTP Basic Authentication (htpasswd) if your production site requires it.', 'production-image-redirector') . '</p>';
		}
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
