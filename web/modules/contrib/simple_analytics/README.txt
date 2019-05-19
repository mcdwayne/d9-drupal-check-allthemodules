Simple Analytics Module Readme
----------------------------

Simple Analytics allow to integrate a site analysis code easily. 
User can use internal tracking system (Very simple) and / or integrate
a google analytics code, Piwik analytics code or any other custom code.
The integrated code can display on all pages, exclude admin pages or 
display only for anonymous users. Permission to view history of 
intanal statistic can manage, to display to the anonymous users.

By default:
    - No tracking for admin pages (/admin/)
    - No tracking for Authenticated Users
    - Intanal tracking system enabled.

Optional
------------

Chartist-js Library
Website : https://gionkunz.github.io/chartist-js/
Git     : https://github.com/gionkunz/chartist-js/tree/master/dist
Files:
    - chartist.min.js
    (https://github.com/gionkunz/chartist-js/blob/master/dist/chartist.min.js)
    - chartist.min.css
    (https://github.com/gionkunz/chartist-js/blob/master/dist/chartist.min.css)

Library folder  : DRUPAL_ROOT_DIR/libraries/chartist-js


Installation
------------

Download and install via drush
drush en simple_analytics -y

To show the charts, please download and put chartist.min.js and
chartist.min.css in to libraries/chartist-js.

To install this module, place it in your modules folder and enable it on the 
modules page.


Configuration
-------------

All settings for this module are on the Simple Analytics Module configuration
 page, under
(Administration -> Configuration -> System -> Simple Analytics).
You can visit the configuration page directly at 
/admin/config/development/simple-analytics.
Only administrator can visit this page.

Statistic pages : 
/simple_analytics/view/today
/simple_analytics/view/settings
/simple_analytics/view/history	(Permission manageable)


Similar projects
-------------
Google Analytics
Piwik Web Analytics

Different with this project : This project include Google Analytics, 
Piwik analytics, Other custom analytics system and also integrated basic 
analytics system.


Thank you for using Simple Analytics Module.
------------------------------------------
