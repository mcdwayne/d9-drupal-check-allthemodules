# Colossal Menu

Menu of Epic Proportions

## Introduction
Colossal Menu is a new type of menu system that is built on content entities
rather than the plugin & config system in the core
[Menu System](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Menu!menu.api.php/group/menu/8).
This allows for fieldable menu links as well as multiple link types.

## Purpose
The primary purpose of this module is to enable site builders to create and
modify [mega menus](http://www.sitepoint.com/mega-drop-down-menus/).
The
core
[Menu System](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Menu!menu.api.php/group/menu/8)
is not robust enough to handle this use case.

Rather than modifying the core
[Menu System](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Menu!menu.api.php/group/menu/8)
which could change the assumptions of how a menu works; we'll instead create our
own system that can have it's own assumptions.

This module does **not** seek to be fully compatible with the core
[Menu System](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Menu!menu.api.php/group/menu/8)
as a drop-in replacement; but instead should be used alongside it.

## Name
The name 'Colossal Menu' was coined by Seth Cardoza
([sethcardoza](https://www.drupal.org/u/sethcardoza)).

## Installation
Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.

## Requirements
* [Machine Name Widget](https://www.drupal.org/project/machine_name_widget)

## Setup
1. Go to https://example.com/admin/structure/colossal_menu/link_type and create
   at least one link type.
2. Go to https://example.com/admin/structure/colossal_menu
3. Click "Add Menu" and create a new Colossal Menu.
4. Add a link to the menu.
5. Go to https://example.com/admin/structure/block and place the Colossal Menu
   block in the region you would like.

## Maintainers
Current maintainers:
* David Barratt ([davidwbarratt](https://www.drupal.org/u/davidwbarratt))

## Sponsors
Current sponsors:
* [Golf Channel](https://www.drupal.org/node/2374873)
