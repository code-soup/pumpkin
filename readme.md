This boilerplate is not yet ready for production, it's a WiP

It's a collection of best practices for streamlined theme development and maintenance using ACF Pro.
After more than 300 custom WP theme built and more than 15 years working with WordPress this is a custom workflow proven to work for me in every environment.
Focus is on keeping things DRY, reusable and organized.

Key concepts:

- Folder based template loader with per-page-template overrides
Template becomes visible as soon as folder is created
Eg:
1. /templates/post-type/{post-type-name}/{page-template-name}/index.php
2. /templates/post-type/{post-type-name}/index.php

Or can override only part of the template:
1. /templates/post-type/{post-type-name}/{page-template-name}/footer.php
2. /templates/post-type/{post-type-name}/footer.php
3. /templates/shared/footer.php

Per page-template/post-type specific css/js bundle
- All files related to a specifc template are saved inside same page-template folder.
- Webpack script looks for page templates on build, based on index.scss/js index-async.scss/js, bundles it and script loader is loading appropriate css/js per page template. index-async.scss/js loading is defered.
- common/shared scss/js is manually added in config.user.js

ACF Flexible Field 
- Used to create reusable sections, ACF Admin categories to organize them
- Field values are retrieved with helper PHP class

- Theme Options are saved with ACF into custom post type, also by specifying page-template and load them with PHP helper class
- Global page-config.php with hardcoded options, can be overrided for each template

Reusable static compoments
- with custom PHP abstract class to reuse them across the site and add new ones using same pattern
Eg: templates/share/components/website-logo
All files required for this component are saved in this folder, later you include css/js in custom page template where required.


### Getting started
- Requirements:
  - PHP 8.0+, WordPress 6.x
  - Node.js >= 20.9.0, npm or yarn
  - ACF Pro (theme expects ACF fields; JSON sync recommended)
- Quick start:
  - Activate the theme in WordPress
  - Install deps: `npm i`
  - Dev server (HMR): `npm run dev`
  - Build (dev mode): `npm run build`
  - Build (production): `npm run build:prod`
  - Lint: `npm run lint` (or `lint:scripts`, `lint:styles`)
  - Clean dist: `npm run clean`
