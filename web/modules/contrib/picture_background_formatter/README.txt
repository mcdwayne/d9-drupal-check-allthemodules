Introduction
------------

The Picture Background Formatter module creates an image field formatter that
generates background image CSS code based on a Picture Mapping.


Requirements
------------

This module requires the following modules:

  * Picture (https://www.drupal.org/project/picture)


Installation
------------

  * Install as you would normally install a contributed Drupal module. See:
    https://drupal.org/documentation/install/modules-themes/modules-7
    for further information.


Configuration
-------------

  * A picture mapping must be created prior to utilizing the Picture Background
    Formatter

  * Add an image field to your desired Content Type

  * In the Content Type Display panel, change the format of your image field
    to Picture Background Formatter

  * Click the field settings gear to expose the Picture Mapping and Selector
    fields

    - The chosen Picture Mapping will create image style varients just as the
      normal Picture formatter would

    - Input the desired CSS Selector where you want the background image
      to appear


Troubleshooting
---------------

If you do not see the Picture Background Formatter formatter in your Content
Type display, please make sure you have correctly configured a valid Picture
Mapping via admin/config/media/picture

Picture Background Formatter only uses Image Styles for its output. Any
breakpoints that use Picture module's Sizes Attribute will be skipped.
