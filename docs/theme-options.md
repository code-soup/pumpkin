# ACF Options Manager

Theme options are now managed by a separate composer package.

**[📖 Full Documentation](https://github.com/code-soup/codesoup-acf-options)**

## Quick Setup

**Step 1:** Define options pages in `includes/wp-mods/class-theme-setup.php`
```php
private const ACF_OPTIONS_PAGES = [
    [
        'id'         => 'general',
        'title'      => 'General Settings',
        'capability' => 'manage_options',
    ],
    [
        'id'         => 'footer',
        'title'      => 'Footer Settings',
        'capability' => 'manage_options',
    ]
];
```

**Step 2:** Create ACF Field Group
- Name: "Footer Settings"
- Fields: `footer_text`, `footer_logo`, etc.
- Location: Theme Options - Page = Footer Settings

**Step 3:** Use in templates
```php
<?php
use CodeSoup\ACFOptions\Options;

$footer_text = Options::get('pumpkin_footer', 'footer_text');
?>
```

For complete documentation, examples, and advanced usage, see the [package repository](https://github.com/code-soup/codesoup-acf-options).

