## WordPress Starter Theme ##
- Theme is still WiP but feel free to use any part of code

Includes:

1. SCSS
1.1 Included SCSS pacakages:
- breakpoint sass
- normalize.css
- wp default styles scss
2. ES6 + Babel
3. Webpack 4 build script
4. Asset optimization (images and fonts)
5. SVG Spritemap generation
- PHP function for using specific icon in templates <?php svg_icon('filename'); ?>
- SCSS mixin for using specific icons in your css classes
6. Composer dependencies, including ACF PRO (You need to provide licence key)
7. Yarn package management
8. Browsersync
9. SoberWP models for creating custom post types
10. Default WordPress 'clean up'
11. Assets versioning
12. Theme Wrapper (introduced in Roots Sage theme)

##### Install instructrions #####
- Add ACF_PRO_KEY to your .env file to enable download of ACF PRO from private repository
a) create file: .env in theme root folder
b) Add key,eg: ACF_PRO_KEY=abcd

1. Clone repository
`~ git clone git@github.com:code-soup/pumpkin.git .`

2. Start fresh with every new theme by deleting git repository and creating new one
`~ rm -rf .git`
`~ git init`
`~ git add .`
`~ git commit -am 'init'`

3. Install NPM dependencies
`~ yarn`

4. Install Composer dependencies
`~ composer install`

5. Update .gitignore
- remove these folder from .gitignore so they get uploaded on push
vendor
wp-content

6. rename /src/config-local-example,json > config-local.json and update paths based on your local install