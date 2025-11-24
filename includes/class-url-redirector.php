<?php

/**
 * URL redirection functionality for Production Image Redirector
 *
 * @package Production_Image_Redirector
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

class Production_Image_Redirector_URL_Redirector
{

	private $option_name = 'production_image_redirector_settings';

	public function __construct()
	{
		add_filter('wp_get_attachment_url', array($this, 'redirect_attachment_url'), 10, 2);
		add_filter('wp_get_attachment_image_src', array($this, 'redirect_attachment_image_src'), 10, 4);
		add_filter('wp_get_attachment_image_attributes', array($this, 'redirect_attachment_image_attributes'), 10, 3);
		add_filter('the_content', array($this, 'redirect_content_images'));
		add_filter('widget_text', array($this, 'redirect_content_images'));
	}

	/**
	 * Redirect attachment URLs
	 */
	public function redirect_attachment_url($url, $attachment_id)
	{
		if (!$this->should_redirect()) {
			return $url;
		}

		return $this->redirect_url($url);
	}

	/**
	 * Redirect attachment image src arrays
	 */
	public function redirect_attachment_image_src($image, $attachment_id, $size, $icon)
	{
		if (!$this->should_redirect() || !is_array($image)) {
			return $image;
		}

		$image[0] = $this->redirect_url($image[0]);
		return $image;
	}

	/**
	 * Redirect attachment image attributes
	 */
	public function redirect_attachment_image_attributes($attr, $attachment, $size)
	{
		if (!$this->should_redirect()) {
			return $attr;
		}

		if (isset($attr['src'])) {
			$attr['src'] = $this->redirect_url($attr['src']);
		}

		if (isset($attr['srcset'])) {
			$attr['srcset'] = $this->redirect_srcset($attr['srcset']);
		}

		return $attr;
	}

	/**
	 * Redirect images in content
	 */
	public function redirect_content_images($content)
	{
		if (!$this->should_redirect()) {
			return $content;
		}

		// Redirect img src attributes
		$content = preg_replace_callback(
			'/<img([^>]+)src=["\']([^"\']+)["\']([^>]*)>/i',
			array($this, 'redirect_img_tag'),
			$content
		);

		// Redirect background images in style attributes
		$content = preg_replace_callback(
			'/style=["\']([^"\']*background-image:\s*url\([^)]+\)[^"\']*)["\']/i',
			array($this, 'redirect_style_background'),
			$content
		);

		return $content;
	}

	/**
	 * Handle img tag redirection
	 */
	private function redirect_img_tag($matches)
	{
		$before_attrs = $matches[1];
		$src = $matches[2];
		$after_attrs = $matches[3];

		$redirected_src = $this->redirect_url($src);

		// Also handle srcset if present
		$before_attrs = preg_replace_callback(
			'/srcset=["\']([^"\']+)["\']/i',
			array($this, 'redirect_srcset_callback'),
			$before_attrs
		);

		return '<img' . $before_attrs . 'src="' . esc_attr($redirected_src) . '"' . $after_attrs . '>';
	}

	/**
	 * Handle srcset redirection in img tags
	 */
	private function redirect_srcset_callback($matches)
	{
		$srcset = $matches[1];
		$redirected_srcset = $this->redirect_srcset($srcset);
		return 'srcset="' . esc_attr($redirected_srcset) . '"';
	}

	/**
	 * Handle background image redirection in style attributes
	 */
	private function redirect_style_background($matches)
	{
		$style = $matches[1];
		$style = preg_replace_callback(
			'/url\(([^)]+)\)/i',
			array($this, 'redirect_style_url'),
			$style
		);
		return 'style="' . esc_attr($style) . '"';
	}

	/**
	 * Handle URL redirection in style attributes
	 */
	private function redirect_style_url($matches)
	{
		$url = $matches[1];
		$redirected_url = $this->redirect_url($url);
		return 'url(' . $redirected_url . ')';
	}

	/**
	 * Redirect srcset URLs
	 */
	private function redirect_srcset($srcset)
	{
		$srcset_parts = explode(',', $srcset);
		$redirected_parts = array();

		foreach ($srcset_parts as $part) {
			$part = trim($part);
			if (preg_match('/^([^\s]+)\s+(.+)$/', $part, $matches)) {
				$url = $matches[1];
				$descriptor = $matches[2];
				$redirected_url = $this->redirect_url($url);
				$redirected_parts[] = $redirected_url . ' ' . $descriptor;
			} else {
				$redirected_url = $this->redirect_url($part);
				$redirected_parts[] = $redirected_url;
			}
		}

		return implode(', ', $redirected_parts);
	}

	/**
	 * Check if URL is in the uploads directory
	 * Optimized to avoid parse_url() when possible
	 */
	private function is_uploads_url($url)
	{
		// Quick check: if URL doesn't contain 'uploads', skip early
		// This catches most non-uploads URLs without parsing
		if (strpos($url, 'uploads') === false) {
			return false;
		}

		// Normalize path separators early
		$normalized_url = str_replace('\\', '/', $url);

		// Fast path: check for standard WordPress uploads directory
		// Most common case - check this first
		if (strpos($normalized_url, '/wp-content/uploads/') !== false) {
			return true;
		}

		// Check for relative URLs that start with wp-content/uploads/
		if (strpos($normalized_url, 'wp-content/uploads/') === 0) {
			return true;
		}

		// Check for custom uploads directory (if WordPress UPLOADS constant is set)
		// This is less common, so we check it last
		if (defined('UPLOADS')) {
			$uploads_path = trim(UPLOADS, '/');
			if (!empty($uploads_path)) {
				// Only parse URL if we need to check custom path
				$parsed_url = parse_url($normalized_url);
				$path = isset($parsed_url['path']) ? $parsed_url['path'] : $normalized_url;

				// For relative URLs, use the full URL string
				if (strpos($normalized_url, 'http') !== 0) {
					$path = $normalized_url;
				}

				if (strpos($path, '/' . $uploads_path . '/') !== false) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Main URL redirection logic
	 */
	private function redirect_url($url)
	{
		$options = get_option($this->option_name);
		$production_url = isset($options['production_url']) ? $options['production_url'] : '';

		if (empty($production_url)) {
			return $url;
		}

		// Only redirect URLs that are in the uploads directory
		if (!$this->is_uploads_url($url)) {
			return $url;
		}

		// Get HTTP authentication credentials if set
		$htpasswd_username = isset($options['htpasswd_username']) ? trim($options['htpasswd_username']) : '';
		$htpasswd_password = isset($options['htpasswd_password']) ? trim($options['htpasswd_password']) : '';

		// Remove trailing slash from production URL
		$production_url = rtrim($production_url, '/');

		// Parse the production URL to add credentials
		$parsed_url = parse_url($production_url);
		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : 'https://';
		$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$path = isset($parsed_url['path']) ? $parsed_url['path'] : '';

		// Build production URL with credentials if provided
		if (!empty($htpasswd_username) && !empty($htpasswd_password)) {
			// URL encode username and password to handle special characters
			$encoded_username = rawurlencode($htpasswd_username);
			$encoded_password = rawurlencode($htpasswd_password);
			$production_url = $scheme . $encoded_username . ':' . $encoded_password . '@' . $host . $port . $path;
		} else {
			$production_url = $scheme . $host . $port . $path;
		}

		// Remove trailing slash after building URL
		$production_url = rtrim($production_url, '/');

		// If it's already a full URL pointing to the production site, return as is
		// Check against base URL without credentials for comparison
		// Also check if URL already contains the host (with or without credentials)
		$base_production_url = $scheme . $host . $port . $path;
		$base_production_url = rtrim($base_production_url, '/');

		// Parse the incoming URL to check if it's already pointing to production
		$url_parsed = parse_url($url);
		$url_host = isset($url_parsed['host']) ? $url_parsed['host'] : '';

		// If the URL host matches the production host, return as is (it's already redirected)
		if ($url_host === $host && !empty($host)) {
			return $url;
		}

		// If it's a relative URL or local URL, redirect to production
		if (strpos($url, 'http') !== 0) {
			// It's a relative URL, prepend production URL
			return $production_url . '/' . ltrim($url, '/');
		}

		// If it's a local URL, replace the domain
		$site_url = get_site_url();
		if (strpos($url, $site_url) === 0) {
			$path = str_replace($site_url, '', $url);
			return $production_url . $path;
		}

		return $url;
	}

	/**
	 * Check if redirection should be enabled
	 */
	private function should_redirect()
	{
		$options = get_option($this->option_name);
		return isset($options['enable_redirect']) && $options['enable_redirect'] && !empty($options['production_url']);
	}
}
