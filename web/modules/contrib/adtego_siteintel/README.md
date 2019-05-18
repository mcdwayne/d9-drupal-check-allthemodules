INTRODUCTION
------------

The SiteIntel module provides Drupal integration with the AdTego SiteIntel
ad-blocker analytics service. See https://www.adtego.com

 * For a full description of the module, visit the project page:
   https://drupal.org/project/adtego_siteintel

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/adtego_siteintel


REQUIREMENTS
------------

No special requirements.


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------
 
 * Configure user permissions in Administration » People » Permissions:


   - Administer SiteIntel

     Users in roles with the "Administer SiteIntel" permission will be able to
     modify the SiteIntel configuration for the site.

   - Opt-in or out of SiteIntel

     Users in roles with the "Opt-in or out of SiteIntel" permission
     will, depending on the configuration of the module, be able to opt in or
     out of adblocker usage tracking on their account settings page.

 * Configure the module with your SiteIntel account key and site ID in
   Administration » Configuration » System » SiteIntel Configuration.
