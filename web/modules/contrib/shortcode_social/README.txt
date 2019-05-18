INTRODUCTION
------------

Provides a the ability to embed social media content via the shortcode API
provided by the shortcode module.

REQUIREMENTS
------------

This module requires the following modules:

 * Shortcode (https://www.drupal.org/project/shortcode)

RECOMMENDED MODULES
-------------------

 * WYSIWYG (https://www.drupal.org/project/wysiwyg)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

CONFIGURATION
-------------

 * You will need to enable the shortcodes for each of the text formats you are
   using within your WYSIWG here (admin/config/content/formats)

TROUBLESHOOTING
---------------

You may find that you need to update the Filter processing order for the
shortcodes to work correctly as the URLs are being converted before the
shortcodes.

You can reorder the filter process order for each of the text formats here:
admin/config/content/formats. You should have the shortcodes processing running
before the 'Convert URLs into links' option.

MAINTAINERS
-----------

Current maintainers:
 * Liam Hiscock (LiamPower) - https://www.drupal.org/u/liampower
