
# ElevateZoomPlus
Integrates ElevateZoom Plus with Slick Carousel and lightboxes.


## REQUIREMENTS
1. [Slick 2.x](http://dgo.to/slick) (Beta2+)
2. ElevateZoomPlus library:
   * [Download ElevateZoomPlus](https://github.com/igorlino/elevatezoom-plus)
   * Extract it as is, rename it to **elevatezoom-plus**, so the assets are at:  
     + **/libraries/elevatezoom-plus/src/jquery.ez-plus.js**

     If using Composer it will be:
     + **/libraries/ez-plus/src/jquery.ez-plus.js**

     Both are supported.
3. If using Slick Carousel with asNavFor, any lightbox for **Media switcher**.
   If not, requires **Image to Elevatezoomplus**.


## INSTALLATION
Install the module as usual, more info can be found on:

[Installing Drupal 8 Modules](https://drupal.org/node/1897420)

## USAGE
1. Install ElevateZoomPlus UI, visit **/admin/config/media/elevatezoomplus**.
2. Use blazy-related formatters: Blazy, Slick, GridStack, etc.
3. Add an **ElevateZoomPlus** optionset, image styles and a lightbox.


### USAGE VARIANTS
1. Slick Carousel **with** asNavFor integrates with any lightbox, recommended.
2. Slick Carousel **without** asNavFor require:  
   + `slidesToShow > 1`.
   + **Image to ElevateZoomPlus** under **Media switcher**
2. Blazy Grid, Gridstack, etc. require:  
   + **Image to ElevateZoomPlus** under **Media switcher**   

With **Image to ElevateZoomPlus**, no lightbox behavior, just a full screen
video if available.


### IMAGE STYLES
We don't add more image styles, instead re-using existing ones regardless names:

1. Slick Carousel **with** asNavFor:
   1. **Image style** for the main preview image (visible one at a time).
   2. **Thumbnail style** for the gallery thumbnails (visible multiple).

2. Slick Carousel **without** asNavFor,  Blazy Grid, Gridstack, etc.:
   1. **Image style** for the gallery thumbnails (visible multiple).
   2. **Thumbnail style** for the main preview image (visible one at a time).

And both uses **Lightbox image style** for the largest zoomed image.


**Important!**

If using Slick Carousel with asNavFor, you can choose any lightbox.
If not, just a static stage, be sure to choose **Image to ElevateZoomPlus**
to sync the main preview and its thumbnails.

For the library usages, please consult their documentations. This module only
provides and facilitates integrations.


## FEATURES
* Has no formatter, instead integrated into **Media switcher** option as seen at
  Blazy, Slick, Gridstack formatters.
* Thumbnail gallery thanks to Slick asNavFor.  
* Supports video as a full screen video, if using **Image to ElevateZoomPlus**.
* Integrates with any lightbox supported by Blazy if using Slick with asNavFor.


## KNOWN ISSUES/ LIMITATIONS/ TROUBLESHOOTINGS
* ElevateZoomPlus is not a lightbox, but treated so at Blazy internally. Do not
  expect regular lightbox features like captions, etc.
* Not tested with all blazy-related features/ formatters, yet.
* Best with similar aspect ratio.
* Use Blazy lazyload for Slick Carousel, if any issue. Edit the working
  Optionset, and change its **Lazy load** option to **Blazy**.


## SIMILAR MODULES
* [Imagezoom](http://dgo.to/imagezoom)

  The main difference is it has nothing to do with Slick/ blazy-related modules,
  and has its own gallery. While this module makes use of Slick Carousel.


## AUTHOR/MAINTAINER/CREDITS
* [Gaus Surahman](https://drupal.org/user/159062)
* [Committers](https://www.drupal.org/node/3039369/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org:

[ElevateZoomPlus module](http://drupal.org/project/elevatezoomplus)

See the ElevateZoomPlus JS docs at:

* [ElevateZoomPlus website](https://igorlino.github.io/elevatezoom-plus/)
* [ElevateZoomPlus at Github](https://github.com/igorlino/elevatezoom-plus)
