CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Contacts


INTRODUCTION
------------

Machine provides 'machine' base field definition for configured entity types
using provided admin form.
The field definition has constraints on uniqueness and format of input data.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.


CONFIGURATION
-------------

 * Configure user permissions in Administration » People » Permissions:

   - Access Machine configuration

     Users in roles with the "Access Machine configuration" permission will see
     the administration link "Machine settings" in development section.

 * Select entity types in Administration » Configuration »
   Development » Machine settings.

 * Edit any entity of selected type - you will find new form field
   with label "Machine name".


Contact
--------------------------------------------------------------------------------
The best way to contact the author is to submit an issue, be it a support
request, a feature request or a bug report, in the project's issue queue:
  https://www.drupal.org/project/issues/machine
