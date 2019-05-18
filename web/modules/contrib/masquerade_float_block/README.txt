INTRODUCTION
------------
Masquerade float block module works with Masquerade module and provides float
Masquerade block.

The module is useful when you use panels everywhere on the site and
features to export the configuration.

With this module you don't need to care about removing Masquerade
block from panel configuration for stage or live environment.

Initial block position is left top, but it can be changed by dragging
the block. Position will be remembered.

Block inherits all settings from native Masquerade block.

REQUIREMENTS
------------
This module requires the following modules:
 * Masquerade (https://drupal.org/project/masquerade)

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-7
for further information.

CONFIGURATION
-------------
Once module was enabled, you will see the Masquerade floating block on the
left top position.

When the floating Masquerade block is shown an overlay protects the page
against any click. To deactivate the overlay simply click on "Hide" button.
Which is located inside the floating Masquerade block.

Also, you can control appearing of the Masquerade block in three ways:

 * With admin UI: navigate to /admin/config/development/masquerade-float-block
   (Configuration > Development > Masquerade Float Block) , enable or disable
   float block with chekbox.
 * Pass the ?mfb_show parameter in GET request to 1 or 0
     ex.: http://site.com?mfb_show=1 - enable Masquerade block
    - 1 enable Masquerade block on all pages
    - 0 disable Masquerade block on all pages
 * In settings.php:
     $config['masquerade_float_block.settings']['visible'] = 0;
       - Masquerade block disabled
     $config['masquerade_float_block.settings']['visible'] = 1;
       - Masquerade block enabled
