# Reusable Static Components

Code-based UI components that don't need WordPress admin editing.

## When to Use

**Good for:**
- Website logo
- Breadcrumbs
- Social media icons
- Search forms

**Not for:**
- Content editors need to change
- Page-specific content

## Structure

```
/templates/shared/components/website-logo/
├── Component.php       # Component class
├── templates/
│   └── default.php     # Template file
└── assets/
    └── logo.svg
```

## Creating a Component

**Component.php:**
```php
<?php
namespace CodeSoup\Pumpkin\Components\WebsiteLogo;

use CodeSoup\Pumpkin\Core\Component as BaseComponent;

class Component extends BaseComponent {
    public static function render(array $args = []): string {
        $template = self::get_template_path('default');
        ob_start();
        include $template;
        return ob_get_clean();
    }

    public static function get_logo_url(): string {
        return self::get_asset_url('logo.svg');
    }
}
```

**templates/default.php:**
```php
<?php
use CodeSoup\Pumpkin\Components\WebsiteLogo\Component;

$logo_url = Component::get_logo_url();
?>

<a href="<?php echo esc_url(home_url('/')); ?>">
    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
</a>
```

**Use in templates:**
```php
<?php
use CodeSoup\Pumpkin\Components\WebsiteLogo\Component as Logo;

echo Logo::render();
?>
```



