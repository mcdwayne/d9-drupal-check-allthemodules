
BYU Hero Node
=============

## Description and Usage

This module adds the BYU hero content type. This particular content type is displayed in the form of a BYU hero component, which is defined by the BYU Hero module. This module also adds a default view simply called "BYU Hero." This view will add the nodes you create of the BYU hero content type. By default, the view will only show one BYU hero component, but you can adjust that if desired by editing the view itself.

The view will display the nodes you create in the "Hero Side Image Style 2" display, and outside the view they display in the default display. The type of BYU hero component that gets displayed depends on what you put in the "Classes" field. The classes are the same as they are described in the README for the BYU Hero Component module. The classes in that field get added to the css of the byu-hero-banner element.

## Classes

**Note:** byu-component-rendered is required for every BYU hero banner.

* title-only - Only displays the title and image/video source. Used for headlines.
* video-hero - Gives support for embedded video.
* side-image - Displays an image on the left.
  * image-style-2 - An alternate side image view. Also can be used to embed videos.
* transparent-overlay - Puts the image in the background with a transparent block holding the other parts of the banner on the left.
  * byu-hero-right - Right aligns the transparent overlay.
* full-screen - Displays the image in the background, and stretches the transparent overlay to fit the width of the viewport.
  * light-image - Adjusts the formatting for bright-colored images.
  * dark-image - Adjusts the formatting for dark-colored images.

Demos for these banners are found here: https://cdn.byu.edu/byu-hero-banner/1.x.x/demo/index.html

### Ideal Image Sizes

* title-only - Width: Viewport width, Height: 280px
* video-hero, transparent-overlay, full-screen - Width: Viewport width, Height: 680px
* side-image - 570px x 320px
