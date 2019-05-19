TABLE OF CONTENTS
-----------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Issues


INTRODUCTION
------------

The main goal of this module is to offer a solution to other modules that need 
some sort of approval workflow. Through event subscriptions, these modules would 
be able to respond to state transitions, like approval or rejection.


REQUIREMENTS
------------

This module requires the following contributed modules:
 * Entity API (https://drupal.org/project/entity)
 * Entity Extra (https://www.drupal.org/project/entity_extra)
 * State Machine (https://www.drupal.org/project/state_machine)
 
The following module is required by the User Request UI submodule:
 * Views Link Area (https://www.drupal.org/project/state_machine)


RECOMMENDED MODULES
-------------------

The following contributed modules are recommended:
 * Sender (https://drupal.org/project/sender)
   With this module enabled, e-mails can be sent whenever requests are created, 
   received or have their status changed.


INSTALLATION
------------

Just copy the module's files to the Drupal installation and enable it as usual.

Links will be added to the "Structure" menu to configure request and response 
types.
   
   
ISSUES
------

Issues should be reported at https://www.drupal.org/project/issues/user_request.
