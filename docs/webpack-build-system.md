# Webpack Build System

Auto-discovers and bundles assets for templates and sections.

## How It Works

1. **Template Discovery** - Scans for `index.scss` and `index.js` in template folders
2. **Section Discovery** - Scans for `scss/index.scss` and `js/index.js` in section folders
3. **Bundle Generation** - Creates separate bundles for each template and section
4. **Auto-loading** - PHP loads only the bundles needed for current page

## Entry Points

**Templates:**
- `index.scss` → `page-{template-name}.css`
- `index.js` → `page-{template-name}.js`
- `index-async.scss` → `page-{template-name}-async.css` (deferred)
- `index-async.js` → `page-{template-name}-async.js` (deferred)

**Sections:**
- `scss/index.scss` → `section-{section-slug}.css`
- `js/index.js` → `section-{section-slug}.js`

**Global:**
Configure in `src/config/config.user.js`

## Commands

```bash
npm run dev        # Development with HMR
npm run build      # Production build
npm run build:dev  # Development build
npm run clean      # Clear cache
```

## Output

```
/dist/
├── manifest.json           # Asset manifest
├── scripts/
│   ├── main.js
│   ├── page-portfolio.js
│   └── section-page-hero.js
└── styles/
    ├── main.css
    ├── page-portfolio.css
    └── section-page-hero.css
```

## Benefits

- **Per-template bundles** - Only load what's needed
- **Per-section bundles** - Sections load their own CSS/JS
- **Auto-discovery** - No manual configuration
- **Cache busting** - Content hashes in filenames

