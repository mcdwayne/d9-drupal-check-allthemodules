INTRODUCTION
------------
Module provides the field which allows editors to draw shapes(circles, lines,
polygons, polylines, markers, rectangles) on Maps. Current version supports
Google maps,but more map providers(Yandex, OSM) can be added in future.

Features:
1.New field "map_object_field"
2.Configurable widget, you can configure types and number of objects available
for drawing.
3.Configurable formatter, admin can define width and height of displayed map.
4.Every drawn shape has title and description which are displayed inside
infowindow on click event(see screenshot).
5.Editors can define fill and stroke colors for the shape


REQUIREMENTS
------------
Drupal 8

INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------
 * Get Google Map API key
(https://developers.google.com/maps/documentation/javascript/get-api-key),
then visit /admin/configuration/map-object-field and set the key.
This is not necessary step and Google Map API can work without the key,
nevertheless Google recommends to use keys.
More info: (https://developers.google.com/maps/documentation/javascript/usage)

 * Attach field to any entity

 * Configure field widget

 * Configure field formatter

-- CUSTOMIZATION --

* TBD

-- TROUBLESHOOTING --

* TBD

-- FAQ --


-- CONTACT --

Current maintainers:
* Dmitry Kazberovich - https://www.drupal.org/user/2720959
