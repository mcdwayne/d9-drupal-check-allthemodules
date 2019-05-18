-- SUMMARY --

The Embedly Formatter module provides a text formatter to embed URLs
using the Embedly service. It works with fields of type link.

For a full description of the module, visit the project page:
  http://drupal.org/project/embedly_formatter

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/embedly_formatter

-- DEPENDENCIES --

* Embedly (http://drupal.org/project/embedly)

-- INSTALLATION --

* Install as usual, see https://www.drupal.org/node/1897420.

-- CONFIGURATION --

* Select 'Embedly' as the field format on the Manage display
  page of your entity type.

-- TROUBLESHOOTING --

If the formatter does not work, check the following:

  - Check that the formatter is selected for your field.

  - Make sure the url is a valid url.

  - Make sure you have entered a valid API key.

-- THEMING --

* Create a template named embedly.html.twig in your theme to customize
  the display of the embedded content.

* Clear your cache.

-- CONTACT --

Current maintainers:
* Irfaan Chummun - http://drupal.org/user/3533879
