# Custom Template Loader

Folder-based template system with automatic discovery and hierarchical fallbacks.

## Template Hierarchy

**Main Templates:**
1. `/templates/post-type/{post-type}/{template-name}/index.php`
2. `/templates/post-type/{post-type}/index.php`
3. `/templates/shared/index.php`

**Template Parts (header, footer, etc.):**
1. `/templates/post-type/{post-type}/{template-name}/footer.php`
2. `/templates/post-type/{post-type}/footer.php`
3. `/templates/shared/parts/footer.php`

## Creating a Template

**Step 1:** Create folder
```
/templates/post-type/page/portfolio/
```

**Step 2:** Add `index.php`
```php
<?php
use CodeSoup\Pumpkin\ACF\Sections;

$sections = new Sections(get_the_ID());
$sections->render();
?>
```

**Step 3:** Use in WordPress
- Edit page → Select "Portfolio" from Template dropdown

Template automatically appears in admin when folder is created.

## Folder Structure

```
/templates/post-type/page/portfolio/
├── index.php       # Main template
├── header.php      # Custom header (optional)
├── footer.php      # Custom footer (optional)
├── scss/index.scss # Template styles
└── js/index.js     # Template scripts
```

## Benefits

- **Auto-discovery** - Create folder, get template
- **Hierarchical** - Override only what's different
- **Self-contained** - All files in one place

