CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
-------------
The GPX Track & Elevation module creates a formatter for file type fields
that are supposed to contain a gpx file.
The formatter will show a map and an elevation profile from the GPX file
using the google maps and visualization api.

The module takes into account only the <trk> and <wpt> tags 
(Tracks and Waypoints) and ignores the <rte> tags (Routes).

The elevations will be parsed from the tags of the gpx file, so this module is
useless if your gpx files does not include the <ele> optional tag.

Given a gpx file the module will consider every <TRK> tag as a single stage of
the whole track: all of them will be represented on the same map, but everyone
will have a dedicated elevation profile named as the tag of the track.

The map and the elevation profiles are linked, so moving the mouse on the
profile will highlight the corresponding point on the map.
It is also possible enabling the link in the reverse way: pointing the mouse
on the track will highlight the corresponding point on the elevation profiles.

Any registered waypoint (<WPT> tag) in the GPX file will appear on the same map
of the tracks.


REQUIREMENTS
------------
No special requirements.


INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
for further information.


CONFIGURATION
-------------
You can configure the  GPX Track & Elevation module from
Home >> Administration >> Configuration >> Web services >> GPX Track & Elevation

Here you will find three configuration tabs:
- Generic Settings
- Tracks Settings
- Waypoints Settings

*****Generic Settings*****
In this tab there are two main section: "General Configuration" and
"Configuration for entity types".
In the "General Configuration" section there are five parameters you can set:
- Enable bidirectional link: if selected, when you move the mouse over the track 
  the corresponding point on the elevation profile will be highlighted.
- Track color: enter a valid RGB color to draw the track on the map.
- Elevation profile color: enter a valid RGB color to draw the elevation profile
- Track stroke: enter a value for the stroke weight
- Map type: select a valid Google Maps map type.
- Google Map API Key: insert your Google API key to be used
- HTTP or HTTPS: select the protocol to be used when using the Google maps API

In the "Configuration for entity types" section you can activate the elevation
profile formatter for each single entity.
Regardles of the selection the formatter will be available only for 
file type fields with cardinality 1.

*****Tracks Settings*****
In this tab there are three parameters you can set.
- Image for track start point: indicates the image to be used to show the start
of a track. If left empty it default to:
  http://www.google.com/mapfiles/dd-start.png
- Image for track end point: indicates the image to be used to show the end
of a track. If left empty it default to:
  http://www.google.com/mapfiles/dd-end.png
- Image for track end point of last track: indicates the image to be used
to show the end of the last track. If left empty it default to:
  http://www.google.com/mapfiles/dd-end.png

When shown on the map, the anchor of the marker is located along the center
point of the bottom of the image.
  
*****Waypoints Settings*****
In this tab you can set images to be used for different waypoint types.
You can only use square images.

The images will be used to show on the maps waypoints whose <type> tag
is the same as the "Waypoint type name" defined in this tab.

You can define a "default" marker type to be used for waypoints without a <type>
tag or with a <type> tag not defined in this form.
If a "default" marker type is not defined the standard
http://www.google.com/mapfiles/marker.png image will be used.

For all the types defined in this page a 30px x 30px marker image will be used
with anchor located in the image center.
