CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Color Schema UI module extends Drupal by functionality for changing your sites color schema via a CSS selectors 
related color picker. So you are able to define a handful of base color zones (e.g. site background color, footer
background color, content font color etc.) and let specific user roles define them easily. You will get a 
[color picker](https://vanilla-picker.js.org/), so non-developer users can change the colors of your site. Colors will 
be compiled into a SCSS (for defining the color variables) and CSS file in your site's files folder.

Place a color_schema_ui.scss file into your active theme for inital color settings. If there is none, there is a default
SCSS file in the module.

REQUIREMENTS
------------

This module requires no Drupal modules outside of Drupal core. Libraries on which this module is based:
* [scssphp](https://github.com/leafo/scssphp): SCSS compiler written in PHP
* [vanilla-picker](https://vanilla-picker.js.org/): A simple, easy to use vanilla JS (no dependencies) color picker 
  with alpha selection.
* [invert-color](https://github.com/onury/invert-color): Generates inverted (opposite) version of the given color. (<1KB)

See all JavaScript library dependencies in `color_schema_ui/js/package.json`. All PHP library
dependencies are contained in `color_schema_ui/composer.json`.

INSTALLATION
------------

 * Install the Color Schema UI module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.
   
If you have made any JavaScript code changes, you must re-compile the JavaScript code.
1. Switch to the `js` folder
2. Make sure NPM and NVM are installed
3. Run `nvm use 9.11.1` (its proven, that NPM works with version `9.11.1` - newer versions can possibly work, too)
4. Run `npm install` (installs all JavaScript dependencies)
5. Run `npm run build` (compiles the assets for a production environment, run `npm run build-dev` for a development
environment. Then you will have JavaScript source maps for example.)

CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > System > Color Schema UI to edit the possible colors. After the
       colors are configured, mind the SCSS definitions to propagate color picker settings to the theme.
    3. Visit any node and pick your color by the local task tab menu item. Color Schema UI is visible on nodes only.

MAINTAINERS
-----------

 * Peter Majmesku - https://www.drupal.org/u/peter-majmesku

Supporting organization:

 * publicplan GmbH - https://www.drupal.org/publicplan-gmbh
