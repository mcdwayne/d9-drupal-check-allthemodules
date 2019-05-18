CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers
 
 
INTRODUCTION
------------

Content Type Clone alows users with the "Administer content types" permission 
to clone content types in one click.
   
   
REQUIREMENTS
------------

No special requirements

 
RECOMMENDED MODULES
-------------------

 * Token (https://www.drupal.org/project/token):
   When enabled, tokens are available for name, the machine name
   and the description when cloning existing content types.
   
   
INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.
   

CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration. When
enabled, the module will add a "clone" link to each existing content type in 
the content type list (admin/structure/types).


HOW TO USE
----------
After having installed and enabled the module, follow the steps below:

 1. Go to the content type list (admin/structure/types).
  
 2. Click on the "clone" list next to the content type you want to clone.
  
 3. In the form that will appear, provide a name, a machine name and 
    a description (optional) for the new content type 
    that is about to be created.
   
 4. Click "Generate". The cloned content type will then appear 
    in the content type list.

Cloning options:
When cloning a content type, there are 2 self explanatory options 
in the creation form.

 * Copy all nodes from the source content type to the target content type
 	
 * Delete all nodes from the source content type after 
   they have been copied to the target content type
