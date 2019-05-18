Handsontable For YML Webform module
========================================

INTRODUCTION
------------
This module allows both the Drupal Form API and the Drupal 8 Webforms module
to use the Excel-like [Handsontable library](https://handsontable.com). You can
add one or more fillable tables to your forms.


REQUIREMENTS
------------
A Drupal 8 site with the Webform module installed. 


INSTALLATION
------------
1. Download the module. I suggest using Composer:
   `$ composer require 'drupal/handsontable_yml_webform:^1.0'`
2. Enable the module, e.g. using
   `$ drush en handsontable_yml_webform`


CONFIGURATION
-------------
3. Go to the build tab of a webform (/admin/structure/webform).
4. You can add one or more Handsontable elements to your webform just like any
   other element.
5. When you add your first Handsontable element, instructions on where to place
   the official JavaScript and CSS files from 
   [handsontable.com](https://handsontable.com).
6. Optionally: Every webform admin can specify 'View settings' for the 
   Handsontable look and feel. For example, you can specify the data type of
   columns by specifying a <code>columns</code> key with a list of types for
   each cell - see 
   https://docs.handsontable.com/pro/1.9.1/tutorial-cell-types.html for more 
   details.

### Webform submissions

All table data will be stored as a nested JSON list: each cell is a string, each
row a list in the list of rows.


MAINTAINERS
-----------
Current maintainer:
 * https://www.drupal.org/u/gogowitsch
