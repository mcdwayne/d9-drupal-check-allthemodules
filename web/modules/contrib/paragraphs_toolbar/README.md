# PARAGRAPHS TOOLBAR

This module creates a new toolbar that lists paragraph types alphabetically. 

# INTRODUCTION

This is helpful for large sites with many paragraph types to easily access the 
subpages of the paragraph type, such as the "Manage fields" or "Manage display" 
local tasks for a particular paragraph type.


## REQUIREMENTS

* Drupal 8
* [Admin Toolbar](https://www.drupal.org/project/admin_toolbar) module

## INSTALLATION

Paragraphs Toolbar can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## CONFIGURATION

* Install and enable Admin Toolbar module.
  [Admin Toolbar](https://www.drupal.org/project/admin_toolbar)
* Install and enable Paragraphs toolbar module.
  [Paragraphs Toolbar](https://www.drupal.org/project/paragraphs_toolbar)
* Clear the drupal cache.

## USAGE

* The toolbar will appear if the user has the "administer paragraphs types" 
permission.

## Altering the Paragraphs Toolbar

* You can alter the toolbar using hook_toolbar().  If you would like alter the 
links within the toolbar, use hook_menu_links_discovered_alter().
