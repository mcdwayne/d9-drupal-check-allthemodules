INTRODUCTION
------------

This module adds white label functionality to Drupal by allowing certain roles
to upload custom logo's and color schemes. Color schemes require a theme that is
compatible with core's color module.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/whitelabel

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/whitelabel


REQUIREMENTS
------------

 * Entity Reference Revisions: 
 https://www.drupal.org/project/entity_reference_revisions


RECOMMENDED MODULES
-------------------

 * Color (Drupal core):
   When enabled, users can create custom color schemes (if the theme supports 
   this).


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

 * Users allowed to send white labeled links require the 'Serve white label 
   pages' permission. This also unlocks the whitelabel options in their user 
   profiles.
   
   * Configure user permissions in Administration » People » Permissions.

 * The global permissions for this module define the white label mode (either 
   with a query parameter of with a domain prefix), as well as the global 
   permissions for all white label users.
 
    * Configure permissions in Administration » Configuration » User Interface 
    » White label.


USING A DOMAIN PREFIX
---------------------

In order to be able to use this module in domain prefix mode, you require a
white label DNS record. Additionally if your website is running on SSL, you 
might need a white label SSL certificate as well.
