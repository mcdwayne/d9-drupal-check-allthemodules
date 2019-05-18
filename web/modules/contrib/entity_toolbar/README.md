# Entity toolbar

This module provides a UI to create new toolbars that lists entity types 
alphabetically.

# INTRODUCTION

This is helpful for large sites with entity types with many bundles. It allows 
easy access the subpages of the entity type, such as the "Manage fields" or 
"Manage display" local tasks for a particular entity type more easily from the 
admin toolbar drop down menus.

Out of the box there are optional configs for node types, paragraph types, 
taxonomy term types, media types and group types toolbars.

## REQUIREMENTS

* Drupal 8
* [Admin Toolbar](https://www.drupal.org/project/admin_toolbar) module

## INSTALLATION

Entity toolbar can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## CONFIGURATION

* Install and enable Admin Toolbar module.
  [Admin Toolbar](https://www.drupal.org/project/admin_toolbar)
* Install and enable Entity toolbar module.
  [Entity toolbar](https://www.drupal.org/project/entity_toolbar)
* Clear the drupal cache.

## USAGE

* enable and configure entity toolbars at /admin/config/content/entity_toolbar.

## Altering the Entity toolbar

* You can alter the toolbar using hook_toolbar().  If you would like alter the 
links within the toolbar, use hook_menu_links_discovered_alter().
