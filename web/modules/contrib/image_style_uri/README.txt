INTRODUCTION
------------
In some cases there in twig to receive an image url from an image field.
This can be generally done by using the file_url() function.
But file_url does not allow for an image style to be chosen. This is where
this project comes into play.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-
   modules-find-import-enable-configure
   for further information.
 * Use the functionality in twig

 CONFIGURATION
-------------

The module does not need any configuration. Just use the functionality in twig.

TROUBLESHOOTING
---------------

Make sure you use the function in the following format:
{% set backgroundUrl =
    image_style_uri(content.field_image.0['#item'].entity.uri.value,
    'mystyle') %}
