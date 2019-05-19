This is a Views Plugin that provides a lightweight accordion node teaser. It 
requires Uikit, which has been adapted for Drupal 8 in the Uikitty base theme. 
The module comes with a view definition for out-of-the-box implementation.

Installation instructions:

1) Make sure your Article content type has the standard "Title", "Body" and 
   "Image" fields. These are Drupal defaults, no worries unless you have deleted 
   them for some reason.
2) Install and activate the module.
3) Include the Uikit accordion component in your front twig template. If your 
   theme is based on Uikitty, you may use the following snippet: 
   {{ attach_library('uikitty/accordion') }}. As always, clear caches too.
4) Demo view page has relative path: /uikit-node-accordion.
