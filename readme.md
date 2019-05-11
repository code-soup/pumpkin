# Pumpkin
Pumpkin is a WordPress Starter Theme with a specific workflow, built on top of ACF PRO plugin which enables you to build component based custom themes.
Powered by Webpack build script

### Features
- Component base theme organization
- Component based code splitting
- Inline critical CSS
- Improved accessibility
- Theme wrapper
- Built in sidebar manager
- Predefined Theme Options (Further extendable with ACF PRO)
- PSR-4 Autoloader
- Composer for PHP dependencies
- SoberWP models for creating custom post types

### WP Mods
- WordPress clean up
- Custom login screen
- Custom 404 page
- Cached nav menus

### Assets
- Yarn package management
- Webpack build script
- ES6 + Babel
- SCSS
- Assets versioning
- Browsersync
- SVG Spritemap

### Extras
- Bitbucket pipeline for deploying theme over FTP/SFTP

### Install instructions
1. Clone repository

`git clone git@github.com:code-soup/pumpkin.git .`

2. Start fresh

`rm -rf .git`

`git init`

`git add .`

`git commit -am 'init'`

3. Create `.env` file and add your ACF PRO key

`ACF_PRO_KEY=abcd`

4. Install PHP dependencies

`composer install`

5. Install node packages

`yarn`

6. Rename `/src/config-local-example.json` to `config-local.json` and update paths to your local environment