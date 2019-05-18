INTRODUCTION
------------
This module allows for export of location-specific data as placemarks in KML and KMZ formats.

KML Format
Keyhole Markup Language (KML) is a file format used to display geographic data in an Earth browser such as Google Earth,
Google Maps and many other mapping/navigation applications.

KML vs KMZ:
KML is a single text file that cannot contain any media resources within it but can contain links to them.
Use it if your placemarks don't need any custom icons from your site OR if you are ready to serve this media
from your site whenever anyone anywhere opens a KML file originating from your site.

KMZ is a zip archive containing a KML file and any media associated with it. Use it if you would like your
placemarks to have custom icons from your web site and you would like those files to be completely self-contained,
i.e. not depending on loading media from your web site.

The module does not have much of UI of its own but adds KML and KMZ format support to the list of formats
supported by the core "REST export" views display and contributed modules like "Views data export".

REQUIREMENTS
------------
1. Drupal 8.
2. Some field containing coordinates to export, like "Geolocation Field" or "Geofield".
3. Core "Serialization" and "RESTful Web Services" modules must be enabled.

INSTALLATION
------------
Install and enable like any other module, there are no 3rd party dependencies or need to use composer.

USAGE
-----
Add a "REST export" display (you might need to enable the core "RESTful Web Services" module first unless
already enabled) to a View that handles nodes (or other data) that include locative infomation.

Under "Format: Serializer | Settings", tick KML or KMZ (or both).

Under "KML settings" you can enter the desired KML document header (can contain document name, description
and/or shared style table in valid KML format) if necessary. The header will be inserted as is between the
opening Document tag and the first Placemark or Folder. The header supports page and site tokens, for example:
<name>[current-page:query:title]</name>

Add Path to your display. Your export will be available by the path you supply here.

If you want to easily attach the export display to another display (so that you could make sure
your view contains exactly the data you want before generating the export file) you might want to use contributed
"Views data export" module/display instead of core "REST export" display. Otherwise you can just place the link
to generate the KML/KMZ file (specified under "PATH SETTINGS | Path" in the view definition) anywhere you need
on your page. Add _format=kml or _format=kmz query parameter to the link, like this:
http://mysite.com/export_placemarks.kmz?_format=kmz

Add fields containing the placemark name, description, coordinates etc. to your view.

Add filters to select exactly the records you need. You can use contextual and/or exposed filters if you need.

Configure "Show: Fields | Settings", choosing the appropriate fields:

With Geolocation field as a location source you are pretty much limited to points. Add your Geolocation field
to your view, choose the "Geolocation tokenized text" field formatter and enter
"[geolocation_current_item:lng],[geolocation_current_item:lat]" as Tokenized text.
Map the field to "Point_coordinates" in "Show: Fields | Settings".

Geofield module supports larger choice of geometries. To export any Geofield data to your KML, add your Geofield
to your view, chose "Raw output" formatter, select "KML" for "Output format", tick "Escape output" box and
map to "Preformatted KML" (default) in "Show: Fields | Settings".
No need to specify what kind of geometry it is - it's all specified in the pre-formatted KML fragment.
You can even have different geometries in different rows.

Alternatively you can untick the "Escape output" box to produce raw coordinates and map the field to either
Point_coordinates or Linestring_coordinates depending on your field content. This will not work
with more complex geometries and you can't have different geomeries in the same view.

You can use any other field (even plain text) for location if it can produce either raw coordinates (for points
and linestrings only) or well-formed (and html-escaped!) KML for any KML-supported geometries.

Another way to use Preformatted KML is to do some fancy styling. If you need to add, say, different background
colors to your balloons/infowindows depending on some data in your database, you can use Rewrite results
to produce something like this:
&lt;Style&gt;
  &lt;BalloonStyle&gt;
    &lt;bgColor&gt;{{ field_color }}&lt;/bgColor&gt;
  &lt;/BalloonStyle&gt;
&lt;/Style&gt;
Note that you have to html-escape your text (replace "<" with "&lt" and ">" with "&gt") - otherwise
the Views engine will strip all your tags as "Unsafe HTML" (see https://www.drupal.org/project/views/issues/2529948
and https://www.drupal.org/project/views/issues/853880).

Name: the placemark name as it's usually shown next to the placemark icon on the map or in the map sidebar ("legend").
It should be plain text (no HTML).

Description (optional): the placemark description as it's usually shown in the placemark "Balloon" or InfoWindow
that appears when you click on the placemark. It can contain HTML tags.

StyleURL (optional): a link to a shared KML Style. If using StyleURLs, you will have to create the Style table by hand
and put it in the KML header setting.
IMPORTANT: icons referenced in this hand-made style table will NOT be packed into KMZ.
If you only need custom icons for your placemarks, you can instead use:

Icon (optional): a path to the icon for this placemark. If KMZ format is chosen, the icon will be packed into the KMZ file.

gx_media_links (optional): a space-delimited list of fully qualified URLs pointing to media files (images, video)
you would like to display in your Baloon/InfoWindow. It seems to be Google Maps-specific, might not work in other apps.
Those media files will NOT be packed into KMZ as Google Maps only expects external/fully qualified URLs here.

Folder (optional): to group your placemarks into KML folders add a field specifying the folder name and map it to Folder
in your "Show: Fields | Settings". All placemarks having the same value for Folder will go the folder by that name.
