--- README  -------------------------------------------------------------

Field Slideshow

Provides a Slideshow format for displaying Image fields,
using the JQuery Cycle 2 plugin.

Compared to Views slideshows, building the slideshow from multiple nodes,
this module builds it from a single node, if you're using a multi-valued
Image field.

--- INSTALLATION --------------------------------------------------------

1 - use composer require 'drupal/field_slideshow:^3.x'
2 - Download the JQuery Cycle 2 plugin here :
    https://github.com/zakgreene/cycle2/
    (It is fork from original Cycle2 repo
    which support the latest jQuery version)
    (don't choose the Lite version), and move the downloaded
    jquery.cycle2.min.js file into /libraries/jquery.cycle2/
3 - Optionally download swipe plugin from
    http://malsup.github.io/min/jquery.cycle2.swipe.min.js
4 - Install Drupal Colorbox module if you want to use colorbox modal.

--- USAGE ---------------------------------------------------------------

1 - Enable Field Slideshow at /admin/modules.
2 - Create or edit a content type at /admin/structure/types and
    include an Image field.
3 - Edit this image field, so that multiple image files may be added
    ("Number of values" setting at admin/structure/types/manage/
    {content type}/fields/{field_image}).
4 - Go to "Manage display" for your content type
    (/admin/structure/types/manage/{content type}/display) and
    switch the format of your multiple image field from Image to Slideshow.
5 - Click the settings wheel in the slideshow-formatted multiple
    image field to edit advanced settings.
6 - Save! and here you go.
