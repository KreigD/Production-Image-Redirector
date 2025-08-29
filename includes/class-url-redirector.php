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
	 * Main URL redirection logic
	 */
	private function redirect_url($url)
	{
		$options = get_option($this->option_name);
		$production_url = isset($options['production_url']) ? $options['production_url'] : '';

		if (empty($production_url)) {
			return $url;
		}

		// Remove trailing slash from production URL
		$production_url = rtrim($production_url, '/');

		// If it's already a full URL pointing to the production site, return as is
		if (strpos($url, $production_url) === 0) {
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
