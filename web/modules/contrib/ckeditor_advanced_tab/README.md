CKEditor Advanced Tab for Dialogs
====================

INTRODUCTION
------------

This module integrates the [dialogadvtab](
https://ckeditor.com/cke4/addon/dialogadvtab) CKEditor plugin for Drupal 8.

This plugin provides the Advanced dialog window tab to extend some editor dialog windows.
Thanks to this other plugins do not need to implement the same features for their dialog windows.

REQUIREMENTS
------------

* CKEditor Module (Core)


INSTALLATION
------------

### Install via Composer (recommended)

If you use Composer to manage dependencies, edit composer.json as follows.

* Run `composer require --prefer-dist composer/installers` to ensure you have
the composer/installers package. This facilitates installation into directories
other than vendor using Composer.

* In composer.json, make sure the "installer-paths" section in "extras" has an
entry for `type:drupal-library`. For example:

```json
{
  "libraries/{$name}": ["type:drupal-library"]
}
```

* Add the following to the "repositories" section of composer.json:

```json
{
  "type": "package",
  "package": {
    "name": "ckeditor/dialogadvtab",
    "version": "4.8.0",
    "type": "drupal-library",
    "extra": {
      "installer-name": "ckeditor/plugins/dialogadvtab"
    },
    "dist": {
      "url": "https://download.ckeditor.com/dialogadvtab/releases/dialogadvtab_4.8.0.zip",
      "type": "zip"
    }
  }
}
```

* Run `composer require 'ckeditor/dialogadvtab:4.8.0'` to download the plugin.

* Run `composer require 'drupal/ckeditor_advanced_tab:^1.0.0'` to download the
CKEditor Advanced Tab module, and enable it [as per usual](
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).


### Install Manually

* Download the [dialogadvtab](https://ckeditor.com/cke4/addon/dialogadvtab)
CKEditor plugin.

* Extract and place the plugin contents in the following directory:
`/libraries/ckeditor/plugins/dialogadvtab/`.

* Install the CKEditor Advanced Tab module [as per usual](
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

MAINTAINERS
-----------
Current maintainers:

 * Julien de Nas de Tourris ([julien](https://www.drupal.org/u/julien))
