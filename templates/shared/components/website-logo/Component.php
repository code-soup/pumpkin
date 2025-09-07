<?php

declare(strict_types=1);

namespace CodeSoup\Pumpkin\Components\WebsiteLogo;

use CodeSoup\Pumpkin\Core\Component as BaseComponent;

/**
 * Website Logo Component
 */
class Component extends BaseComponent
{
    /**
     * Component name
     *
     * @var string
     */
    protected string $name = 'website-logo';

    /**
     * Get default arguments for the component
     *
     * @return array
     */
    public function get_default_args(): array
    {
        return [
            'css_class' => 'website-logo',
            'atts'      => []
        ];
    }

    /**
     * Prepare component data
     *
     * @return array
     */
    protected function prepare_data(): array
    {
        return [
            'has_custom_logo' => has_custom_logo(),
            'home_url'        => esc_url(home_url('/')),
            'site_name'       => get_bloginfo('name')
        ];
    }
} 