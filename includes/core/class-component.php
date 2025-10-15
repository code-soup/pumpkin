<?php

namespace CodeSoup\Pumpkin\Core;

use CodeSoup\Pumpkin\Utils\TemplateUtilities;
use CodeSoup\Pumpkin\Utils\AssetLoader;

/**
 * HP Component Abstract Class
 *
 * Base class that all components should extend.
 */

// Don't allow direct access to file
defined("ABSPATH") || die();

abstract class Component
{
    use TemplateUtilities;
    use AssetLoader;

    /**
     * Component configuration arguments
     *
     * @var array
     */
    protected array $args = [
        "template" => "default", // Add default template to args
    ];

    /**
     * Component data (content or key-value pairs)
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Component name
     *
     * @var string
     */
    protected string $name = "";

    /**
     * Component template path
     *
     * @var string
     */
    protected string $template_path = "";

    /**
     * Constructor
     *
     * @param array $args Component configuration arguments
     * @param array $data Component data
     */
    public function __construct(array $data = [], array $args = [])
    {
        $this->set_args($args);
        $this->set_data($data);
        $this->set_template_path();
    }

    /**
     * Set component configuration arguments
     *
     * @param array $args Arguments to set
     * @return self For method chaining
     */
    public function set_args(array $args = []): self
    {
        $defaults = array_merge(
            ["template" => "default"],
            $this->get_default_args(),
        );

        $this->args = wp_parse_args($args, $defaults);
        return $this;
    }

    /**
     * Set component data
     *
     * @param array $data Data to set
     * @return self For method chaining
     */
    public function set_data(array $data = []): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set component data
     *
     * @param array $data Data to set
     * @return self For method chaining
     */
    public function get_data(): array
    {
        return $this->data;
    }

    /**
     * Set template to use
     *
     * @param string $template Template name
     * @return self For method chaining
     */
    public function set_template(string $template): self
    {
        $this->args["template"] = $template;
        return $this;
    }

    /**
     * Set template path for the component
     *
     * @return void
     */
    protected function set_template_path(): void
    {
        if (empty($this->name)) {
            return;
        }

        $relative_path = $this->join_paths(
            "templates",
            "shared",
            "components",
            $this->name
        );

        $this->template_path = $this->join_paths(
            $this->get_theme_directory(),
            $relative_path
        );
    }

    /**
     * Get template path for the component
     *
     * @return string
     */
    protected function get_template_path(): string
    {
        return $this->template_path;
    }

    /**
     * Get template URI for component
     * 
     * @return string
     */
    protected function get_template_uri(): string
    {
        return $this->join_paths(
            untrailingslashit(get_stylesheet_directory_uri()),
            'templates',
            'shared',
            'components',
            $this->name
        );
    }

    /**
     * Get default attributes
     *
     * @return array Default attributes
     */
    abstract public function get_default_args(): array;

    /**
     * Prepare template arguments
     *
     * @return array Arguments to pass to template
     */
    protected function get_component_args(): array
    {
        return [
            "args" => $this->args,
            "data" => $this->data,
            "component" => $this,
        ];
    }

    /**
     * Render the component
     *
     * @return string HTML output
     */
    public function render_template(): string
    {
        $args = $this->get_component_args();
        return $this->get_template($this->args["template"], $args);
    }

    /**
     * Get HTML attributes string
     *
     * @param array|null $args Optional args array to use instead of component args
     * @return string Formatted HTML attributes
     */
    public function get_attributes_string(?array $args = null): string
    {
        $attrs = is_null($args) ? $this->args : $args;
        $attributes = [];
        $css_class = $this->get_class_name_string();

        if (!empty($attrs["atts"])) {
            foreach ($attrs["atts"] as $key => $value) {
                $key = esc_attr($key);

                if (is_bool($value) && $value) {
                    $attributes[] = $key;
                } elseif (!is_bool($value) && !is_null($value)) {
                    $attributes[] = sprintf('%s="%s"', $key, esc_attr($value));
                }
            }
        }

        if (!empty($css_class)) {
            $attributes[] = sprintf('class="%s"', $css_class);
        }

        return $attributes ? " " . implode(" ", $attributes) : "";
    }

