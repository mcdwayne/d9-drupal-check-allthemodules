CONTENTS OF THIS FILE
--------------------

 Introduction
 Requirements
 Installation
 Configuration
 Maintainers


INTRODUCTION
------------

The Node Manager module allows the user to send a notification email when a
predefined date is reached.

We are planning to add more sub-modules that will help editor to manage nodes.

 * Node Delete - Allows the user to delete a node when it has expired, i.e
   notify date reached.
 * File Manager - Allows the user to manage files used in expired nodes.

 * For a full description of the module visit:
   https://www.drupal.org/project/node_manager

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/node_manager


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Node Manager module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Upon saving, the module adds the following fields to Content types: Node
       expire date and Node notify email.
    3. Navigate to Administration > Configuration > Node Manager > Node notify
       for notification configurations. Configs include: Email subject, Email
       body, and number of days before notification is sent. Save configuration.
    4. Navigate to Administration > Structure > Content types > [Content type to
       edit] > Manage form display and drag the Node expire date and Node notify
       email fields in to the enabled fields.
    5. Use the contextual links to further configure the fields and save.


MAINTAINERS
-----------

Supporting organization:

 * ]init[ AG - https://www.drupal.org/node/2356891
