<?php
/**
 * URL redirection functionality for Production Image Redirector.
 *
 * @package Production_Image_Redirector
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters image URLs to point at the configured production site.
 *
 * @since 1.0.0
 */
class Production_Image_Redirector_URL_Redirector {

	/**
	 * Options array cache for the current request.
	 *
	 * @var array|null
	 * @since 1.1.0
	 */
	private $settings = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'wp_get_attachment_url', array( $this, 'redirect_attachment_url' ), 10, 2 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'redirect_attachment_image_src' ), 10, 4 );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'redirect_attachment_image_attributes' ), 10, 3 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'redirect_calculated_srcset' ), 10, 5 );
		add_filter( 'the_content', array( $this, 'redirect_content_images' ) );
		add_filter( 'widget_text', array( $this, 'redirect_content_images' ) );
		add_filter( 'widget_block_content', array( $this, 'redirect_content_images' ), 10, 4 );
	}

	/**
	 * Redirect attachment URLs.
	 *
	 * @param string $url           Attachment URL.
	 * @param int    $attachment_id Attachment ID.
	 * @return string
	 * @since 1.0.0
	 */
	public function redirect_attachment_url( $url, $attachment_id ) {
		unset( $attachment_id );
		if ( ! $this->should_redirect( 'attachment_url' ) ) {
			return $url;
		}
		return $this->redirect_url( $url );
	}

	/**
	 * Redirect attachment image src arrays.
	 *
	 * @param array|false $image         Image data or false.
	 * @param int         $attachment_id Attachment ID.
	 * @param string      $size          Requested size.
	 * @param bool        $icon          Whether the image is an icon.
	 * @return array|false
	 * @since 1.0.0
	 */
	public function redirect_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		unset( $attachment_id, $size, $icon );
		if ( ! $this->should_redirect( 'attachment_image_src' ) || ! is_array( $image ) ) {
			return $image;
		}
		$image[0] = $this->redirect_url( $image[0] );
		return $image;
	}

	/**
	 * Redirect attachment image attributes.
	 *
	 * @param array        $attr        Img attributes.
	 * @param WP_Post      $attachment  Attachment post.
	 * @param string|int[] $size       Size.
	 * @return array
	 * @since 1.0.0
	 */
	public function redirect_attachment_image_attributes( $attr, $attachment, $size ) {
		unset( $attachment, $size );
		if ( ! $this->should_redirect( 'attachment_image_attributes' ) ) {
			return $attr;
		}
		if ( isset( $attr['src'] ) ) {
			$attr['src'] = $this->redirect_url( $attr['src'] );
		}
		if ( isset( $attr['srcset'] ) ) {
			$attr['srcset'] = $this->redirect_srcset( $attr['srcset'] );
		}
		return $attr;
	}

	/**
	 * Redirect URLs inside calculated srcset sources.
	 *
	 * @param array  $sources       One source entry per width.
	 * @param array  $size_array    Width and height.
	 * @param string $image_src     Calculated image src.
	 * @param array  $image_meta    Attachment meta.
	 * @param int    $attachment_id Attachment ID.
	 * @return array
	 * @since 1.1.0
	 */
	public function redirect_calculated_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		unset( $size_array, $image_src, $image_meta, $attachment_id );
		if ( ! $this->should_redirect( 'wp_calculate_image_srcset' ) || ! is_array( $sources ) ) {
			return $sources;
		}
		foreach ( $sources as $width => $source ) {
			if ( is_array( $source ) && isset( $source['url'] ) ) {
				$sources[ $width ]['url'] = $this->redirect_url( $source['url'] );
			}
		}
		return $sources;
	}

	/**
	 * Redirect images in HTML content.
	 *
	 * @param string $content HTML content.
	 * @return string
	 * @since 1.0.0
	 */
	public function redirect_content_images( $content ) {
		if ( ! $this->should_redirect( 'the_content' ) ) {
			return $content;
		}
		$content = preg_replace_callback(
			'/<img([^>]+)src=["\']([^"\']+)["\']([^>]*)>/i',
			array( $this, 'redirect_img_tag' ),
			$content
		);
		$content = preg_replace_callback(
			'/style=["\']([^"\']*background-image:\s*url\([^)]+\)[^"\']*)["\']/i',
			array( $this, 'redirect_style_background' ),
			$content
		);
		return $content;
	}

	/**
	 * Replace img tag src and srcset with production URLs.
	 *
	 * @param array $matches Regex matches.
	 * @return string
	 * @since 1.0.0
	 */
	private function redirect_img_tag( $matches ) {
		$before_attrs   = $matches[1];
		$src            = $matches[2];
		$after_attrs    = $matches[3];
		$redirected_src = $this->redirect_url( $src );
		$before_attrs   = preg_replace_callback(
			'/srcset=["\']([^"\']+)["\']/i',
			array( $this, 'redirect_srcset_callback' ),
			$before_attrs
		);
		$after_attrs    = preg_replace_callback(
			'/srcset=["\']([^"\']+)["\']/i',
			array( $this, 'redirect_srcset_callback' ),
			$after_attrs
		);
		return '<img' . $before_attrs . 'src="' . esc_attr( $redirected_src ) . '"' . $after_attrs . '>';
	}

	/**
	 * Replace a srcset attribute value.
	 *
	 * @param array $matches Regex matches.
	 * @return string
	 * @since 1.0.0
	 */
	private function redirect_srcset_callback( $matches ) {
		$srcset            = $matches[1];
		$redirected_srcset = $this->redirect_srcset( $srcset );
		return 'srcset="' . esc_attr( $redirected_srcset ) . '"';
	}

	/**
	 * Rewrite background-image URLs inside a style attribute.
	 *
	 * @param array $matches Regex matches.
	 * @return string
	 * @since 1.0.0
	 */
	private function redirect_style_background( $matches ) {
		$style = $matches[1];
		$style = preg_replace_callback(
			'/url\(([^)]+)\)/i',
			array( $this, 'redirect_style_url' ),
			$style
		);
		return 'style="' . esc_attr( $style ) . '"';
	}

	/**
	 * Rewrite a single CSS url() value.
	 *
	 * @param array $matches Regex matches.
	 * @return string
	 * @since 1.0.0
	 */
	private function redirect_style_url( $matches ) {
		$raw            = $matches[1];
		$url            = trim( $raw, " \t\n\r\0\x0B\"'" );
		$redirected_url = $this->redirect_url( $url );
		return 'url("' . esc_url( $redirected_url ) . '")';
	}

	/**
	 * Redirect srcset URLs.
	 *
	 * @param string $srcset Raw srcset attribute value.
	 * @return string
	 * @since 1.0.0
	 */
	private function redirect_srcset( $srcset ) {
		$srcset_parts     = explode( ',', $srcset );
		$redirected_parts = array();
		foreach ( $srcset_parts as $part ) {
			$part = trim( $part );
			if ( preg_match( '/^([^\s]+)\s+(.+)$/', $part, $matches ) ) {
				$url                = $matches[1];
				$descriptor         = $matches[2];
				$redirected_url     = $this->redirect_url( $url );
				$redirected_parts[] = $redirected_url . ' ' . $descriptor;
			} else {
				$redirected_parts[] = $this->redirect_url( $part );
			}
		}

		return implode( ', ', $redirected_parts );
	}

	/**
	 * Whether the URL looks like an uploads path.
	 *
	 * @param string $url URL or path.
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_uploads_url( $url ) {
		if ( false === strpos( $url, 'uploads' ) ) {
			return false;
		}
		$normalized_url = str_replace( '\\', '/', $url );
		if ( false !== strpos( $normalized_url, '/wp-content/uploads/' ) ) {
			return true;
		}
		if ( 0 === strpos( $normalized_url, 'wp-content/uploads/' ) ) {
			return true;
		}
		if ( defined( 'UPLOADS' ) ) {
			$uploads_path = trim( UPLOADS, '/' );
			if ( ! empty( $uploads_path ) ) {
				$parsed_url = wp_parse_url( $normalized_url );
				if ( ! is_array( $parsed_url ) ) {
					$parsed_url = array();
				}
				$path = isset( $parsed_url['path'] ) ? $parsed_url['path'] : $normalized_url;
				if ( 0 !== strpos( $normalized_url, 'http' ) ) {
					$path = $normalized_url;
				}
				if ( false !== strpos( $path, '/' . $uploads_path . '/' ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Core redirect: production host + optional basic-auth userinfo for uploads URLs.
	 *
	 * @param string $url Original URL.
	 * @return string
	 * @since 1.0.0
	 */
	private function redirect_url( $url ) {
		$settings       = $this->get_settings();
		$original       = $url;
		$new_url        = $url;
		$production_url = isset( $settings['production_url'] ) ? $settings['production_url'] : '';
		if ( empty( $production_url ) || ! $this->is_uploads_url( $url ) ) {
			return $this->filter_redirect_url( $new_url, $original, $settings );
		}

		$htpasswd_username = isset( $settings['htpasswd_username'] ) ? trim( $settings['htpasswd_username'] ) : '';
		$htpasswd_password = isset( $settings['htpasswd_password'] ) ? trim( $settings['htpasswd_password'] ) : '';
		$production_url    = rtrim( $production_url, '/' );
		$parsed_url        = wp_parse_url( $production_url );
		if ( ! is_array( $parsed_url ) ) {
			$parsed_url = array();
		}
		$scheme      = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : 'https://';
		$host        = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$port        = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
		$path_prefix = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';

		if ( ! empty( $htpasswd_username ) && ! empty( $htpasswd_password ) ) {
			$encoded_username = rawurlencode( $htpasswd_username );
			$encoded_password = rawurlencode( $htpasswd_password );
			$production_base  = $scheme . $encoded_username . ':' . $encoded_password . '@' . $host . $port . $path_prefix;
		} else {
			$production_base = $scheme . $host . $port . $path_prefix;
		}
		$production_base = rtrim( $production_base, '/' );

		$url_parsed = wp_parse_url( $url );
		if ( ! is_array( $url_parsed ) ) {
			$url_parsed = array();
		}
		$url_host = isset( $url_parsed['host'] ) ? $url_parsed['host'] : '';

		if ( $url_host === $host && ! empty( $host ) ) {
			return $this->filter_redirect_url( $original, $original, $settings );
		}

		if ( 0 !== strpos( $url, 'http' ) ) {
			$new_url = $production_base . '/' . ltrim( $url, '/' );
			return $this->filter_redirect_url( $new_url, $original, $settings );
		}

		$site_url = get_site_url();
		if ( 0 === strpos( $url, $site_url ) ) {
			$local_path = str_replace( $site_url, '', $url );
			$new_url    = $production_base . $local_path;
			return $this->filter_redirect_url( $new_url, $original, $settings );
		}

		return $this->filter_redirect_url( $new_url, $original, $settings );
	}

	/**
	 * Apply the redirect URL filter.
	 *
	 * @param string $new_url   URL produced by redirect logic.
	 * @param string $original  Original URL before redirect.
	 * @param array  $settings  Plugin option array.
	 * @return string
	 * @since 1.1.0
	 */
	private function filter_redirect_url( $new_url, $original, $settings ) {
		/**
		 * Filters the image URL after redirect logic.
		 *
		 * @since 1.1.0
		 * @param string $new_url   URL to use (possibly unchanged).
		 * @param string $original  Original URL before the plugin ran.
		 * @param array  $settings  Plugin settings option.
		 */
		return apply_filters( 'production_image_redirector_redirect_url', $new_url, $original, $settings );
	}

	/**
	 * Cached plugin settings for the request.
	 *
	 * @return array
	 * @since 1.1.0
	 */
	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = get_option( PRODUCTION_IMAGE_REDIRECTOR_OPTION_NAME, array() );
		}
		return $this->settings;
	}

	/**
	 * Whether redirection is enabled and allowed for this context.
	 *
	 * @param string $context Context key e.g. attachment_url, the_content.
	 * @return bool
	 * @since 1.1.0
	 */
	private function should_redirect( $context = 'default' ) {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['enable_redirect'] ) && $settings['enable_redirect'] && ! empty( $settings['production_url'] );
		if ( ! $enabled ) {
			return false;
		}
		/**
		 * Whether to redirect image URLs for the given context.
		 *
		 * @since 1.1.0
		 * @param bool   $allow     Whether redirection should proceed before this filter.
		 * @param string $context   Entry point identifier (e.g. attachment_url, the_content).
		 * @param array  $settings  Plugin option array.
		 */
		return (bool) apply_filters( 'production_image_redirector_should_redirect', true, $context, $settings );
	}
}