    protected function get_class_name_string(): string
    {
        $classes = [];

        // Append CSS class to component
        if (!empty($this->args["css_class"])) {
            $classes[] = esc_attr($attrs["css_class"]);
        }

        if (!empty($this->name)) {
            $classes[] = sprintf("component-%s", strtolower($this->name));
        }

        return trim(implode(" ", $classes));
    }

    /**
     * Include a template file
     *
     * @param string $template Template name
     * @param array $args Template arguments
     * @return string Rendered template
     */
    protected function get_template(string $template, array $args = []): ?string
    {
        // Extract component args and data for template use
        $template_args = [
            "args" => $this->args,
            "data" => $this->data,
            "component" => $this,
        ];

        // Start output buffering
        ob_start();

        // Build template path using normalized path joining
        $template_file = $this->join_paths(
            $this->get_template_path(),
            "templates",
            "$template.php"
        );

        // Check if template exists before including
        if ($this->template_exists($template_file)) {
            extract($template_args);
            include $template_file;
        } else {
            if (defined("WP_DEBUG") && WP_DEBUG) {
                error_log(sprintf("Template not found: %s", $template_file));
            }
        }

        // Get the buffered content
        return ob_get_clean();
    }

    /**
     * Static render method for simplified component usage
     *
     * @param array $data Component data
     * @param array $args Component configuration arguments
     * @return string Rendered HTML
     */
    public static function render(array $data = [], array $args = []): string
    {
        // Get the calling class name
        $class = get_called_class();

        if (!$class || $class === self::class) {
            if (defined("WP_DEBUG") && WP_DEBUG) {
                error_log("Cannot render abstract component class directly");
            }
            return "";
        }

        $component = new $class($data, $args);
        return $component->render_template();
    }

    /**
     * Resolve component class name from component name
     *
     * @param string $component_name Component name
     * @return string|false Full class name or false if not found
     */
    protected static function resolve_component_class(
        string $component_name,
    ): string|false {
        $class_name = ucwords($component_name) . "_Component";

        error_log($class_name);

        if (class_exists($class_name)) {
            return $class_name;
        }

        return false;
    }

    /**
     * Get asset from current template
     * 
     * @param string $filename Asset filename
     * @param array<string, mixed> $args Asset arguments
     * @return string|null Asset HTML or null if file doesn't exist
     */
    public function get_asset(string $filename, array $args = []): ?string
    {
        return $this->get_template_asset($filename, $args);
    }

    /**
     * Get asset URI
     * 
     * @param string $filename Asset filename
     * @return string|null Asset URI or null if file doesn't exist
     */
    public function get_asset_uri(string $filename): ?string
    {
        return $this->get_template_asset_uri($filename);
    }

    /**
	 * Register custom autoloader for components
	 *
	 * @return void
	 */
	public static function register_autoloader(): void {
		spl_autoload_register( function ( $class ) {
			// Only handle our namespace
			if ( strpos( $class, 'CodeSoup\\Pumpkin\\Components\\' ) !== 0 ) {
				return;
			}

			// Convert namespace to file path
			$file = str_replace(
				[ 'CodeSoup\\Pumpkin\\Components\\', '\\' ],
				[ 'templates/shared/components/', '/' ],
				$class
			);

			// Convert PascalCase to kebab-case for all parts
			$parts = explode( '/', $file );
			$parts = array_map( function ( $part ) {
				return strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', $part ) );
			}, $parts );

			// Remove the last part (Component) and add class-component.php
			array_pop( $parts );
			$parts[] = 'class-component.php';
			$file    = implode( '/', $parts );

			// Load the file if it exists
			if ( file_exists( __DIR__ . '/../../' . $file ) ) {
				require_once __DIR__ . '/../../' . $file;
			}
		} );
	}
}
