# Leaflet Entity Browser

This module allows you to use a Leaflet map as a view widget in Entity Browser.

Note: This module depends on the following patches to the Leaflet drupal
module (if the issues are not fixed already):

- https://www.drupal.org/node/2852506#comment-12157626
- https://www.drupal.org/node/2904954#comment-12236645
- https://www.drupal.org/node/2904707#comment-12235364

Once applied, enable this module and add an `entity_browser` display to
any view as usual, and select "**Leaflet map for Entity Browser**" as the view
style plugin.

Configure your map display as usual (add a geofield field, define title /
description fields if necessary, etc), and add the "**Entity Browser Bulk Select
Form**" field to the list of fields as well, as with any other entity browser
view display to be used inside an Entity Browser.
