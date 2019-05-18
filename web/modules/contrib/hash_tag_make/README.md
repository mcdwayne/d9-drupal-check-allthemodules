INTRODUCTION
------------

# Hash Tag Make

## Description
This module provides a text format filter which turns strings beginning with "#" into links. This module uses preg_replace() to alter the value of the Text Format value. Uses Unicode Scripts.


REQUIREMENTS
------------

This module requires the following modules:

 * Node
 * <a href="https://www.drupal.org/docs/8/core/modules/search" title="Drupal Search Module">Search</a> core module
 * <a href="https://www.drupal.org/docs/8/core/modules/ckeditor">CKEditor</a> core module
 * <a href="https://www.drupal.org/docs/8/core/modules/filter">Filter</a> core module


INSTALLATION
------------

1. Install this module.
2. Edit your desired text editor at /admin/config/content/formats.
3. Check the Hash Tag Make Filter checkbox under Enabled Filters.
4. Configure form at /admin/config/tinsel-suite/hash-tag-make.

KNOWN LIMITATIONS
------------

1. Does not work with "Common" script.
2. Does not work with "RTL" scripts.


MAINTAINERS
-----------

Current maintainers:
 * Preston Schmidt - https://www.drupal.org/user/3594865
