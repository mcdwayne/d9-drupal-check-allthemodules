WARNING
----------
This module will be only available for Drupal 8.
No version will be developed for Drupal 7.

DESCRIPTION
--------------------------------
The Advanced Update module provide a simple way to apply updates and
migrations without using the update module of Drupal.

PROBLEMATIC
--------------------------------
Problems of using the core update module in a Drupal project:

- Problem with versioning. It's difficult to use hook_update with these
incremental numbers in a complex project with a team of developers.
If two developers used the same update number (like 8101), it will cause a
conflict and take time to be resolved. In a process of continuous delivery,
it is this kind of conflict we don't want to have.
- Unlike other frameworks, with Drupal it is not possible to reverse an
update.

FUNCTIONALITIES
--------------------------------
- Using Drupal Console to generate a php class for each update
(drupal generate:advancedupdate or drupal generate:adup).
- All php class contain a function up() and a function down() in order to
reverse your update.
- Using Drush in order to display updates available
- Using Drush in order to apply updates up or down
- Using Drupal interface in order to list, create or delete an advanced update.

CREATE AN UPDATE WITH DRUPAL CONSOLE
------------------------------------
The line below is used to generate a new class in order to create an update.

drupal generate:advancedupdate

or

drupal generate:adup

After that :

- type the module "machine name" you want your update available.
- type the description of your update.

CLASS GENERATED WITH DRUPAL CONSOLE COMMAND
--------------------------------------------
<?php

namespace Drupal\mymodule\AdvancedUpdate;

use Drupal\advanced_update\AdvancedUpdateInterface;
use Drupal\advanced_update\UpdateNotImplementedException;

/**
 * Class AdvancedUpdate571c876ca9060.
 *
 * My new functionality description.
 *
 * @package Drupal\mymodule\AdvancedUpdate
 */
class AdvancedUpdate571c876ca9060 implements AdvancedUpdateInterface {

  /**
   * This method is called by AdvancedUpdateManager.
   */
  public function up() {
    throw new UpdateNotImplementedException();
  }

  /**
   * This method is called by AdvancedUpdateManager.
   */
  public function down() {
    throw new UpdateNotImplementedException();
  }

}


LAUNCH YOUR UPDATES WITH DRUSH
-------------------------------

In order to apply an update up or down you have to use Drush.
You can see examples of commands available by typing:

drush adup -h

See below a copy of the drush help command for this module.

$ drush adup -h
Apply advanced updates available

Examples:
 drush adup                           Perform all availables advanced
                                      updates.

 drush adup mymodule                  Perform all advanced updates up
                                      for the mymodule module
 drush adup mymodule down             Perform all advanced updates down
                                      for the mymodule module
 drush adup mymodule up 2             Perform the next 2 advanced updates
                                      up for the mymodule module
 drush adup --report                  Display all advanced updates up
                                      available.
 drush adup mymodule --report         display a list of available updates
                                      up for the mymodule module
 drush adup mymodule down 1 --report  display the next update down available
                                      for the mymodule module

Arguments:
 module_name                          A name of a specific module to perform
                                      update
 direction                            The update direction (up or down)
 number                               Max number of updates to perform

Options:
 --report                             Display a report of selected updates