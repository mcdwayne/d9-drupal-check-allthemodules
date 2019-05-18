DESCRIPTION
-----------

This is a very simple module makes available Flag link in a block.

REQUIREMENTS
------------

* Block module (core)
* Flag module (https://www.drupal.org/project/flag)


INSTALL
-------

If your site is managed via Composer, use Composer to download the module.

composer require drupal/flag_block:^1.0

Or

Install the module as any other module.

USAGE
-----

Enable the module in 'Extend' page (/admin/modules).

Or

use Drush: drush en flag_block

Visit the 'Block layout' page and click 'Place block'.
On the block settings for you will see 'Flag' field.
Select one from the your Flag types list (/admin/structure/flags).
Click 'Save block'.
