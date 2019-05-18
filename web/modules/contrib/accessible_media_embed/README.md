Accessible Media Embed
======================


INTRODUCTION
------------

This module aims to bridge the gap between embedding media within a WYSIWYG
such as ckeditor and having a fully accessible website using context
sensitive alt tags for that embedded media.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/accessible_media_embed

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/accessible_media_embed


REQUIREMENTS
------------

This module requires the following modules:

 * Entity Embed
 * Media (from Core)


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.


CONFIGURATION
-------------

To enable this filter you would:

 * Configure Text formats and editors (/admin/config/content/formats)
 * Select the format to add the functionality to
 * Enable the 'Context sensitive alt for media' filter
 * Ensure it sits after the 'Display embedded entities' filter