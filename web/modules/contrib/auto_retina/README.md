# Drupal Module: Auto Retina
**Author:** Aaron Klump  <sourcecode@intheloftstudios.com>

You may also visit the [project page](http://www.drupal.org/project/auto_retina) on Drupal.org.

##Summary
**Extends core image styles/effects by providing retina versions of any defined style, by simply adding `@2x` before the file extension, e.g. `some-great-file@2x.png`.  The resulting image is double as wide as the width defined in the image style effect.  Can be configured to allow for more than one magnifier, e.g. `@1.5x` `@2x`.  Supports multiple toolkits including GD and ImageMagick.**

_**This module can't do magic**, so be aware that all source image widths should be at least double the width of your image style effect (for a 2x magnification).  See the section "How Wide?" and "Maximizing Quality" below for more info._

## What this module is not

* This module will not detect retina devices.
* This module will not output html tags for your images.
* This module does not handle the front end of retina image handling.

## What this module is

It simply provides the retina version of every image style you define, with no extra work on your part.  Supports more than one multiplier, so you can have `@.75x`, `@1.5x`, `@2x`, etc, on any given image style.

## Requirements

1. Depends on the Drupal 7 core image module.
1. The image style quality module adds better control of the retina output and is highly suggested.

## Installation

1. Install as usual, see [http://drupal.org/node/70151](http://drupal.org/node/70151) for further information.

## Configuration

1. This module leverages the `administer image styles` permission for making configuration changes.
1. The suffix can be changed from the default `@2x` by visiting the configuration page.
1. You can add more than one suffix, if you want to have various levels of magnification, e.g. `@.75x @1.5x @2x`.
1. You can diminish the quality of the retina images, to decrease file size in the admin settings.
1. You can make these settings available to Javascript files by enabling the option in the advanced settings.  This will provide a `drupalSettings.autoRetina` object (In Drupal 7 this is `Drupal.settings.autoRetina`), and can be handy for exposing configuration to your js files that deal with retina images.

## Suggested Use

Once enabled, visit the image url of any derivate image, modify the url by prepending the extension with `@2x`, and you should see the image double in width (so long as your original image is large enough).

As an example, if the following produced a derivate image at 100px wide:

    sites/default/files/styles/thumbnail/public/images/my-great-photo.jpg?itok=hpMKPMBm

You would change the url to this, and see an image at 200px wide:

    sites/default/files/styles/thumbnail/public/images/my-great-photo@2x.jpg?itok=hpMKPMBm

**Please note that you must include the `itok` param when visiting a derivate url for the first time.  This is a requirement of the image module.  See `image_style_deliver()` for more info, reading about `image_allow_insecure_derivatives`.**  The same `itok` is used for the original style derivative and all magnifications thereof.

## How Wide Will the 2x Retina Image Be?

The retina image will not always be twice the width of the standard derivative.  Use this flowchart to understand the logic.

| &darr;  |   |   |
|----------|----------|----------|
| Was the standard image style derivative upscaled?&nbsp;&darr;&rarr; | YES&nbsp;&rarr;  | Same width as the standard derivative.   |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;NO&nbsp;&darr; |   |   |
| Is the original image wider than 2x the standard derivative??&nbsp;&darr;&rarr; | YES&nbsp;&rarr;  | Twice as wide as the standard derivative.  |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;NO&nbsp;&darr; |   |   |
| The width of the original image. |   |   |

## Maximizing Quality

You can locate which original images need to be bigger by reviewing the logs.  Check _Reports > recent log messages_ to look for retina images of poor quality; filter by module = auto_retina and you may see entries indicating which images are too small to be optimum quality.

When a retina image is generated and the quality could be better with a larger source image, an entry will be made in the message log.  In this way you can identify which images need larger originals to provide the best retina quality.  To disable this feature add the following to `settings.php`.

    // Drupal 7
    $conf['auto_retina_log'] = FALSE;
    
    // Drupal 8
    $config["auto_retina.settings"]["log"] = FALSE;

## Image Style Quality Module

In previous versions of this module, in order to affect the quality of retina images you would have to install the [Image Style Quality Module](https://www.drupal.org/project/image_style_quality) module.  This is no longer the case.  However if installed, this module will still modify the style-specific qualities provided by that module.

## Uninstall

Be aware that when you uninstall this module, it will NOT delete the retina derivatives that it has created.

## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fauto_retina).

## Developers

### Drupal 7 Only

`auto_retina_image_style_create_derivative()` is meant to replace `image_style_create_derivative()` in your code, if you wish to take advantage of the functionality of this module at a programattic level.

## Design Decisions/Rationale

We needed a way to have @2x versions of the images on the server without extra work, without any extra configuration, absolutely turn-key.  We did not need the front end handling of the retina images as this was already accomplished by other means.  Current Drupal modules did not provide this use case.

## Similar Modules

1. <https://www.drupal.org/project/hires_images>
1. <https://www.drupal.org/project/retina>
1. <https://www.drupal.org/project/retina_images>
1. <https://www.drupal.org/project/foresight>
1. <https://www.drupal.org/project/resp_img>

##Contact
* **In the Loft Studios**
* Aaron Klump - Developer
* PO Box 29294 Bellingham, WA 98228-1294
* _skype_: intheloftstudios
* _d.o_: aklump
* <http://www.InTheLoftStudios.com>
