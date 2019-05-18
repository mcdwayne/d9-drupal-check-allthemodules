Responsive Image Automatic
==========================

The responsive image module in core takes a fair amount of configuration and
setup to get working. It requires a group of breakpoints to be setup, image
styles to be created for each breakpoint, a new 'responsive image style' entity
and then updating the image field formatter. That fine grained, while useful
could be tedious for sites with lots of varying image contexts.

This module aims to automatically deliver images of appropriate sizes to the
browser, without the need for any configuration by:

 - Taking existing image styles and proportionally reducing their size for
   smaller screen sizes.
 - Overriding the existing plain image output with fancy picture tags.
 - Including the core picture polyfill for potato web browsers.

The result should be a turnkey solution for saving bandwidth and speeding up
websites.
