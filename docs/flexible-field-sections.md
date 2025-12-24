# ACF Flexible Field Sections

Everything is built around one ACF Flexible Field called "Sections".

## How It Works

1. **ACF Flexible Field "Sections"** - Central hub with all section layouts
2. **Field Groups** - Create one per section, add to "Sections" flexible field
3. **Section Folder** - Each section gets a folder: `/templates/shared/sections/{section-slug}/`
4. **Sections Class** - Loads data, templates, and auto-injects CSS/JS
5. **Render** - `$sections = new Sections(get_the_ID()); $sections->render();`

## Folder Structure

```
/templates/shared/sections/page-hero/
├── index.php       # Template (receives $data from ACF)
├── scss/index.scss # Auto-loaded styles
├── js/index.js     # Auto-loaded scripts
└── assets/         # Images/icons
```

## Example

**Create ACF Field Group:**
- Name: "Section: Page Hero"
- Fields: `heading`, `content`, `image`
- Add to "Sections" flexible field as layout

**Create Section Template** (`/templates/shared/sections/page-hero/index.php`):
```php
<?php
$heading = $data['heading'] ?? '';
$content = $data['content'] ?? '';
?>

<section class="page-hero">
    <h1><?php echo esc_html($heading); ?></h1>
    <div><?php echo wp_kses_post($content); ?></div>
</section>
```

**Use in Page Template:**
```php
<?php
use CodeSoup\Pumpkin\ACF\Sections;

$sections = new Sections(get_the_ID());
$sections->render();
?>
```

## Benefits

- **Self-contained** - Everything in one folder
- **Auto-loading** - CSS/JS loads only when section is used
- **Reusable** - Use across any page/post type
- **No bloat** - Full code control

