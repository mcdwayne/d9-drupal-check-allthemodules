Automatic Alternative Text

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The module uses the Microsoft Azure Cognitive Services API to identify, if the
image(s) provided by user contains racist or adult content. The cognitive
service returns a value for each category describing the likelihood the image
contains of racist or sexual content.
You can specify a treshold in percent for an image to be flagged for containg
racist or adult content.
Content (or any content entity) containing an image beeing flagged will not be
able to be published, without review my a Moderator (Administrator).



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
After creating an acoount login

Register a new Resource "Content Moderator" at:
  https://portal.azure.com/#create/Microsoft.CognitiveServicesContentModerator

After that you can access your API keys.
  Copy "Key 1" to the "API Key" field of your Drupal configuration

Go to:
  https://westus.dev.cognitive.microsoft.com/docs/services/57cf753a3f9b070c105bd2c1/operations/57cf753a3f9b070868a1f66c
  Select "Image Evaluate" function
  Choose your location
  Scroll down to "Request URL"
  Copy "Request URL" to the "URL of Endpoint" field of your Drupal configuration

Specify a treshold for racist and adult content, 40% should be a good point to start.


MAINTAINERS
-----------

Current maintainers:
 * Ullrich Neiss (slowflyer) - https://drupal.org/user/850168

This project has been sponsored by:
 * crowd-creation GmbH
