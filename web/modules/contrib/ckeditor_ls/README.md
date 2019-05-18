CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

The CKEditor Letter Spacing module adds a "tracking" dropdown to WYSIWYG that
allows users to apply the letter-spacing CSS property to text.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/ckeditor_ls

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/ckeditor_ls


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * CKEditor Letter Spacing plugin - http://ckeditor.com/addon/letterspacing


INSTALLATION
------------

 * Install the CKEditor Letter Spacing module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.

Manual download:
 * Download the plugin from http://ckeditor.com/addon/letterspacing.
 * Place the plugin in the root libraries folder (/libraries).

Result path to `plugin.js` should be  `/libraries/letterspacing/plugin.js`.
 * Enable CKEditor Letter Spacing in the Drupal admin.
 * Configure your WYSIWYG toolbar to include the buttons.

Installation with composer:
 * Add letterspacing to repositories section in `composer.json`:

```
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "ckeditor/letterspacing",
                "type": "drupal-library",
                "version": "0.1.2",
                "dist": {
                    "type": "zip",
                    "url": "https://download.ckeditor.com/letterspacing/releases/letterspacing_0.1.2.zip",
                    "reference": "master"
                }
            }
        }
    ],
```

 * Check that you have configured `installer-paths` for libraries. Example:

```
    "extra": {
        "installer-paths": {
            "web/core": ["type:drupal-core"],
            "web/libraries/{$name}": ["type:drupal-library"],
            "web/modules/contrib/{$name}": ["type:drupal-module"],
            "web/profiles/contrib/{$name}": ["type:drupal-profile"],
            "web/themes/contrib/{$name}": ["type:drupal-theme"],
            "drush/contrib/{$name}": ["type:drupal-drush"]
        }
    }
```

 * Run `composer require ckeditor/letterspacing`


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Content Authoring > Text
       formats and editors to configure the WYSIWYG toolbar.
    3. Select which format to edit and drag the Letter Spacing Icon into the
       Active toolbar.
    4. Save configuration.


TROUBLESHOOTING
---------------

In case Letter Spacing does not apply to the text - try to disable Limit allowed
HTML tags and correct faulty HTML filter, which calls to
Drupal\Component\Utility\Xss::filter() (this function ALWAYS stripes the style
attribute, no matter the configuration that you passed, is hardcoded)


MAINTAINERS
-----------

 * Igor Karpilenko (hamrant) - https://www.drupal.org/u/hamrant

Supporting organizations:

 * Five Jars - https://www.drupal.org/five-jars
 * Drupal Ukraine Community - https://www.drupal.org/drupal-ukraine-community
