
ABOUT
Provides Mason integration to create a perfect gapless grid of elements.
This is not Masonry, or Isotope or Gridalicious. Mason fills in those ugly gaps,
and creates a perfectly filled space.

The module provides a Views style plugin to return results as a Mason grid.


FEATURES
o Lazyloaded inline images, or CSS background images.
o Easy captioning.
o A few simple box layouts.
o Supports Colorbox/Photobox/Photoswipe via Blazy formatters.
o Views style plugin.


INSTALLATION
Install the module as usual, more info can be found on:
http://drupal.org/documentation/install/modules-themes/modules-7


USAGE / CONFIGURATION
- Visit admin/structure/mason to build a Mason grid.
- Visit admin/structure/views, and create a new page or block with Mason style.
- Use the provided sample to begin with, be sure to read its README.txt.


REQUIREMENTS
- Blazy module.
- Views module.
- Mason library:
  o Download Mason archive from https://github.com/DrewDahlman/Mason
  o Extract it as is, rename "Mason-master" to "mason", so the assets
    are available at:

    /libraries/mason/dist/mason.min.js


HOW DOES IT WORK?
Mason works by flowing a grid of floated elements as a normal CSS layout, then
measuring the dimensions of the blocks and total grid area. It then detects
where gaps are and fills them.

It uses fillers to fill in gaps. Fillers are elements that you can define or it
will reuse elements within the grid. If fillers are ugly, use Promoted option
with proper calculation and most likely a couple of trials and errors. Be sure
the amount of visible mason boxes are matching the amount of Promoted items.


TIPS:
o The key is: "floated elements as a normal CSS layout".
o Do not rely on random sizes, use proper calculation, or a simple math.


PROMOTED OPTION
You can tell mason to promote specific elements if you want by assigning a class
and telling mason in the config. Notice in this grid nothing changes on refresh.


ROADMAP
o Support lazyload for inline images. Currently only CSS background images.
o Support Responsive image to have various sizes, only if doable.


CURRENT DEVELOPMENT STATUS
A full release should be reasonable after proper feedbacks from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.

Alpha and Beta releases are for developers only. Be aware of possible breakage.

However if it is broken, unless an update is explicitly required, clearing cache
should fix most issues durig DEV phases. Prior to any update, always visit:
/admin/config/development/performance


KNOWN ISSUES
o Not compatible with Responsive image OOTB.


AUTHOR/MAINTAINER/CREDITS
gausarts


READ MORE
See the project page on drupal.org: http://drupal.org/project/mason

See the Mason JS docs at:
o https://github.com/DrewDahlman/Mason
o http://masonjs.com/
