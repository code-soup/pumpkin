<?php

namespace CodeSoup\Pumpkin\Utils;

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

/**
 * Template Utilities Trait
 * 
 * Provides core utility methods for template handling and path normalization.
 * Focuses on path manipulation and validation utilities.
 */
trait TemplateUtilities {
    /**
     * Theme root directory
     *
     * @var string|null
     */
    private ?string $theme_root = null;

    /**
     * WordPress Filesystem instance
     *
     * @var \WP_Filesystem_Base|null
     */
    private $wp_filesystem = null;

    /**
     * Initialize WordPress Filesystem
     *
     * @return bool Whether filesystem was initialized successfully
     */
    protected function init_filesystem(): bool
    {
        if ($this->wp_filesystem !== null) {
            return true;
        }

        global $wp_filesystem;

        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        if (!WP_Filesystem()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Failed to initialize WordPress Filesystem');
            }
            return false;
        }

        $this->wp_filesystem = $wp_filesystem;
        return true;
    }

    /**
     * Get directory listing using WordPress Filesystem
     *
     * @param string $path Path to list
     * @param bool $include_hidden Whether to include hidden files
     * @param bool $recursive Whether to list recursively
     * @return array|false Directory listing or false on failure
     */
    protected function get_directory_listing(string $path, bool $include_hidden = false, bool $recursive = false)
    {
        if (!$this->init_filesystem()) {
            return false;
        }

        return $this->wp_filesystem->dirlist($path, $include_hidden, $recursive);
    }

    /**
     * Read file contents using WordPress Filesystem
     *
     * @param string $file Path to file
     * @return string|false File contents or false on failure
     */
    protected function get_file_contents(string $file)
    {
        if (!$this->init_filesystem()) {
            return false;
        }

        if (!$this->wp_filesystem->exists($file)) {
            return false;
        }

        return $this->wp_filesystem->get_contents($file);
    }

    /**
     * Write to a file using WordPress Filesystem
     *
     * @param string $file Path to file
     * @param string $contents File contents
     * @return bool Whether the operation succeeded
     */
    protected function put_file_contents(string $file, string $contents): bool
    {
        if (!$this->init_filesystem()) {
            return false;
        }

        return $this->wp_filesystem->put_contents($file, $contents);
    }

    /**
     * Check if a file is readable using WordPress Filesystem
     *
     * @param string $file Path to file
     * @return bool Whether the file is readable
     */
    protected function is_readable(string $file): bool
    {
        if (!$this->init_filesystem()) {
            return is_readable($file);
        }

        return $this->wp_filesystem->is_readable($file);
    }

    /**
     * Get file size using WordPress Filesystem
     *
     * @param string $file Path to file
     * @return int|false File size in bytes or false on failure
     */
    protected function get_file_size(string $file)
    {
        if (!$this->init_filesystem()) {
            return filesize($file);
        }

        return $this->wp_filesystem->size($file);
    }

    /**
     * Normalize a path with proper directory separators
     * Optimized version with input validation and caching
     *
     * @param string|array $path Path or path segments to normalize
     * @return string Normalized path with proper directory separators
     * @throws \InvalidArgumentException If path contains invalid characters
     */
    public function normalize_path($path): string
    {
        static $cache = [];
        
        // If path is array, join it first
        if (is_array($path)) {
            $path = implode(DIRECTORY_SEPARATOR, array_map(
                fn($segment) => $this->validate_path_segment($segment),
                $path
            ));
        } else {
            $this->validate_path_segment($path);
        }
        
        if (isset($cache[$path])) {
            return $cache[$path];
        }
        
        // Optimize path normalization with single regex
        $normalized = preg_replace(
            ['#[/\\\\]+#', '#' . preg_quote(DIRECTORY_SEPARATOR, '#') . '{2,}#'],
            DIRECTORY_SEPARATOR,
            $path
        );
        
        $cache[$path] = $normalized;
        return $normalized;
    }

    /**
     * Validate a path segment for security
     *
     * @param string $segment Path segment to validate
     * @return string The validated segment
     * @throws \InvalidArgumentException If segment contains invalid characters
     */
    private function validate_path_segment(string $segment): string
    {
        if (preg_match('#[<>:"|?*]|\.{2,}#', $segment)) {
            throw new \InvalidArgumentException(
                "Invalid characters in path segment: {$segment}"
            );
        }
        return $segment;
    }

    /**
     * Get the theme directory path
     *
     * @param string|null $theme_directory Optional theme directory override
     * @return string Theme directory path
     */
    protected function get_theme_directory(?string $theme_directory = null): string
    {
        if ($theme_directory !== null) {
            return rtrim($theme_directory, '/\\');
        }
        
        if ($this->theme_root === null) {
            $this->theme_root = rtrim(get_template_directory(), '/\\');
        }
        
        return $this->theme_root;
    }

    /**
     * Build template path relative to theme directory
     *
     * @param string $relative_path Path relative to theme directory (without leading slash)
     * @return string Full template path
     */
    protected function build_template_path( string $relative_path ): string {
        return $this->get_theme_directory() . '/' . ltrim( $relative_path, '/' );
    }

    /**
     * Check if a template file exists at the given path
     *
     * @param string $path Absolute path to check
     * @return bool Whether the template exists
     */
    protected function template_exists(string $path): bool
    {
        // Always try to initialize filesystem
        if (!$this->init_filesystem()) {
            return file_exists($path);
        }
        return $this->wp_filesystem->exists($path);
    }

    /**
     * Check if a directory should be excluded from template scanning
     *
     * @param array $directory Directory info from WP_Filesystem
     * @return bool Whether the directory should be excluded
     */
    protected function is_excluded_directory(array $directory): bool
    {
        // Skip if not a directory
        if ($directory['type'] !== 'd') {
            return true;
        }

        $name = $directory['name'];

        // Skip special directories
        if (in_array($name, ['.', '..'])) {
            return true;
        }

        // Skip system directories
        $excluded_dirs = apply_filters('pumpkin_excluded_template_directories', ['archive', 'default', 'inc', 'includes']);
        
        if (in_array(strtolower($name), $excluded_dirs)) {
            return true;
        }

        // Skip hidden directories
        if (str_starts_with($name, '_')) {
            return true;
        }

        return false;
    }

    /**
     * Join path segments and normalize the resulting path
     *
     * @param string|array $segments Path segments to join (either as array or multiple arguments)
     * @return string Normalized path
     * @throws \InvalidArgumentException If any segment contains invalid characters
     */
    protected function join_paths($segments): string
    {
        // Handle both array input and variadic arguments
        $paths = is_array($segments) ? $segments : func_get_args();

        if (empty($paths)) {
            return '';
        }

        // Filter out empty segments while preserving root slash
        $filtered = array_filter($paths, fn($segment) => $segment !== '');

        // If all segments were empty, return empty string
        if (empty($filtered)) {
            return '';
        }

        // Preserve leading slash if first segment started with one
        $first  = reset($filtered);
        $prefix = str_starts_with($first, '/') ? '/' : '';

        return str_replace('//', '/', ($prefix . $this->normalize_path($filtered)) );
    }

    /**
     * Sanitize template name by removing prefixes, extensions, and applying context-specific formatting
     *
     * @param string $template_name Template name to sanitize
     * @param string $context Context for formatting: 'display' for admin display, 'file' for file operations
     * @return string Sanitized template name (empty if should be excluded)
     */
    protected function sanitize_template_name(string $template_name, string $context = 'file'): string
    {
        // Get just the filename without path
        $template_name = basename($template_name);

        // Remove .php extension
        $template_name = str_replace('.php', '', $template_name);

        // Skip files starting with underscore (private templates)
        if (str_starts_with($template_name, '_')) {
            return '';
        }

        // Apply context-specific formatting
        if ($context === 'display') {
            // Convert to display format (e.g., 'about-us' -> 'About Us')
            return ucwords(str_replace(['-', '_'], ' ', $template_name));
        }

        // Default: return raw template name (for file/folder operations)
        return $template_name;
    }

    /**
     * Check if we're in a development environment
     *
     * @return bool
     */
    protected function is_development_environment(): bool
    {
        return in_array(wp_get_environment_type(), ['development', 'local'], true);
    }
}
