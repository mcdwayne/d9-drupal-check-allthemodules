INTRODUCTION
------------

This module makes the iFrame Resizer javascript library available to your Drupal
site. With it enabled, you can "keep same and cross domain iFrames sized to
their content with support for window/content resizing, in page links, nesting
and multiple iFrames."

See the library's homepage for information on its capabilities:
http://davidjbradshaw.github.io/iframe-resizer/

 * For a full description of the module, visit the project page:
   https://www.drupal.org/sandbox/milodesc/2610780

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/2610780


REQUIREMENTS
------------

This module requires the iFrame Resizer javascript library to be installed in
Drupal's "libraries" directory (if that directory does not exist, you'll
need to create it.). Make sure the path to the main library file becomes:
    "libraries/iframe-resizer/js/iframeResizer.min.js"

If your site is iFraming in another site, the child site (i.e. the site listed
in your iFrame tag's 'src' attribute) must include the
js/iframeResizer.contentWindow.js or js/iframeResizer.contentWindow.min.js file
from the iFrame Resizer library.

If your site is being iFramed into another site (i.e. your site is in the
'src' attribute of the iFrame HTML tag), the parent site must include
the js/iframeResizer.js or js/iframeResizer.min.js file from the iFrame
Resizer library.


INSTALLATION
------------

 * Download the library from https://github.com/davidjbradshaw/iframe-resizer

 * Unpack and rename the resulting directory "iframe-resizer" and place it in
   your Drupal installation's "libraries" directory. Make sure the path to the
   library's main file becomes:
   "libraries/iframe-resizer/js/iframeResizer.min.js"

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

 * If your site will be hosting resizable iFrames, the page your site will be
   iFraming in must include the content window file from the iFrame Resizer
   library (i.e. The iFramed page must contain the
   js/iframeResizer.contentWindow.js or js/iframeResizer.contentWindow.min.js
   file.), and the 'This site will host resizable iFrames.' checkbox must be
   checked on your site's configuration page
   (admin/config/user-interface/iframe_resizer).

 * If your site will be hosted within a resizable iFrame, the page hosting
   your site must include the main file from the iFrame Resizer library
   (i.e. The page iFraming in your site must contain the js/iframeResizer.js
   or js/iframeResizer.min.js file.), and the 'Pages from this site will be
   hosted within iFrames that have been made resizable by the iFrame Resizer
   JavaScript library.' checkbox must be checked on your site's configuration
   page (admin/config/user-interface/iframe_resizer).



CONFIGURATION
-------------

 * Configure user permissions in Administration » People » Permissions:

   - Administer the iFrame Resizer module

     Users in roles with the "Administer the iFrame Resizer module"
     permission will see be able to configure the behavior of the iFrame
     Resizer module

 * Customize the module settings in Administration » Configuration » User
   interface » iFrame Resizer

   - The iFrame Resizer Usage fieldset

     At least one of the checkboxes in this fieldset should be checked,
     otherwise the module won't do anything.

   - The Advanced Options for Hosting Resizable iFrames fieldset

     This fieldset will appear if the 'This site will host resizable
     iFrames.' checkbox is checked. It will allow you to override the
     library's default behavior.

   - The Advanced Options for Hosted Resizable iFrames fieldset

     This fieldset will appear if the 'Pages from this site will be hosted
     within iFrames that have been made resizable by the iFrame Resizer
     JavaScript library.' checkbox is checked. It will allow you to override
     the library's default behavior.


MAINTAINERS
-----------

Current maintainers:
 * Patrick Jensen (milodesc) - https://www.drupal.org/user/1498524
