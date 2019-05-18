-- INTRODUCTION --

Scroll Depth Analytics module provides an additional scroll tracking 
functionality to Google Analytics using scroll depth google analytics plugin. 

Module provide an API to track persentage/element wise scroll tracking.

-- REQUIREMENTS --

This module requires the following modules:
 1) Google Analytics - https://www.drupal.org/project/google_analytics
 2) Libraries - https://www.drupal.org/project/libraries
 3) Scroll Depth JQuery Plugin - http://scrolldepth.parsnip.io/

-- INSTALLATION -- 

1) Copy scroll_depth_analytics directory to your modules directory
2) Download Scroll Depth library from http://scrolldepth.parsnip.io/ and put 
   it in libraries directory.
3) Enable the module at module administration page
4) Configure scroll tracking in admin/config/system/googleanalytics page under 
   'Scroll' vertical scrollbar.

-- CONFIGURATION --

* Configure Scroll options in 
Administration » Configuration » System » Google Analytics :
   - Click on the Scroll Tab to Enable Scroll Tracking
   - Add pages to track specific pages
   - Enable element tracking option to record scroll of 
     specific elements with class or id attribute

-- FEATURES -- 

1) Track page scroll tracking in persentage wise. Monitors the 25%, 50%, 75%, 
   and 100% scroll points, sending a Google Analytics Event for each one.
2) Record scroll events for specific elements on the page.


-- CONTACT --

Current maintainers:
* Shiju John (shiju_ckl) - https://www.drupal.org/u/shiju_ckl
