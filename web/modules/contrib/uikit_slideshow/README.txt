This is a Views Plugin that provides a lightweight slideshow with thumbnail
navigation. It is aimed at being used with a Drupal theme that implements Uikit. 
The module comes with a view definition and an image style for out-of-the-box
implementation.

Installation instructions:

1. Make sure your Article content type has the standard "Title", "Body" and
   "Image" fields. These are Drupal defaults, no worries unless you have deleted
   them for some reason.
2. Install and activate the module.
3. Include the line {{ attach_library('uikit_slideshow/uikit.slideshow') }} in
   your front page twig template and clear caches. 
4. Assign Uikit Slideshow View block to your front page region of convenience.
