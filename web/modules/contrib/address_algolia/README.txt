
-- SUMMARY --

This module provides integration with the Algolia Places library to improve
usability of your address field types.

Currently this module only provides autocompletion of some address form
components. Patches to implement new features are always welcome.

-- REQUIREMENTS --

The module "address" and its dependencies.


-- INSTALLATION & CONFIGURATION --

* Install the module as usual
* Go to the field widget configuration, and select the widget "Address Algolia".
* Open the settings for this widget and mark the checkbox "Use autocompletion from the
Algolia Places library".

-- USAGE --

* With the previous settings in place, when you are creating content with this field, you
will now see that the first line of the address field has been converted into an autocomplete
that will populate some other fields (line2, post code and city).

-- ROADMAP --

These are future features that may be implemented at some point:
* On autocompletion, integrate with some countries specific components and elements (such as
Spanish provinces that are shown on a select, etc).
* Integrate with geofield to be able to choose lat/long entering the address on the autocomplete.
(This will probably overlap with the D8 version of https://www.drupal.org/project/geofield_gmap)
* Allow multiple fields on the same page.
* Replace all selects from the Address module with autocomplete elements (country lists,
provinces, etc)
 * ...

-- SUPPORT, FEATURE REQUESTS AND CONTRIBUTION --

* https://www.drupal.org/project/issues/search/address_algolia
