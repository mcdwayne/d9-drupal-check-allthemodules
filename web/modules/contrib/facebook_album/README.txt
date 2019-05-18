
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Setup
 * Installation


INTRODUCTION
------------

 * Current Maintainers: Bryan Sharpe <bryansharpe@gmail.com>, Francis Bailey <fbailey@acromediainc.com>

 * Facebook Album provides a simple block to display public Facebook 
   Albums in a gallery and optionally using colorbox.
 * Only FB Pages will work with this module, not personal profile albums.
   Profile albums require that you use an authorized access token (this may eventually be integrated into the module,
   but not at this point).


FEATURES
--------
 * Include/Exclude Albums
 * Limit Albums
 * Album Descriptions
 * Album Location
 * Album width/height
 * Photo width/height


REQUIREMENTS
------------

This module requires the following modules:

 * Jquery (https://www.drupal.org/project/jquery_update)
 * Colorbox (optional, but preferred) (https://www.drupal.org/project/colorbox)


SETUP
-----

 * Enter your Facebook app ID and secret at the modules configuration page: /admin/config/services/facebook_album
 * How to create your Facebook App Id and Secret?
    Step1 : To start with, navigate your browser to the Facebook Developers page(https://developers.facebook.com/apps).
    Step2 : click “Add a New App” and choose website.
    Step3 : click “Skip and Create App ID”. Next you will be asked to Create a New App ID and choose a category.
    Step4 : After creating new App Id, use can find your App name on dashboard(on left corner). Click on your App name which redirects to your App page.
    Step5 : On your App dashboard App Id and secret is available.
 * Reference to create App Id and Secret, follow(https://goldplugins.com/documentation/wp-social-pro-documentation/how-to-get-an-app-id-and-secret-key-from-facebook/).


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

 * Navigate to <yourdrupalinstall.com>/admin/configuration/facebook_album or click on the configuration link in the modules page.
   Enter in your Page ID, Facebook Application ID, Facebook Application secret and click save configuration. Wherever you display the block
   you should now see a list of albums specified from from the Page ID.


MAINTENANCE
-------------

 * This project is not actively maintained, but I will try to help 
   with any bugs/issues/etc.
