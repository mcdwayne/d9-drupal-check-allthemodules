Drupal Leaflet Widget using Leaflet.pm
==========================================

Allows the selection of geofield values via leaflet maps.

![](demo/demo.gif)

It supports drawing of
- Markers
- Polyline
- Rectangle
- Polygon / Multipolygon

It has
- Edit Mode
- Drag Mode
- Cut Mode
- Delete Mode

The GeoJSON Data is saved in the geofield module.

![](demo/settings.gif)


For drawing of layers it used the library [Leaflet.PM](https://github.com/codeofsumit/leaflet.pm) instead of Leaflet.draw:

Because [Leaflet.Draw](http://leaflet.github.io/Leaflet.draw/docs/leaflet-draw-latest.html) does not support MULTIPOLYGON, the Leaflet.PM library was used.


Original author
---------------

Originally developed [on GitHub](https://github.com/bforchhammer/leaflet_widget)
by bforchhammer.
