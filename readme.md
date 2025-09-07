# Pumpkin [Alpha Release]
### Next generation WordPress Theme boilerplate concept

This boilerplate is WiP and **NOT production ready**.

It's a collection of best practices for streamlined theme development and maintenance using ACF Pro.
After more than 300 custom WP theme built and more than 15 years working with WordPress this is a custom workflow proven to work for me in every environment.
Focus is on keeping things DRY, reusable and organized.

## Key concepts:

#### Folder based template loader with per-page-template overrides
Template becomes visible for selection as soon as folder is created.

    1. /templates/post-type/{post-type}/{template-name}/index.php
    2. /templates/post-type/{post-type}/index.php 

Or can override only part of the template, in this loading order:

    1. /templates/post-type/{post-type}/{template-name}/footer.php => Page template specific footer.php
    2. /templates/post-type/{post-type}/footer.php                 => Post type specific footer.php
    3. /templates/shared/footer.php                                => Default footer.php

#### Per page-template/post-type specific css/js bundle
All files related to a specifc template are saved inside same page-template folder.

    templates/{post-type}/{template-name}
    /assets/images      => Page specific images
    /assets/icons       => Page specific icons
    /scss               => Page specific SCSS
    /scss/sections      => Section specific SCSS
    /js                 => Page specific Js
    /sections           => PHP/HTML template for each sections
    /index.php          => Main page template file
    /index.scss         => Dynamic Webpack entrypoint
    /index-async.scss   => Dynamic Webpack entrypoint
    /index.js           => Dynamic Webpack entrypoint
    /index-async.js     => Dynamic Webpack entrypoint


Webpack script looks for page templates on build, based on index.scss/js index-async.scss/js, bundles it and script-loader class is loading appropriate css/js per page template. index-async.scss/js loading is defered.
common/shared scss/js is manually added in config.user.js

#### ACF Flexible Field
- Used to create reusable sections, ACF Admin categories to organize them
- Field values are retrieved with helper PHP class

#### Theme Options to Post Type
Saved with ACF into custom post type.
Fields are assigned by specifying page-template, load and access options with PHP helper class.

#### Global PHP config
Global page-config.php with hardcoded options, can be overrided for each template using same template hierarchy

#### Reusable static compoments
Reusable components that don't need to be edited in WP admin, with custom PHP abstract class to reuse them across the site and add new ones using same pattern.
All files required files for this component are saved in component folder, you include css/js in custom page template where required.

    templates/shared/components/website-logo/Component.php
    
    Usage in template:
    <?php echo \CodeSoup\Pumpkin\Components\WebsiteLogo\Component::render(); ?>

### Requirements:
- PHP 8.2+, WordPress 6.x
- Node.js >= 22, npm or yarn
- ACF Pro
