CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

A Drupal 8 module providing a field formatter plugin to generate images from
the content of string fields, e.g. node titles and taxonomy term names, for
display in views, view_modes and templates.

Example: You need a lot of nodes to have placeholder images. You could add an
image field to the content type and then generate those images, attach them as
files and so on. There are modules that can do that. Text2Image lets you select
an existing string field, e.g. node title, and view it as an image by rendering
it using the text2image formatter in your view/display/code.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/text2image

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/text2image


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * PHP GD library with PNG support -
   https://secure.php.net/manual/en/book.image.php


INSTALLATION
------------

 * Install the Text2Image module as you would normally install a contributed
   Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

Font:
This module provides some free fonts and the default path upon installation
points to  `modules/custom/text2image/fonts/liberation-fonts-ttf-2.00.1`

[Liberation Fonts](https://pagure.io/liberation-fonts)

Enter a path to your own installed truetype fonts in the settings tab and save.
On a Linux server this is usually: `/usr/share/fonts/truetype/`

Go to the fonts tab to select your preferred fonts from all the available fonts.
It is the list of preferred fonts that will show as options in the 'font'
dropdown.

Should you add, move or remove fonts you should repeat the above steps and you
may need to reconfigure any fields that were using those fonts.

Image:
Enter your preferred default font-size, image height and width.

Default colors for the image background and text are optional.

Sampler:
The sampler tab lets you play around with options and generate a preview image.

It features a color picker where you can find color codes to use when entering
Text2Image settings anywhere.

Usage:
The Text2Image formatter is available as an option for any string field type
from Content Type Display Manager and Views Display Field configuration.

The Text2Image settings form lets you select font, font-size, image height,
width, background and text colors. If colors are left blank, a random
background color with random contrasting text color will be generated.

The display configuration form lets you select an image style and whether to
link the image to it's parent content.

The Text2Image format can be used programmatically, e.g. in a preprocess
function. The "image_style" and "image_link" settings can be sent via the
render array.

```
$vars['title2image'] = $variables['node']->title->view([

        'type' => 'Text2Image',

        'label' => 'hidden',

        'settings' => array(

          'image_style' => 'medium',

          'image_link' => 'content',

        ),

      ]

    );

$vars['title2image'] = $variables['node']->title->view([

        'type' => 'Text2Image',

        'label' => 'hidden',

        'settings' => array(

          'image_style' => 'original',

          'image_link' => '',

        ),

      ]

    );
```

When a string that uses the Text2Image format is changed, the image and image
style should be regenerated. Clear cache and flush the image style if you find
this is not the case.

When any selected image style is altered, Text2Image images will remain but the
derivative images will be deleted. If the image style is deleted you may need
to revisit Text2Image formatted fields and select another image style.

When changing a field's Text2Image settings in the Views UI, it may be necessary
to flush the image style and/or clear views cache.


MAINTAINERS
-----------

 * Monique Szpak (zenlan) - https://www.drupal.org/u/zenlan
