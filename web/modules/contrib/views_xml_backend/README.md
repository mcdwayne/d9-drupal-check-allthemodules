Views XML Backend
=================


INTRODUCTION
------------

Views XML Backend is a Views 3 plugin that adds native XPath 1.0 query
generation. It allows you to parse XML/(X)HTML with XPath selectors
using Views' graphical query builder.

 * For a full description of the module, visit the project page:
https://www.drupal.org/project/views_xml_backend
 * To submit bug reports and feature suggestions, or to track changes:
https://www.drupal.org/project/issues/views_xml_backend


REQUIREMENTS
------------

This module requires the following modules:

 * Views (https://drupal.org/project/views)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
See: https://drupal.org/documentation/install/modules-themes/modules-7
for further information.
 * You may want to enable Views and Views UI modules, to facilitate
configuration.


CONFIGURATION
-------------

### Create an XML Backend View

    1. Click to Structure > Views > + Add new view or go to
    /admin/structure/views/add.
    2. Under the 'View settings' fieldset select the 'XML' option for
    the 'Show' field.
    3. In the View UI click the'Advanced' fieldset title in the third
    column.
    4. Under the 'Other' heading click the 'Query settings' 'settings'
    link.
    5. Enter the XML file external URL or internal path.
    6. Set the row Xpath (e.g. /first_path/second_path).

### Configuration options available in settings.php.

```php

// Set the amount of time, in seconds, that cached files can exist.
// Defaults to one week. $settings['views_xml_backend_expire'] = 604800;

// Set the directory that cache files are stored in. Defaults to
// 'public://views_xml_backend'.
// $settings['views_xml_backend_cache_directory'] =
// 'public://views_xml_backend'; ```


MAINTAINERS
-----------

Current maintainers:
 * Chris Leppanen (twistor) - https://www.drupal.org/u/twistor
 * Dan Gurin (dangur) - https://www.drupal.org/u/dangur
 * Peter Sawczynec (xpsusa) - https://www.drupal.org/u/xpsusa

This project has been ported to Drupal 8 by:
 * CivicActions
CivicActions empowers U.S. government agencies to deliver delightful
digital experiences that are as innovative and rewarding as popular
online and mobile consumer services.
https://www.drupal.org/civicactions
