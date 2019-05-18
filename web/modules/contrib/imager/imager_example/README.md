## What is imager_example_app.js

imager_example_app.js is a JavaScript library that both displays and edits
images.  When a user hovers over a thumbnail, imager_example_app.js pops up a
window that displays a larger image.  This works similar to the way lightbox,
colorbox and other similar packages work.  Users can drag the image around
using the mouse.  The mouse-wheel is used to zoom in and out.  Zooming is
centered around the mouse cursor.

In addition, imager_example_app.js can rotate the image +/- 90 and save the
image.

  - rotate +/-90 degrees
  - crop
  - brightness/contrast
  - hue, saturation, luminosity

imager_example_app.js uses the imgareaselect.js library to select the area
to crop.

# History

I needed a way to easily view and edit images in a Drupal Media website.
There are modules that use lightbox or colorbox to display images, but they
don't provide a way to edit the image.  In addition they covered the full
window with an overlay, I wanted the underlying window to still be visible
and active.

I also needed simple editing - cropping, rotation, brightness/contrast, and
hue, saturation and luminosity.

I looked around for a simple JavaScript library to edit photos and couldn't
find a good solution.  The fabric.js, darkroom.js, and panzoom.js libraries
do way more than I needed, they didn't do everything I needed, and added a
lot of unnecessary code and complexity.

Online I found a simple example of how to display, pan and zoom images
using the HTML5 canvas.  I decided this was a lot simpler and I would have
full control.

A simple image editor like this is needed, both for Drupal and as a
JavaScript library.  A couple more weeks work (written April 30) and I'll be
ready to present it to the Drupal Media Group.  I suspect there are many
others that have been looking for exactly this type of image viewer/editor.

# JavaScript libraries used

This code is based on an example by Gavin Kistner (http://phrogz
.net/tmp/canvas_zoom_to_cursor.html).  It is very simple in design.  All
images are drawn using the transform matrix such that the JavaScript
context.drawImage() function is always called with the simplest of arguments
   Ex: context.drawImage(image,0,0);
The context.save() and context.restore() functions are not used except to
backtrack to a previous state.  This means images are translated, scaled and
rotated and these changes accumulate in the transform matrix.

imgareaselect.js - provides method for users to select an area to crop.

hoverintent.js - attempts to determine a users intent when hovering with
the mouse.  With hoverintent.js hovering is determined by how many pixels the
mouse is moved in a period of time.  This is a way to detect when the mouse
has almost slowed to a stop.

## Install

Install using one of the following methods:


## How to use it

# Popping up the large image

# Rotating an image

# Zooming

# Panning

# Saving
