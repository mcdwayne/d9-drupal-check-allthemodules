CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module improves the appearance of the Drupal admin tabs
(view, edit, translate, .. links).

It shows a "settings" icon at a fixed position on the bottom right
of the screen (see screenshot 1).
When clicking this icon, the admin tab items (create, edit, ..) pop up
as clickable icons (see screenshot 2).

This is achieved in pure css without any javascript.
The css will only load for authenticated users, so anonymous users are
uninfected if this module is enabled.

All icons are SVG icons and are minified into a single svg sprite, so the
number of resources for this module is very low.


REQUIREMENTS
------------

This module has no other requirements outside of Drupal core.


INSTALLATION
------------

Install the better_admin_tabs module as you would normally install a
contributed Drupal
module:
- require the repository:
```
composer require drupal/better_admin_tabs --prefer-dist
```
- enable the module:
```
drush en better_admin_tabs -y
```


CONFIGURATION
--------------

There is no configuration needed for this module. Just enable and
enjoy how good your admin tabs look now.


MAINTAINERS
-----------

The 8.x.1.x branch was created by:

 * Joery Lemmens (flyke) - https://www.drupal.org/u/flyke

A big thank you to Martijn Cuppens (currently working @ Intracto, Herentals,
Belgium)
for most of the styling and icons.
