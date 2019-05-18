This module integrates the CKEditor [Text Transform](https://ckeditor.com/addon/texttransform) plugin.

This plugin adds support for transforming selected text to new cases. You can 
transform selected text to uppercase, lowercase or simply capitalize it.

DEPENDENCIES
-------------
This module requires installing the CKEditor "Text Transform" plugin.

HOW TO INSTALL DEPENDENCIES VIA COMPOSER:

1. Add ckeditor/texttransform repositories to your `composer.json`.

```
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "ckeditor/texttransform",
            "version": "1.1",
            "type": "drupal-library",
            "dist": {
                "url": "https://download.ckeditor.com/texttransform/releases/texttransform_1.1.zip",
                "type": "zip"
            }
        }
    }
]
```

You can find more versions at the [Text Transform](https://ckeditor.com/addon/texttransform) plugin page.

2. Execute `composer require ckeditor/texttransform`
3. Make sure there is the file `libraries/texttransform/plugin.js`.

HOW TO INSTALL DEPENDENCIES MANUALLY:
1. Download the plugin on the [Text Transform](https://ckeditor.com/addon/texttransform) plugin page.
2. Create a libraries folder in your drupal root if it doesn't exist
3. Extract the plugin archive in the librairies folder
4. Make sure there is the file `libraries/texttransform/plugin.js`.

HOW TO USE
-----------
- Go to the format and editor config page and click configure on the format you want to edit : http://example.com/admin/config/content/formats
- Add the desired transform plugin buttons into your editor toolbar.
- Save, that's it, transformation buttons will now be available for use.
