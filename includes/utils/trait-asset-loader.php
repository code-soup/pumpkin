<?php
/**
 * Asset Loader Trait
 *
 * @package Pumpkin
 */

declare(strict_types=1);

namespace CodeSoup\Pumpkin\Utils;

// Don't allow direct access to file
defined('ABSPATH') || die;

/**
 * Trait AssetLoader
 * 
 * Handles loading of template-specific assets
 */
trait AssetLoader
{
    /**
     * Supported image types
     */
    private const SUPPORTED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Assets path constant
     */
    protected const ASSETS_PATH = 'assets';

    /**
     * Get template asset
     * 
     * @param string $filename Asset filename
     * @param array<string, mixed> $args Asset arguments
     * @return string|null Asset HTML or null if file doesn't exist
     */
    public function get_template_asset(string $filename, array $args = []): ?string
    {
        if (!method_exists($this, 'get_template_path')) {
            return null;
        }

        $filepath = $this->join_paths(
            $this->get_template_path(),
            self::ASSETS_PATH,
            $filename
        );

        if (!$this->template_exists($filepath)) {
            return null;
        }

        $ext    = pathinfo($filepath, PATHINFO_EXTENSION);
        $params = wp_parse_args($args, [
            'alt'           => basename($filename),
            'css_class'     => 'inline-image',
            'wrap'          => '',
            'load'          => 'lazy',
            'fetchpriority' => 'auto',
        ]);

        return match($ext) {
            'svg' => $this->get_svg_content($filepath),
            'jpg', 'jpeg', 'png', 'gif', 'webp' => $this->get_image_html($filepath, $filename, $params),
            default => null,
        };
    }

    /**
     * Get template asset URI
     * 
     * @param string $filename Asset filename
     * @return string|null Asset URI or null if file doesn't exist
     */
    public function get_template_asset_uri(string $filename): ?string
    {
        if (!method_exists($this, 'get_template_path') || !method_exists($this, 'get_template_uri')) {
            return null;
        }

        $filepath = $this->join_paths(
            $this->get_template_path(),
            self::ASSETS_PATH,
            $filename
        );

        if (!$this->template_exists($filepath)) {
            return null;
        }

        return sprintf(
            '%s/%s/%s',
            $this->get_template_uri(),
            self::ASSETS_PATH,
            $filename
        );
    }

    /**
     * Get SVG content
     * 
     * @param string $filepath
     * @return string|null
     */
    private function get_svg_content(string $filepath): ?string
    {
        $content = $this->get_file_contents($filepath);

        if ($content === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Failed to read SVG file: {$filepath}");
            }
            return null;
        }

        return $content;
    }

    /**
     * Get image HTML
     * 
     * @param string $filepath File path
     * @param string $filename File name
     * @param array<string, mixed> $params Image parameters
     * @return string|null
     */
    private function get_image_html(string $filepath, string $filename, array $params): ?string
    {
        $sizes = $this->get_image_dimensions($filepath);
        if (!$sizes) {
            return null;
        }

        $fileuri = sprintf(
            '%s/%s/%s',
            $this->get_template_uri(),
            self::ASSETS_PATH,
            $filename
        );

        $output = sprintf(
            '<img src="%s" alt="%s" %s loading="%s" class="%s" fetchpriority="%s" />',
            esc_url($fileuri),
            esc_attr($this->normalize_alt_tag($params['alt'])),
            $sizes[3],
            esc_attr($params['load']),
            esc_attr($params['css_class']),
            esc_attr($params['fetchpriority'])
        );

        if ($params['wrap']) {
            return sprintf($params['wrap'], $output);
        }

        return $output;
    }

    /**
     * Normalize alt tag text
     * 
     * @param string $slug Text to normalize
     * @return string
     */
    private function normalize_alt_tag(string $slug): string
    {
        $title = sanitize_title($slug);
        $title = str_replace(['-', '_'], ' ', $title);
        return esc_attr(ucwords($title));
    }
} 