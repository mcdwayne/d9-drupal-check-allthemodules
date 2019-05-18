INTRODUCTION
------------

This module allows for custom CKEditor configuration to be attached to
individual editors.

See [https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html](https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html)
for configuration options.

REQUIREMENTS
------------

This module requires the following modules:

  * CKEditor (included in Drupal core)

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
[https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
for further information.

CONFIGURATION
-------------

  * Navigate to an editor configuration page
    (/admin/config/content/formats/manage/\[editor\])
  * On the configuration page, navigate to _CKEditor custom configuration_ 
    under _CKEditor plugin settings_
  * Enter custom configuration with each item on its own line
    formatted as `[setting.name] = [value]`

    Examples:
    `forcePasteAsPlainText = true`
    `forceSimpleAmpersand = true` 

MAINTAINERS
-----------

Current maintainers:
  * Chris Burge - https://www.drupal.org/u/chris-burge
  * Martin Klíma (martin_klima) - https://www.drupal.org/u/martin_klima
