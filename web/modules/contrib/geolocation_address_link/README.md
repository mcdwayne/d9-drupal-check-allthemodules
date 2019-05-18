CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Geolocation Address Link module contains functionality to allow a
Geolocation field and an Address field to be linked together. Each entity type
that uses this functionality needs two fields:

 * An address field to allow the user to input an address.
 * A geolocation field that will contain the geocoordinates of the address and
   display them in a map.

Both fields should have the same settings for allowed number of values. If
they have multiple values, the first value in the address field will correspond
to the first value in the geolocation field, etc.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/geolocation_address_link

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/geolocation_address_link


REQUIREMENTS
------------

This module requires the following modules outside of Drupal core:

 * Address - https://www.drupal.org/project/address
 * Geolocation - https://www.drupal.org/project/geolocation


INSTALLATION
------------

Install the Geolocation Address Link module as you would normally install a
contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
further information.


CONFIGURATION
-------------

Configure the functionality at admin/config/system/geolocation-address/settings.
There you can identify which address and geolocation fields should be linked
together.

When the entity is saved, any values in the address field will be geocoded to
automatically update the corresponding geo coordinates in the related
geolocation field.

The assumption is that the geolocation field will be hidden on the edit form
since it will be updated automatically when the entity is saved. Note that any
previous values in the geolocation field will be wiped out by this update!

To display address field values in the map, use either the Map formatter
provided by this module or the Map formatter provided by the Geolocation module,
and use tokens for title and content values. For instance, if the address field
is called `field_address`, the token for a fully formatted address would be
`[node:field_address]`, and the token for the organization name in the address
would be `[node:field_address:organization]`.


This module provides:

 * A geocoder service that can be used to retrieve geocoordinates for an array
   of values structured like the address field. The service will also provide
   boundary coordinates and can compute a suggested zoom level based on the size
   of the area covered in the boundary.

 * A configuration setting to select specific geolocation field(s) that should
   be updated from the values in corresponding address field(s) when the entity
   is saved. This update will also store the boundary and zoom level in the
   field(s).

 * An optional formatter, `Geolocation Google Maps API - Map with dynamic zoom`,
   that overrides the default Google Maps formatter to use the zoom value stored
   in the geolocation field when displaying the map.

The API can be used independently of the included configuration.
Example usage of the API:

 * EXAMPLE 1

```
$geocoder = \Drupal.service('geolocation_address.address2geo');
$address = [
  'country_code' => 'US',
  'administrative_area' => 'IL',
  'locality' => 'Chicago',
  'address_line1' => '111 First Street',
];
$geodata = $geocoder->geocode($address);
```

 * EXAMPLE 2

```
$geocoder = \Drupal.service('geolocation_address.address2geo');

$addresses = $entity->get('field_address')->getValue());
$values = [];
foreach ($addresses as $delta => $address) {
  if ($geodata = $geocoder->geocode($address)) {
    $values[$delta] = $geodata;
  }
}
$entity->set('field_geolocation', $values);
```


MAINTAINERS
-----------

 * Karen Stevenson (KarenS) - https://www.drupal.org/u/karens
