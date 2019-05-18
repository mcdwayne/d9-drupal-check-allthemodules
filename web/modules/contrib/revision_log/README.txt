CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Revision Log module will show the revisions as a log in
chronological/reverse orders.

 * For a full description of the module visit:
   https://www.drupal.org/project/revision_log

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/revision_log


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Revision Log module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Content types > [Content type to
       edit] > Manage display for configuration.
    3. Drag the History field into the enabled section.
    4. Select the contextual edit link to configure display order: Chronological
       or Reverse Chronological.
    5. Select the date format.
    6. Select the Header template. The available tokens are: Revision author,
       @action => Created/Updated, @datetime => Revision created time.
    7. Select the history limit. Set to 0 to show all.
    8. Update and save.


MAINTAINERS
-----------

 * Sandeep Reddy (sandeepguntaka) - https://www.drupal.org/u/sandeepguntaka
