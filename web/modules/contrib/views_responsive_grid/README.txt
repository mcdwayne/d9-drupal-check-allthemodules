CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * How to use


INTRODUCTION
------------

Current Maintainers:
Mark Carver - https://drupal.org/user/501638
Ian Whitcomb - https://drupal.org/user/771654

Views Responsive Grid provides a views plugin for displaying content in a
responsive (mobile friendly) grid layout. Rather than trying to force the
standard Views grid display to work for mobile this provides the same
functionality, but in DIVs instead of tables. Provided is also the ability to
specify a horizontal or vertical grid layout which will properly stack the
content on a mobile display.


INSTALLATION
------------

1. Download module and copy views_responsive_grid folder to /modules
2. Enable Views and Views Responsive Grid modules.


HOW TO USE
------------

After enabling the module, create a new view with the responsive grid display
format. Specify the number of columns, alignment and classes of the grid.

If the automatic width option is enabled, the plugin will determine the width of
the columns automatically. If it is disabled, you may need to specify classes
for either the rows, columns or both depending on your configuration and theming
needs.

If a theme automatically injects classes, the automatic width option may also
interfere with the visual aspect of the grid.

This module does not provide any visual styling for the grid, that responsibility
is left to themes.
