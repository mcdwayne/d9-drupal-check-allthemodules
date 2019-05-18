CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module will display a Drupal menu using a jQuery UI accordion effect. The
top-level menu items are referred to as header items. The accordion effect is
invoked when the triggering event occurs on a header item. The triggering event
may be a mouse down, mouse click, or mouse over. The submenu expands to display
the menu items beneath the header. A subsequent triggering event on the same
header item collapses the menu beneath it.

 * For a full description of the module visit:
  https://www.drupal.org/project/accordion_menus

 * To submit bug reports and feature suggestions, or to track changes visit:
  https://www.drupal.org/project/issues/accordion_menus


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Accordion Menus module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
--------------

    1. Navigate to Administration > Extend and enable the Accordion Menus
       module.
    2. Navigate to Administration > Configuration > User Interface >
       Accordion Menus.
    3. Select the menus which want to be accordion menu block and save form.
    4. Navigate to Administration -> Menus -> menu which need to accordion
      -> edit all top menu item of the menu and tick the checkbox of 'show
      as expended' and save the form.
   5. Place the menu block in the expected region. Block name will be
      'Accordion {menu name}'


MAINTAINERS
-----------

The 8.x branches were created by:

 * Biswajit Mondal (bisw) - https://www.drupal.org/u/bisw

as an independent volunteer
