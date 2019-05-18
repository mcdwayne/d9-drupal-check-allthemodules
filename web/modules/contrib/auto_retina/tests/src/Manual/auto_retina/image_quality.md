Test Case ID: image_quality
Author: Aaron Klump
Created: February 27, 2019
---
## Test Scenario

Retina image quality is affected as expected for GD and ImageMagick toolkits as well as using the Image Style Quality module.

## Pre-Conditions
1. Regex is set to {{ _Retina filename regex default }} in [Settings](/admin/config/media/image-styles/auto-retina).
1. Load an image-style derived image in your browser; one who's original image is at least double the size of the style width.
1. Enable the [ImageMagick module](https://www.drupal.org/project/imagemagick) 
1. Disable the [Image Style Quality module](https://www.drupal.org/project/image_style_quality) module.

## Test Data

    _Retina filename regex default: (.+)(SUFFIX)\.(png|jpg|jpeg|gif)$
    GD JPEG quality: 100%
    _GD Compression: 50
    Retina filename suffix: "@2x"
    JPEG Quality Multiplier: 0.5
    Imagemagick Image quality: 90%
    _Imagemagick Compression: 45
    Image Style Quality quality: 80%
    _Image Style Quality Compression: 40

## Test Execution

1. Set the [image toolkit](/admin/config/media/image-toolkit) to `ImageMagick image toolkit`.
1. Set the JPEG quality to {{ Imagemagick Image quality }}.
1. Click _Save configuration_.
1. Visit the [Auto Retina settings](/admin/config/media/image-styles/auto-retina)
1. Set the _Retina filename suffix_ to {{ Retina filename suffix }}
1. Set the _JPEG Quality Multiplier_ to {{ JPEG Quality Multiplier }}
1. Click _Save configuration_.
    - Assert configuration messages appear.
1. Open the image style url in a new window.
1. Delete the style files in _sites/default/files/styles/{style}/_ so you know you are generating.
1. Append {{ Retina filename suffix }} to the URL filename.
1. Reload the browser window to generate the new image.
    - Assert the image is generated at double width.
1. Download the image.
1. Detect the compression using `identify -format '%Q' <filename>` in a terminal window.
    - Assert the compression is {{ _Imagemagick Compression }}.
1. Switch the [image toolkit](/admin/config/media/image-toolkit) to `GD2 image manipulation toolkit`.
1. Set the JPEG quality to {{ GD JPEG quality }}.
1. Click _Save configuration_.
1. Delete the style files in _sites/default/files/styles/{style}/_ so you know you are generating.
1. Reload the browser window to generate the new image.
    - Assert the image is generated at double width.
1. Download the image.
1. Detect the compression using `identify -format '%Q' <filename>` in a terminal window.
    - Assert the compression is {{ _GD Compression }}.
1. Enable the [Image Style Quality module](https://www.drupal.org/project/image_style_quality) module.
1. Visit [Image styles](/admin/config/media/image-styles) and click _edit_ for your style.
1. Add a new _Image Style Quality_ effect set to {{ Image Style Quality quality }}.
1. Delete the style files in _sites/default/files/styles/{style}/_ so you know you are generating.
1. Reload the browser window to generate the new image.
    - Assert the image is generated at double width.
1. Download the image.
1. Detect the compression using `identify -format '%Q' <filename>` in a terminal window.
    - Assert the compression is {{ _Image Style Quality Compression }}.    
