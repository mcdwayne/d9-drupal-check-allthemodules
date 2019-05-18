Global Gateway
==============

## Description

Module provides plugins and UI pages to work with different
 regions, languages.
Provides a language switcher block,
 that allow users to switch interface language in a simple way.
Contains a few sub-modules for region auto-detection as well.
And even more...

## Installation
Can be installed via traditional module
 installation ways(UI, Drush, Drupal Console).

## Usage
After installing module you might need to configure it - for this you
need to enable global_gateway_ui sub-module to be able 
to configure things in UI. Can be uninstalled later.

After enabling the UI sub-module you need to visit **admin/config/regional/global_gateway**
page and configure all of the basic options for the module.

To use the auto-detection methods you need to enable the appropriate 
sub-module(s) provided within this module:

Modules which provide a detection methods:

  * **Global Gateway Ip2Country**: provides an integration
   with ip2country service
  * **Global Gateway Smart IP**: provides an integration
   with Smart IP service
  * **Global Gateway Country**: preselect the country
   field to user region

Modules which provides an integration between detection methods
 and specific field types for a preselect values:
 
  * **Global Gateway Address**: integration with Address
   and Country fields
