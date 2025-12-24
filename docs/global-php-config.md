# Global PHP Config

Hardcoded configuration for settings that don't need WordPress admin editing.

## File Hierarchy

1. `/page-config.php` (Global)
2. `/templates/post-type/{post-type}/page-config.php` (Post-type)
3. `/templates/post-type/{post-type}/{template}/page-config.php` (Template)

Later files override earlier ones.

## Usage

**Create config file** (`/page-config.php`):
```php
<?php
return [
    'features' => [
        'enable_comments' => false,
        'enable_search' => true,
    ],
    'api' => [
        'google_maps_key' => 'YOUR_KEY',
    ],
];
```

**Access in templates:**
```php
<?php
use CodeSoup\Pumpkin\Core\PageConfig;

$enable_search = PageConfig::get('features.enable_search');
$api_key = PageConfig::get('api.google_maps_key');
?>
```

## When to Use

**Use Config for:**
- API keys
- Feature flags
- Environment settings

**Use ACF Options for:**
- Footer text
- Logo images
- Contact info

