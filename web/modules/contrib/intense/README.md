# About Intense images

Provides a simple Intense image field formatter. A stand alone javascript
library for viewing images on the full screen. Using the touch/ mouse position
for panning.

All styling of image elements is up to the user, Intense.js only handles the
creation, styling and management of the image viewer and captions.

## REQUIREMENTS
* Intense library:
  + Download Intense archive from
    [Intense images](https://github.com/tholman/intense-images/)
  + Extract it as is, rename **intense-images-master** to **intense"**, so the
    asset is available at:
    **/sites/../libraries/intense/intense.min.js**

* [Blazy](http://dgo.to/blazy).


## INSTALLATION
Install the module as usual, more info can be found on:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules


## USAGE / CONFIGURATION

* Enable this module and its dependency, core image and Blazy modules.
* At **/admin/config/people/accounts/fields**, or **/admin/structure/types**,
  or any fieldable entity, click **Manage display**.
* Under **Format**, choose blazy-related formatters:
  **Blazy**, **Slick carousel**, etc. for image field.
* Click the **Configure** icon.
* Under **Media switcher**, choose **Image to Intense**. Adjust the rest.
* The same option is also available at Blazy Filter for Blazy 8.x-2.x.


### CUSTOM/ VIEWS GALLERY
Assumes a gallery for multiple nodes/ entities within a Views block/ page.
Use Blazy Grid Views style for easy and responsive grid building.

1. Add a class `intense-gallery` to Views container via Views UI:

   **Advanced** > **CSS class**

   If not using Blazy Grid.

2. Choose ONE of the options below for images which is easier at Views UI:
   1. Add a class `intense` to individual IMG with attribute `[data-image]`
      pointing to the original/ largest image.
   2. Or wrap each IMG with a link which has a class `intense` with attribute
      HREF pointing to the original/ largest image.
   3. Or use Blazy/ Slick formatters, choose **Image to Intense** under
      **Media switcher**. Adjust the rest. Be sure to leave
      **Use field template** under **Style settings**  unchecked. If checked,
      the gallery is locked to a single entity, that is no Views gallery,
      but gallery per field.

Use **Image URL formatter** with Views custom rewrite as needed if not using
Blazy/ Slick formatters.


## FEATURES
* Has no formatter, instead integrated into **Media switcher** option as seen at
  Blazy/ Slick formatters, including Blazy Views fields for File Entity and
  Media, and also Blazy Filter for inline images.
* Supports for IMG.intense, or IMG|DIV CSS background wrapped in a link.
* Fullscreen video if Media module is installed.
* Next and previous arrows. Disclaimer, this is module feature, not original
  library implementation. If any issue, use CSS to hide the arrows:

  ````
  body > figure .intensed__arrow (display: none;)
  ````


## MIGRATION/ UPGRADING FROM 1.x to 2.x
Migration is not supported, yet. Consider 2.x for new sites only.
We haven't provided an upgrade path for now, yet.


## INTENSE 2.x CHANGES
1. Removed Intense formatter. Now Intense is just a Media switcher option at
   blazy-related formatters. Meaning Intense is available for free at Blazy,
   Slick carousel, Views field, including Blazy Filter for inline images.
2. Removed **theme_intense()** for **theme_blazy()**.


## AUTHOR/MAINTAINER/CREDITS
gausarts

[Contributors](https://www.drupal.org/node/2647200/committers)


## READ MORE
See the project page on drupal.org:

[Intense module](http://drupal.org/project/intense)

See the Intense images docs at:

* [Intense at Github](https://github.com/tholman/intense-images/)
* [Intense website](http://tholman.com/intense-images)
