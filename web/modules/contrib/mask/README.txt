INTRODUCTION
------------

This module uses the jQuery Mask Plugin to enable adding masks to input fields.
By masking the user input, the provided values are restricted to only the 
allowed formats. Examples of data that should be masked include phone numbers,
ZIP codes and IP addresses.

This module offers:

 * Field widget settings to add masks to fields. Several Drupal's widget types 
   are supported out-of-the-box. Others can be easily added by third-party 
   modules - by providing a my_module.mask_widget_types.yml file.

 * A Form API property ("#mask") to enable masking form elements in custom 
   forms. The supported form elements include "textfield" and "tel". More can 
   be easily added by third-party modules - with the help of the 
   Drupal\mask\Helper\ElementHelper class.


REQUIREMENTS
------------

This module uses the jQuery Mask Plugin by Igor Escobar 
(https://igorescobar.github.io/jQuery-Mask-Plugin/), but it is automatically
downloaded from a CDN when a page with a masked field is loaded. So there is no
special requirement during installation.


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.


CONFIGURATION
-------------

 * Go to your content type's Manage form display page, e.g. 
   admin/structure/types/manage/article/form-display.

 * Click the gear icon of the fields that are using supported field widgets to 
   change mask settings.

Currently, the supported field widgets are "Text field" and "Telephone".

Please refer to the module's documentation for more detailed information:
https://www.drupal.org/docs/8/modules/mask-field


MAINTAINERS
-----------

 * Daniel C. Biscalchin (dbiscalchin) - https://www.drupal.org/u/dbiscalchin
