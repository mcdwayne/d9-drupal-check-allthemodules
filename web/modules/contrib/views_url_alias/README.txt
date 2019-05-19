
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Related Issues
 * Notes
 * Todo


INTRODUCTION
------------

The 'Views URL alias' module allows views to be filtered by path aliases.

This module is useful if your website uses heirachical paths. It allows you to
filter and sort a view by URL aliases. When combined with the
Views bulk operation (VBO) module (http://drupal.org/project/views_bulk_operations)
you can apply operations to a specific section of your website based on a
URL alias.

All content entities aliases are supported.


INSTALLATION
------------

1. Copy/upload the view_url_alias.module to the modules directory
   of your Drupal installation.

2. Enable the 'Views URL alia' module in 'Modules'. (admin/modules)

3. Create or view and select 'URL alias' for the field or filter



NOTES
-----

- This module creates and maintains separate 'views_url_alias' table
  to provide clean and fast joins between the primary {type} table and its url
  aliases.


TODO
----

- Support multiple path alias per content entity


AUTHOR/MAINTAINER
-----------------

- Jacob Rockowitz
  http://drupal.org/user/371407
- Kyay Rindlisbacher
  https://www.drupal.org/u/l-four
