<?php
/**
 * Script Loader Trait
 *
 * @package Pumpkin
 */

declare(strict_types=1);

namespace CodeSoup\Pumpkin\Utils;

// Don't allow direct access to file
defined('ABSPATH') || die;

/**
 * Trait ScriptLoader
 * 
 * Handles loading of CSS and JS assets based on current page template
 */
trait ScriptLoader
{
    /**
     * Manifest data
     *
     * @var array<string, string>
     */
    private static array $manifest = [];

    /**
     * Get manifest data
     *
     * @return array<string, string>
     */
    private static function get_manifest(): array
    {
        if (empty(self::$manifest)) {
            $manifest_path = get_template_directory() . '/dist/manifest.json';
            
            if (!file_exists($manifest_path)) {
                return [];
            }

            $manifest_content = file_get_contents($manifest_path);
            if ($manifest_content === false) {
                return [];
            }

            $manifest_data = json_decode($manifest_content, true);
            if (!is_array($manifest_data)) {
                return [];
            }

            self::$manifest = $manifest_data;
        }

        return self::$manifest;
    }

    /**
     * Get a specific asset from the manifest
     *
     * @param string $asset_name The name of the asset to retrieve
     * @return string|null The asset path if found, null otherwise
     */
    public function get_asset(string $asset_name): ?string
    {
        $manifest = self::get_manifest();

        if ( empty( $manifest[$asset_name] ) )
            return '';

        return get_stylesheet_directory_uri() . '/dist/' . $manifest[$asset_name];
    }

    /**
     * Get template-specific assets
     *
     * @param string $template_name Template name without extension.
     * @return array{js: array<string>, css: array<string>, async_js: array<string>, async_css: array<string>}
     */
    private static function get_template_assets(string $template_name): array
    {
        $manifest = self::get_manifest();
        
        $assets = [
            'js'      => [],
            'css'     => [],
            'async_js' => [],
            'async_css' => [],
        ];

        // Look for main template assets
        $js_file  = $template_name . '.js';
        $css_file = $template_name . '.css';
        
        if (isset($manifest[$js_file])) {
            $assets['js'][] = $manifest[$js_file];
        }
        
        if (isset($manifest[$css_file])) {
            $assets['css'][] = $manifest[$css_file];
        }

        // Look for async template assets
        $async_js_file  = $template_name . '-async.js';
        $async_css_file = $template_name . '-async.css';
        
        if (isset($manifest[$async_js_file])) {
            $assets['async_js'][] = $manifest[$async_js_file];
        }
        
        if (isset($manifest[$async_css_file])) {
            $assets['async_css'][] = $manifest[$async_css_file];
        }

        return $assets;
    }

    /**
     * Get current template name
     *
     * @return string
     */
    private static function get_current_template_name(): string
    {
        $template = get_page_template_slug();
        
        if (empty($template)) {
            return 'index';
        }

        // Remove .php extension and any directory path
        $template = basename($template, '.php');
        
        // Convert directory separators to hyphens
        return str_replace('/', '-', $template);
    }

    /**
     * Get assets for current template
     *
     * @return array{js: array<string>, css: array<string>, async_js: array<string>, async_css: array<string>}
     */
    private static function get_current_template_assets(): array
    {
        $template_name = self::get_current_template_name();
        return self::get_template_assets($template_name);
    }

    /**
     * Get SVG icon from sprite
     *
     * @param string $icon_name The name of the icon without prefix
     * @param array<string, mixed> $args Additional arguments for the SVG
     * @return string|null The SVG HTML or null if not found
     */
    public static function get_icon(string $icon_name, array $args = []): ?string 
    {
        $manifest = self::get_manifest();
        $sprite_key = 'images/spritemap.svg';
        
        if (!isset($manifest[$sprite_key])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SVG sprite not found in manifest: {$sprite_key}");
            }
            return null;
        }

        $params = wp_parse_args($args, [
            'class'       => '',
            'aria-label' => '',
            'role'       => 'img',
        ]);

        $sprite_url = get_template_directory_uri() . '/dist/' . $manifest[$sprite_key];
        $icon_id = 'icon-' . $icon_name;
        
        $class = 'icon ' . esc_attr($params['class']);
        $aria_label = $params['aria-label'] ? sprintf(' aria-label="%s"', esc_attr($params['aria-label'])) : '';
        $role = $params['role'] ? sprintf(' role="%s"', esc_attr($params['role'])) : '';

        return sprintf(
            '<svg class="%s"%s%s><use xlink:href="%s#%s"></use></svg>',
            $class,
            $aria_label,
            $role,
            esc_url($sprite_url),
            esc_attr($icon_id)
        );
    }
} 