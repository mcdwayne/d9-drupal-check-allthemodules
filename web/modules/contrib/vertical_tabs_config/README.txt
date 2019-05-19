CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration

INTRODUCTION
------------

This module allows you to:

 * Hide vertical tabs on add/edit node pages depending on content type and
   role.

 * Decide vertical tabs order.

 * IMPORTANT NOTE: The php code responsible for the customization of vertical
   tab order is based on user "knight" answer here:
   http://drupal.stackexchange.com/questions/12979/how-can-i-change-the-order-of-vertical-tabs-in-drupal-7

 * WARNING: You may need to clear drupal's and/or browsers cache if you don't
   see any changes after modifying vertical tabs order.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

 * Configure module's options in Administration » Configuration » User Interface
   » Vertical Tabs Config:

    - Tab 1: Vertical tabs visibility.

      For each content type you will see a list of vertical tabs to hide. If
      you want this changes to only apply to certain roles, select desired
      roles for that content type.

    - Tab 2: Vertical tabs order.

      Set a weight for each vertical tab.
