# Default Content Extra

## Overview

Default Content Extra is a Drupal 8 module that adds "extra", experimental
functionality to the
[Default Content for D8](https://www.drupal.org/project/default_content)
module including:

* Path alias support for nodes and taxonomy term entities
* Automatically delete users 0 and 1 when exporting entities using
`drush default-content-export-references` as these can cause errors when
installing a site with default content.

To use the module, simply enable it and export your content as you would with
the Default Content commands such as:

```
drush dcer node --folder=relative_path_from_docroot
```

The exported content will contain additional data that looks like:

```
"path": {
    "alias": "\/myawesomepath"
}
```

Configuration for enabling the above features is available at
`admin/config/content/default-content-extra`. All features are enabled by
default.

## Why Another Module?

There is an issue \([d.o issue #2710421](https://www.drupal.org/node/2710421)\)
in the Default Content issue queue and it was postponed.

## Todo

* The normalizers use a lot of duplicated code which enhances readability but
could be optimized.
