README file for Commerce Google Analytics

CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Installation
* Configuration
* How it works
* Troubleshooting
* Maintainers

INTRODUCTION
------------
This is a contribution to Drupal Commerce. This module adds the possibility to
send the order data to the Google Analytics service.
https://developers.google.com/analytics/devguides/collection/analyticsjs/ecommerce
* For a full description of the module, visit the project page:
  https://www.drupal.org/project/commerce_google_analytics
* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/commerce_google_analytics


REQUIREMENTS
------------
This module requires the following:
* Submodules of Drupal Commerce package (https://drupal.org/project/commerce)
  - Commerce core,
  - Commerce Order (and its dependencies);
* Google Analytics (https://www.drupal.org/project/google_analytics).
* GA Push (https://www.drupal.org/project/ga_push).


INSTALLATION
------------
* Install as you would normally install a contributed drupal module. See:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
  for further information.

CONFIGURATION
-------------
No configuration needed for this module.
All it is done in the code. The configuration should be done for Google Analitics
and GA push modules.


HOW IT WORKS
------------

* It sends Ecommerce tracking data to google analytics when an order is placed.
@see \Drupal\commerce_google_analytics\EventSubscriber\SendOrderAnalyticsSubscriber

* The data could be customized using the API available
@see commerce_google_analytics.api.php


TROUBLESHOOTING
---------------
* No troubleshooting pending for now.


MAINTAINERS
-----------
Current maintainers:
* Tavi Toporjinschi (vasike) - https://www.drupal.org/u/vasike

This project has been developed by:
* Commerce Guys by Actualys
  Visit https://commerceguys.fr/ for more information.
