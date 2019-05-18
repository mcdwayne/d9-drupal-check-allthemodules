Automatic Alternative Text

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The module uses the Microsoft Azure Cognitive Services API to generate an 
Alternative Text for images when no Alternative Text has been provided by user.


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

 * You may want to disable Toolbar module, since its output clashes with
   Administration Menu.


CONFIGURATION
-------------

After install go to /admin/config/media/auto_alter to configure the module.
You need to create an account at: https://www.microsoft.com/cognitive-services

On Microsoft Azure:
After creating an account login
Register a new Resource "Computer Vision API"
After that you can access your API keys.
 Copy "Key 1" to the "API Key" field of your Drupal configuration
Click on "Api Reference" link
  Select "Describe Image" function
  Choose your location
  Scroll down to "Request URL"
  Copy "Request URL" to the "URL of Endpoint" field of your Drupal configuration

Then, decide whether or not users should see a status message when saving 
content.

Last step, enable suggestions on image upload, if you want to have an automtic suggestion for the alternative text of the image


MAINTAINERS
-----------

Current maintainers:
 * Ullrich Neiss (slowflyer) - https://drupal.org/user/850168

This project has been sponsored by:
 * crowd-creation GmbH
