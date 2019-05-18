CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Menu Multilingual module provides multilingual features for menu blocks, to
filter out menu items that do not have translated labels or link to untranslated
content.

Out of the box integrations provided for the next menu block types:
`systemMenuBlock`, `menuBlock`.
Supported menu item types: `MenuLinkContent`.<br>
Supported menu item links: any that extend a `ContentEntityBase` class.


FEATURES
--------

* Filter menu items that does not have a translated label.
* Filter menu items that link to unstranlated content.

**Notice:**
* Content that is not translatable or have language Not Applicable will never be
filtered as non-translated content as they belong to all languages
* Content with language Not Specified will always be filtered as non-translated
content as they do not belong to any languages.
* When a menu item is filtered away all sub-menu items will also be filtered
away.


REQUIREMENTS
------------

Requieres these modules to work:
* menu_link_content
* content_translation


RECOMMENDED MODULES
-------------------

This module is integrated and works out of the bow with block provided from:
 * [Menu block](https://www.drupal.org/project/menu_block)
 * [Context](https://www.drupal.org/project/context)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

When you configure menu blocks at `admin/structure/block` you will get two new
options:
 * Hide menu items without translated label
 * Hide menu items without translated content

**Don't forget** to clear a cache after adding a new menu item or change a block
settings.


MAINTAINERS
-----------

Developed by
 * [vlad.dancer](https://drupal.org/u/vladdancer)

Designed by
 * [matsbla](https://drupal.org/u/matsbla)
