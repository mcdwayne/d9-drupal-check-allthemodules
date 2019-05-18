# NODE TYPES TOOLBAR

This module creates a new toolbar that lists node types (bundles) alphabetically. 

# INTRODUCTION

This is helpful for large sites with many node types to easily access the 
subpages of the node type, such as the "Manage fields" or "Manage display" 
local tasks for a particular node type.


## REQUIREMENTS

* Drupal 8
* [Admin Toolbar](https://www.drupal.org/project/admin_toolbar) module

## INSTALLATION

Node Type Toolbar can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## CONFIGURATION

* Install and enable Admin Toolbar module.
  [Admin Toolbar](https://www.drupal.org/project/admin_toolbar)
* Install and enable Node Types toolbar module.
  [Node Types Toolbar](https://www.drupal.org/project/node_types_toolbar)
* Clear the drupal cache.

## USAGE

* The toolbar will appear if the user has the "administer content types" 
permission.

## Altering the Node Types Toolbar

* You can alter the toolbar using hook_toolbar().  If you would like alter the 
links within the toolbar, use hook_menu_links_discovered_alter().
