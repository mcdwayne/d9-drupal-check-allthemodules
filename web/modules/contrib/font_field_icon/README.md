Font Field Icon
======

This module will add new field type "Font field icon" to the site.
It's combined from two fields - select with Fontawesome icon
and text field for link.

Features
-------------

This field has two formatters for output:
1 - Fontawesome icon and text (link)
2 - Only Fontawesome icon (clickable).

I hope this module will help developers to avoid the routine, connected with
fields for social links in the footer or in user's profile.

Also, when you will use this field for email with selected 
"envelope" Fontawesome icon 
- use in additional textfield only email address 
(e.g. info@example.com) and field
will be rendered as <a href='mailto:info@example.com'>info@example.com</a> link.

Installation
-------------

This module depends on Field and Libraries.
So ensure you have enabled both of them.

This module needs to be installed via Composer, which will download the
required library (Fontawesome to libraries/fontawesome).

1. Add the Drupal Packagist repository

    ```sh
    composer config repositories.drupal composer https://packages.drupal.org/8
    ```
This allows Composer to find Icon field and the other Drupal modules.

2. Download Font field icon

   ```sh
   composer require "drupal/font_field_icon ~1.0"
   ```
This will download the latest release of Font field icon module.
Use 1.x-dev instead of ~1.0 to get the -dev release instead.

If you're downloaded this module as an archive - do not forget to add 
fontawesome library 
from https://github.com/FortAwesome/Font-Awesome/archive/v4.7.0.zip 
to libraries/fontawesome.
Download and unpack this archive to libraries/fontawesome.
Folders structure should to be 
libraries/fontawesome/css, libraries/fontawesome/fonts, etc.


See https://www.drupal.org/node/2404989 for more information.
