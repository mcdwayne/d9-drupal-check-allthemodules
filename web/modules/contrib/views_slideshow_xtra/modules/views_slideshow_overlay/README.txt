NOTE: THIS IS A NON-WORKING ALPHA VERSION

Overview
--------

The goal of this module is to create an easy way to place HTML elements on top of a Views Slideshow.
The HTML elements are placed in <div> overlays, with overlay visiblity controlled by toggling the  
CSS 'display' property, so that elements are displayed with their corresponding slide.

Installation
------------

1.  Download and extract to your modules directory the following modules:
    views, views_slideshow, views_slideshow_xtra, ctools, libraries.

2.  Download the Cycle plugin from http://malsup.com/jquery/cycle/download.html and
    extract into the directory /sites/all/libraries/jquery.cycle.
    All files except jquery.cycle.all.min.js may be deleted.

3.  Enable the following modules: views, views ui, views_slideshow, views_slideshow_cycle,
    ctools, libraries, views_slideshow_xtra_overlay and (optionally) views_slideshow_xtra_example.

Slideshow Examples Module
-------------------------
   
If a working example is desired, enable the Views Slideshow Examples module.  See that module's
README.txt for more information.

Adding an Overlay to an Existing Slideshow View
-----------------------------------------------

1.  Edit the slideshow's View, creating a new display of type "Views Attachment".
2.  Set the style of the attachment display to "Slideshow Overlay".
3.  Define the content you want on the overlay in the attachment display.  Use
    reglar Views' fields, sorting, filters, templates, etc.
4.  Attach the attachment display to one or more displays that have the style set to "Slideshow"
    by selecting Attachment Settings >> Attach to: and specify the slideshow display.
5.  In the slideshow display's Slideshow Settings >> Widgets, check one of the "Views Slideshow Xtra Overlay"
    checkboxes.

