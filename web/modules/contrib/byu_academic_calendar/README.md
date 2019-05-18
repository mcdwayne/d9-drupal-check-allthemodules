# Academic Calendar Module For Drupal 8

This module generates a block that you can display on your websites. The module displays all academic calendar information and it can't be customized or limited to only certain academic calendar items.

## Dependencies
Fontawesome library.

## To Use
1. Git clone or Download the module. If you download it, make sure to remove '-master' from the module folder name.
2. Make sure you have included Fontawesome, either through the module or by adding this library to your theme in the libraries file:
```font-awesome:
  remote: https://fortawesome.github.io/Font-Awesome/
  version: 4.5.0
  license:
    name: MIT
    url: https://fortawesome.github.io/Font-Awesome/license/
    gpl-compatible: true
  css:
    theme:
      https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css: { type: external, minified: true }```
You will then want to include the library in your .info file for all pages or see this documentation:
https://www.drupal.org/docs/8/theming-drupal-8/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-theme

3. Enable the module. It will generate the block.
4. To place the block, go to the Blocks page and place it in a region and set it on whichever page(s) you would like.

## Screenshots
This module is in use on the new BYU homepage (coming soon!).

## Behind the Scenes
The html is dynamically loaded from the api Michael Kemp built based off the registrar's data. The css and javascript are not dynamically loaded.

Navigating between the 6 months at a time using the < > buttons changes immediately without delay because the data is loaded when the page loads.
