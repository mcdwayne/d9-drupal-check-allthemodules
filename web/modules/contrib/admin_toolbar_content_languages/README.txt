-- SUMMARY --

This is the port of Administration menu - Content languages, but it's based on
Administration Toolbar.
The module provides a dropdown menu with available languages when adding content
for all content types that default to the current language in the Administration
toolbar.


-- REQUIREMENTS --

This module requires admin_toolbar and it's sub module admin_toolbar_tools.

The module is intended to be used on a multilingual site.


-- INSTALLATION --

* Install and enable the module as usual. See
  http://drupal.org/documentation/install/modules-themes/modules-8 for further
  information.


-- CONFIGURATION --

* Configure content types

  - For each multilingual content type:

    Set the option "Interface text language selected for page" on the language
    setting tab.


-- TROUBLESHOOTING --

* If the menu does not display:

  - Make sure the "Interface text language selected for page" is set on every
    content type for which you want the menu to be displayed.
