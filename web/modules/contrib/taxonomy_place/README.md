# Taxonomy Place

This module dynamically creates a 'Place' vocabulary using Address module to manage the geographical information. The taxonomy terms that are created are nested by country, then state/province, then city. The Address module is used as an API to retrieve the right country and province codes and names, and to manage the nesting of countries, provinces, and localities.

Instead of creating a huge taxonomy of all possible locations, many of which may never be used, the module creates the taxonomy as it is required. For instance, users fill out an address field on a node to identify the place the node should be associated with. When the node is saved, the node's entity_reference field is updated to link it to the correct place taxonomy term, adding it to the vocabulary, if it doesn't already exist. In this way, the vocabulary only contains places that have actually been referenced.

## Fields on the taxonomy term

The Place vocabulary terms should include an address field, which is used to manage the way terms are displayed and nested. The module can also populate a description field for each term, a field for a short name, and a sortable name for the term. 

- The description field will be enhanced if the [Wikipedia Client](https://www.drupal.org/project/wikipedia_client) module is enabled. If that module exists, the description field will be populated with a description of the place pulled from Wikipedia.
- The short name field will contain just the place name, to be used where the full name of the place is too long.
- The sortable name field will contain a string value that represents the nested structure of the place names, and can be used in views to ensure the places are sorted correctly without any need for complicated joins.

A further enhancement can be created by enabling and configuring the [Geolocation Address Link](https://www.drupal.org/project/geolocation_address_link) module. If you add a Geolocation field to the taxonomy term, it can be geocoded from the address field data when the term is created, and then used to display a map of the place on the Term page.

## Referencing content fields

Each content type that references the Place taxonomy needs two special fields: 

- One should be the usual entity_reference field to link the node to places in the Place taxonomy.
- The other should be an address field to allow the user to identify a place, even if it is not already in the Place taxonomy. 

The entity_reference field should be hidden on the node form since it will be populated automatically.

## Configuration settings

Go to the confiration form (/admin/config/system/taxonomy-place/settings) to identify the vocabulary and the required fields on the taxonomy term needed to store the Place information, as well as the address and entity_reference fields on the referencing content that will be used to create and update the terms.

## Special notes

The module requires the ability to use optional and hidden address fields so users can select only a country, or only a country and state/province, or omit values that only make sense in postal addresses, like postal codes. To that end, an [Address module patch](https://www.drupal.org/files/issues/2514126-102.field-behavior-settings.patch) is needed.

Once patched, configure the address field so that the administrative area and locality are optional, either hide the organization name or make it optional (and use that field to hold the Place name), then hide all the other address fields.

The module creates new terms if they don't exist, but  does NOT delete terms when the references are removed, since the terms may have been updated to include other information and might be used again in the future.
