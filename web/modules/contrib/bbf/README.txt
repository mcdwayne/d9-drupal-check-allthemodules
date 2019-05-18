-- SUMMARY --

* This module is used to render a pre-configured block based on a toggle switch from a boolean field.
* If you want to show or hide a block within the content area based on a checkbox value,  this is the module for you!
* You need to configure which block to be displayed in the field formatter settings for the boolean field in the entity's manage display screen.

-- REQUIREMENTS --

You must have the block and display suite modules installed.

-- INSTALLATION --

* Install as usual as per http://drupal.org/node/895232.

-- USAGE --

* Create a boolean field.
* On the entity's display settings, set the display to use "Boolean Block Formatter" for the boolean field.
* Select the block you need to display on the field formatter settings.

-- IMPORTANT NOTE --

This module loads only the configured blocks, that means a block that is placed in the block layout. It does not load plugins. You can place the block in the Disabled section, if you only want to configure the block, but don't want to show the block in a region.

-- SIMILAR MODULES --

* Block Formatter: https://www.drupal.org/project/block_formatter
* Menu Formatter: https://www.drupal.org/project/menu_formatter
* Contact Formatter: https://www.drupal.org/project/contact_formatter

-- SUPPORTING ORGANIZATIONS --
The Collegeboard

-- CONTACT --

Current maintainers:
* Vinoth Govindarajan (vinothg) - https://www.drupal.org/u/vinothg
