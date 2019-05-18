Imager Module
=============

imager.js is a JavaScript library that both displays and edits images.  When
a user hovers over a thumbnail, imager.js pops up a window that displays a
larger image.  This works similar to the way lightbox, colorbox and other
similar packages work.  Users can drag the image around using the mouse.  The
mouse-wheel is used to zoom in and out.  Zooming is centered around the mouse
cursor.

In addition, imager.js can rotate the image +/- 90 and save the image.  

  - rotate +/-90 degrees
  - crop
  - brightness/contrast
  - hue, saturation, luminosity

imager.js uses the imgareaselect.js library to select the area to crop.

Dependencies
------------
- Modules
  - entities
  - file_entity
- Libraries 2.x
- jQuery plugins:
  - imgAreaSelect:
    + Website: http://odyniec.net/projects/imgareaselect
    + Download: http://odyniec.net/projects/imgareaselect/jquery
    .imgareaselect-0.9.10.zip

Basic Design
------------

The JavaScript is based on an example by Gavin Kistner (http://phrogz
.net/tmp/canvas_zoom_to_cursor.html).  All images are drawn using the
transform matrix such that the JavaScript context.drawImage() function is
always called with the simplest of arguments
   Ex: context.drawImage(image,0,0);
The context.save() and context.restore() functions are not used except to
backtrack to a previous state.  This means images are translated, scaled and
rotated and these changes accumulate in the transform matrix.
Installation
------------

Configuration
-------------

Using Imager module
-------------------
