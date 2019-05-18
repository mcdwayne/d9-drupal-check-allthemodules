-- SUMMARY --

The Embedly Filter module provides a text filter to embed URLs using the Embedly
service.

For a full description of the module, visit the project page:
  http://drupal.org/project/embedly_filter

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/embedly_filter

-- DEPENDENCIES --

* Embedly (http://drupal.org/project/embedly)

-- INSTALLATION --

* Install as usual, see https://www.drupal.org/node/1897420.

-- CONFIGURATION --

* Enable the Embedly filter for any text format at
  Administration » Configuration » Content authoring.

-- TROUBLESHOOTING --

* If the filter does not work, check the following:

  - Check that the filter is enabled for your text format.

  - Make sure the shortcode is in the following format:
    [embedly:http://example.com]

  - Make sure you have entered a valid API key.

-- THEMING --

* Create a template named embedly.html.twig in your theme to customize
  the display of the embedded content.

* Clear your cache.

-- CONTACT --

Current maintainers:
* Irfaan Chummun - http://drupal.org/user/3533879
