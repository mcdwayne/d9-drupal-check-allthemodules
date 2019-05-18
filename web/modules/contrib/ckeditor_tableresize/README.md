This module integrates the CKEditor [Table Resize](https://ckeditor.com/addon/tableresize) plugin.

This plugin adds support for table column resizing with your mouse.
Hover your mouse over the column border to see the cursor change to indicate 
that the column can be resized. Click and drag your mouse to set the desired 
column width.

DEPENDENCIES
-------------
This module requires to install the CKEditor "Table Resize" plugin.

HOW TO INSTALL DEPENDENCIES VIA COMPOSER:

1. Add ckeditor/tableresize repositories to your `composer.json`.
For CKEditor 4.7

```
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "ckeditor/tableresize",
            "version": "4.7.2",
            "type": "drupal-library",
            "dist": {
                "url": "https://download.ckeditor.com/tableresize/releases/tableresize_4.7.2.zip",
                "type": "zip"
            }
        }
    }
]
```

For CKEditor 4.6

```
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "ckeditor/tableresize",
            "version": "4.6.2",
            "type": "drupal-library",
            "dist": {
                "url": "https://download.ckeditor.com/tableresize/releases/tableresize_4.6.2.zip",
                "type": "zip"
            }
        }
    }
]
```
You can find more versions at the [Table Resize](https://ckeditor.com/addon/tableresize) plugin page.


2. Execute `composer require ckeditor/tableresize`
3. Make sure there is the file `libraries/tableresize/plugin.js`.

HOW TO INSTALL DEPENDENCIES MANUALLY:
1. Download the plugin on the [Table Resize](https://ckeditor.com/addon/tableresize) plugin page.
2. Create a libraries folder in your drupal root if it doesn't exist
3. Extract the plugin archive in the librairies folder
4. Make sure there is the file `libraries/tableresize/plugin.js`.

HOW TO USE
-----------
- Go to the format and editor config page and click configure on the format you want to edit : http://example.com/admin/config/content/formats
- Add the Table plugin button in your editor toolbar.
- Save, that's it, "Table Resize" plugin will automatically start to work once you have the "Table" button in the CKEditor toolbar.
