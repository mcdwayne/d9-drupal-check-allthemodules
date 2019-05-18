Responsive Image Batch
======================

What Is This?
-------------

Responsive Image Batch is a helper module for Drupal 8 to speed up creating
responsive image styles. It provides  a single interface for the following
tasks:
- Calculate image sizes in various breakpoints and multipliers for the Picture
element.
- Calculate image sizes for Sizes type responsive images, based on increment
values.
- Name and create multiple image styles and assign image effects to them.
- Map image styles to a "responsive image style". This maps image styles to the
breakpoints and multipliers of the picture element.

How To Use The Responsive Image Batch Interface
-----------------------------------------------

This module provides a single admin interface at:
`/admin/config/media/responsive-image-batch`

Picture elements:
-----------------

1. Fill out a component label (used to name picture mapping and the image
style).

1. Select a base image style. This image style will be used as the base for all
the generated images, meaning that it will copy the exact image effect
settings. The only  exception is that all width and height values of image
effects defining image dimensions will be overridden.

1. Select 'Picture' from the responsive image type dropdown.

1. Select a breakpoint group. Breakpoint groups are
[defined as Yaml files](https://www.drupal.org/documentation/modules/breakpoint)
 in your module or theme. Selecting a breakpoint groups will generate one or
more tables sorted by multiplier. The image style names will consist of a
component name (e.g. article-teaser), breakpoint label (e.g. foobar.mobile),
and a multiplier (e.g. 1.5x).

1. Fill out width and height values for your images. If the breakpoint group
has multipliers defined, these will be automatically calculated (input value *
multiplier). Additionally you can select an aspect ratio per image style. This
allows you to only fill out a single dimension value and all the further
calculating will be done automatically. If you don't want to create an image at
a certain breakpoint, there is an option to exclude it. When the responsive
image is rendered, the image for the breakpoint below will be served.

1. Select a default fallback image to be used when older browsers can't read
the source elements in the picture elements.

1. Select "Generate image styles". This will create all image styles and will
map them to a "responsive image style", which can be used in the image fields
display settings.

Sizes elements:
---------------

1. Fill out a component label (used to name picture mapping and the image
style).

1. Select a base image style. This image style will be used as the base for all
the generated images, meaning that it will copy the exact image effect
settings. The only  exception is that all width and height values of image
effects defining image dimensions will be overridden.

1. Select 'Sizes' from the responsive image type dropdown.

1. Fill out a width and height value for the starter image. You can select an
aspect ratio for your images. This allows you to only fill out a single
dimension value and all the further calculations will be done automatically.

1. Fill out an increment value, type and count. Optionally, you can round up
the numbers for the width values.

1. Fill out a value for the Sizes attribute.

1. Select a default fallback image to be used when older browsers can't read
the source elements in the picture elements.

1. Select "Generate image styles". This will create all image styles and will
map them to a "responsive image style", which can be used in the image fields
display settings.
