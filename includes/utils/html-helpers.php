<?php declare( strict_types=1 );

namespace CodeSoup\Pumpkin\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Conditionally wrap content in an HTML tag
 *
 * @param string $tag        The HTML tag name (e.g., 'div', 'span', 'h1')
 * @param mixed  $content    The content to wrap
 * @param array  $attributes Optional. HTML attributes as key-value pairs
 * @param bool   $escape     Optional. Whether to escape the content. Default true.
 *
 * @return string The wrapped content or empty string if no content
 */
function wrap( string $tag, $content, array $attributes = [], bool $escape = false ): string {
	// Check if content exists and is not empty
	if ( empty( $content ) ) {
		return '';
	}

	// Sanitize tag name
	$tag = \sanitize_key( $tag );

	// Escape content if needed
	$content = $escape ? \esc_html( $content ) : $content;

	// Build attributes string
	$attr_string = '';
	if ( ! empty( $attributes ) ) {
		$attr_parts = [];
		foreach ( $attributes as $key => $value ) {
			$key          = \sanitize_key( $key );
			$value        = \esc_attr( $value );
			$attr_parts[] = \sprintf( '%s="%s"', $key, $value );
		}
		$attr_string = ' ' . \implode( ' ', $attr_parts );
	}

	return \sprintf( '<%s%s>%s</%s>', $tag, $attr_string, $content, $tag );
}

/**
 * Conditionally wrap content in a div with optional classes
 *
 * @param mixed  $content The content to wrap
 * @param string $class   Optional. CSS class(es)
 * @param array  $attributes Optional. Additional HTML attributes
 *
 * @return string The wrapped content or empty string if no content
 */
function div( $content, string $class = '', array $attributes = [] ): string {
	if ( ! empty( $class ) ) {
		$attributes['class'] = $class;
	}

	return wrap( 'div', $content, $attributes );
}

/**
 * Conditionally wrap content in a span with optional classes
 *
 * @param mixed  $content The content to wrap
 * @param string $class   Optional. CSS class(es)
 * @param array  $attributes Optional. Additional HTML attributes
 *
 * @return string The wrapped content or empty string if no content
 */
function span( $content, string $class = '', array $attributes = [] ): string {
	if ( ! empty( $class ) ) {
		$attributes['class'] = $class;
	}

	return wrap( 'span', $content, $attributes );
}

/**
 * Conditionally wrap content in heading tag
 *
 * @param mixed  $content The content to wrap
 * @param int    $level   Heading level (1-6)
 * @param string $class   Optional. CSS class(es)
 * @param array  $attributes Optional. Additional HTML attributes
 *
 * @return string The wrapped content or empty string if no content
 */
function h( $content, int $level = 2, string $class = '', array $attributes = [] ): string {
	$level = \max( 1, \min( 6, $level ) ); // Ensure level is between 1-6
	$tag   = 'h' . $level;

	if ( ! empty( $class ) ) {
		$attributes['class'] = $class;
	}

	return wrap( $tag, $content, $attributes );
}

/**
 * Conditionally create an anchor/link tag
 *
 * @param array  $link_data Array with 'title', 'url', and optionally 'target'
 * @param string $class     Optional. CSS class(es)
 * @param string $id        Optional. HTML id attribute
 * @param array  $attributes Optional. Additional HTML attributes
 *
 * @return string The link HTML or empty string if no title/url
 */
function link( $link_data, string $class = '', string $id = '', array $attributes = [] ): string {
	// Check if we have required data
	if ( empty( $link_data['title'] ) || empty( $link_data['url'] ) ) {
		return '';
	}

	// Build attributes
	$attributes['href'] = \esc_url( $link_data['url'] );

	if ( ! empty( $link_data['target'] ) ) {
		$attributes['target'] = \esc_attr( $link_data['target'] );

		// Add rel="noopener" for security when target="_blank"
		if ( $link_data['target'] === '_blank' ) {
			$attributes['rel'] = 'noopener';
		}
	}

	if ( ! empty( $class ) ) {
		$attributes['class'] = $class;
	}

	if ( ! empty( $id ) ) {
		$attributes['id'] = $id;
	}

	return wrap( 'a', $link_data['title'], $attributes );
}