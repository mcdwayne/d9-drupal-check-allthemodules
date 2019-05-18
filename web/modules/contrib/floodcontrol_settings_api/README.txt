CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
If you want to extend Drupal core's flood control mechanism to your custom forms then
this module provides
1) an API to construct an admin form to manage flood control settings per form.
2) an example module (D8 version for now) to demonstrate how you can implement flood control to your custom form.
3) an interface to clear flood table (D7 version for now)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules for further information.

 * Install the example module "mymodule" which demostrate how you can implement the hook (D8 only for now)

CONFIGURATION
-------------
1) Example module "mymodule" for demonstration (D8 for now)

 * Go to mymodule/form/default to see the example form

 * Keep submitting the form

 * You should see "You cannot send more than 2 messages in 60. Try again later."

 * To configure this threshold and window visit admin/config/floodcontrol_settings_api 

2) floodcontrol_settings_api

 * Go to admin/config/system/floodcontrol-settings-api to see the settings

 * Initially it is empty. You need to hook_floodcontrol_settings in your custom module to construct the admin settings form.

 * Once the hook_floodcontrol_settings is implemented then clear the cache and refresh admin/config/system/floodcontrol-settings-api

 * You should see the settings to configure the threshold and window   

 * Go to admin/config/system/floodcontrol-settings-api/clear-flood to clear the flood table (if needed).

@TODO: 
 * Add event based and identified based flood table clearance.
 * Create plugin based hook for D8

MAINTAINERS
-----------

Current maintainers:
 * gopisathya - https://www.drupal.org/u/gopisathya
 
