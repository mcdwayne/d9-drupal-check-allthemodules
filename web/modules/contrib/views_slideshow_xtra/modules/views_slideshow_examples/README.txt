Views Slideshow Examples
========================

Project site: http://drupal.org/project/views_slideshow_xtra

Code: https://drupal.org/project/views_slideshow_xtra/git-instructions

Issues: https://drupal.org/project/issues/views_slideshow_xtra

What Is Views Slideshow Examples?
---------------------------------

The Views Slideshow Examples module is a sub-module of the Views Slideshow Xtra project. 
The module serves as a "Starter Kit", in that enabling the module creates working
slideshows, which can be used as a starting point for your slideshows. Currently
this includes two example slideshows. The first example is of a basic slideshow
created using just the Views Slideshow module.  The second example uses the
Views Slideshow Overlay module, providing an example of a slideshow that has
multiple overlays. 

Installation
------------

To install the Views Slideshow Examples module, download the Views Slideshow Xtra module
(https://www.drupal.org/project/views_slideshow_xtra, currently 8.x-4.0 Alpha),
and install the Views Slideshow Examples module in the normal way from admin/modules,
or using Drush (drush -y en views_slideshow_examples).  DO NOT install the
Views Slideshow Xtra module, it is just a placeholder module for the included sub-modules.

When the module is installed, it creates a Slide content type, Slideshow taxonomy, 
Slideshow view, and example Slide content.  The Slideshow view has two displays,
one for each of the example slideshows.

The Slide content type is pre-populated with Slide nodes for the two examples.
The Slideshow taxonomy has a term for each slideshow.

The basic slideshow example will be found at the path /basic-slideshow.  The overlay
slideshow example will be found at the path /overlay-slideshow.

Un-installing
-------------

Removal of the Slide content type, Slideshow taxonomy, and Slideshow view was
omitted from this module's uninstall process, with the idea that users could enable
the module and use them for their own slideshows, after deleting the example content.
Thus, uninstalling this module does not delete the Slide content type, Slideshow
taxonomy, or Slideshow view.  The example slide content may be manually deleted from
Admin >> Content, and the Slide content type may be deleted from
Admin >> Structure >> Content Types.  The Slideshow view may be deleted from
Admin >> Structure >> Views.

Custom CSS
----------

If this module is disabled, and you want to continue using the slideshow it
creates, be sure to copy the CSS in views_slideshow_xtra_example.css
to one of your site's CSS files, as this CSS file will no longer be available,
once this module is disabled.

