--------------------------------------------------------------------
Description
--------------------------------------------------------------------

dRealty Field Map is a sub-module for Real Estate dRealty Module to help improve the process of creation and mapping of Drupal's entity fields to the data from RETS system.

dRealty Field Map offers the following functionality:

- Provides an interface to select multiple RETS Property resource fields for mapping with Drupal's fields (each list of fields is specific to a certain class, e.g. Residential, Commercial, etc.).
- At the moment, the same fields from RETS system can be mapped to different drupal fields. That means, every time you get to the Field selection form, it doesn't do any validation/restriction check
 to see if the field has already been mapped to anything. Use case example: you can map RETS StreetName field to a taxonomy reference type field for search/filtering purposes, and you can also create
 a Postal Address type field(will need to be mapped manually from entity edit form) and reuse the same StreetName field to be a part of the street address subfield.
- Current implementation allows for mapping of RETS fields to Number type (integer, decimal, float), List type, Taxonomy Type, and Boolean type fields in Drupal.

** This project is still very much in alpha. It can be used on production sites to facilitate fields creation/mapping, but you have to be mindful of which fields you're mapping with which field type.
** It still needs to be updated with some tweaks to improve user experience.
** Style needs to be implemented

----------------------------------
Installation
-----------------------------------

* To install, follow the general directions available at:
http://drupal.org/getting-started/5/install-contrib/modules

--------------------------------------------------------------------
Configuration
--------------------------------------------------------------------

After you enable this module, the configuration form can be found under:
  Drealty => Drealty Connections => Configure Listings (for your specific connections created). Next to each resource class you will see a "MAP FIELDS" link, which will take you to a field mapping form
  of the specific class.

When mapping the fields through this module after the initial mapping has been done, the fields that have been mapped at least once for each particular resource/class will have a background color applied to them
to facilitate visual distinction when adding more field mappings later on.
