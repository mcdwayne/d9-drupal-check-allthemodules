This module integrates the CKEditor "Table Selection" plugin.

This plugin introduces a unique custom selection system for tables to, for example:
- Select an arbitrary rectangular table fragment - a few cells from different rows, a column (or a few of them) or a row (or multiple rows).
- Apply formatting or add a link to all selected cells at once.
- Cut, copy and paste entire rows or columns.

DEPENDENCIES
------------

**Important!** The "Table Selection" plugin requires CKEditor 4.7.
If you are using Drupal 8.3 or less use the patch from https://www.drupal.org/node/2893566.

This module requires to install the CKEditor "Table Selection" plugin.

HOW TO INSTALL DEPENDENCIES VIA COMPOSER:

1. Add ckeditor/tableselection repositories to your `composer.json`.

```
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "ckeditor/tableselection",
            "version": "4.7.2",
            "type": "drupal-library",
            "dist": {
                "url": "https://download.ckeditor.com/tableselection/releases/tableselection_4.7.2.zip",
                "type": "zip"
            }
        }
    }
],
```

2. Execute `composer require ckeditor/tableselection`
3. Make sure there is the file `libraries/tableselection/plugin.js`.

HOW TO INSTALL DEPENDENCIES MANUALLY:
1. Download the plugin on the project page : https://ckeditor.com/addon/tableselection
2. Create a libraries folder in your drupal root if it doesn't exist
3. Extract the plugin archive in the librairies folder
4. Make sure there is the file `libraries/tableselection/plugin.js`.

HOW TO USE
-----------
- Go to the format and editor config page and click configure on the format you want to edit : http://example.com/admin/config/content/formats
- Add the Table plugin button in your editor toolbar.
- Save, that's it, "Table Selection" plugin will automatically start to work once you have the "Table" button in the CKEditor toolbar.

