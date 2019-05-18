Inline Formatter Field


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

The Inline Formatter Field module allows for templating and 
styling of entities on the front side. This module will create
a new field type called "Inline Formatter" which is a boolean. 
When the boolean is checked the field will render what is entered
in the "HTML or Twig Format" field for the formatter's settings 
in the "Manage Display" screen of the entity. This module makes 
use of the ACE Editor javascript library.


REQUIREMENTS
------------

This module requires the following modules:

  * Field (https://www.drupal.org/docs/8/core/modules/field)


RECOMMENDED MODULES
-------------------

  * Token (https://www.drupal.org/project/token)
  When enabled, token replacement patterns can be entered into 
  the formatter field.

  * Devel (https://www.drupal.org/project/devel)
  This module is helpful for finding the right field and field 
  variables when kint is enabled.


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal 
 module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.
 * Font Awesome can be downloaded in the libraries directory by following
 https://fontawesome.com/how-to-use/on-the-web/setup/hosting-font-awesome-yourself
 and naming the directory 'fontawesome', or use the CDN.
 * Ace Editor can be downloaded in the libraries directory by cloning
 https://github.com/ajaxorg/ace-builds/ and naming the directory 'ace-builds',
 or use the CDN.


CONFIGURATION
-------------
 
 * Configuration for the field format in editable on the manage displays
 form of the content type.
 * Configuration for where the source code is for Ace Editor and Font 
 Awesome is editable in the Inline Formatter Field Settings Form.


TROUBLESHOOTING
---------------

 * If the field is not rendering, check to see if the boolean field 
 is actually checked in the edit form for the content, and check to 
 make sure that valid html and twig is entered.

 * If the ACE Editor fails to load:
  - make sure that javascript is 
 allowed in your browser, and check for console logs.
  - check that the library path matches what is used in the libraries 
  file.


FAQ
---

Q: I checked the box in the content form and all that is rendering 
is a h1 "Hello World!". What am I missing?

A: The "Hello World!" message is the default template. In order to 
change this, go to the manage display tab for the entity and click 
on the gear for the inline formatter field. Then, replace the "Hello World!" 
with your own template.


Q: Can I use more than one of these on a single entity type?

A: Yes, you can use multiple inline formatter fields to a single entity. 
This will allow you to have many different 'displays' or formats for a 
single entity by checking which display you want, or you could display 
multiple parts of a rendered entity with separate inline formatter fields.


Q: Will the format render if the checkbox is not checked?

A: No, the checkbox must be selected in order for the format to be rendered. 
This will allow the ability for parts of an entities templat that may or 
may not be rendered based on the content creator.


MAINTAINERS
-----------

Current maintainers:
 * Bobby Saul - https://www.drupal.org/u/bobbysaul
