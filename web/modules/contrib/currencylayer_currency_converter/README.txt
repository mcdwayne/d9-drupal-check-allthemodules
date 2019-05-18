CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
This module will help your website convert currency using the Google finance
calculator. It will give you a block to convert currency from one format to
another. This module also provides the option to set your default block
currency.

INSTALLATION
------------
* Install as usual,
see https://www.drupal.org/documentation/install/modules-themes/modules-8.

CONFIGURATION
-------------
* Register on https://currencylayer.com site and get Access Key
* Set API key, at
   admin >> configuration >> Currencylayer currency converter settings.
* Run Cron (Every cron run currency conversion rate save in
"currencylayer_converter_rate" variable and at the time of currency conversion,
system use this conversion rate. For more accurate rate set cron every 3 hours.)
* Enable Currencylayer currency converter block & Change default currencies to convert from and to, at
   admin » structure » block layout


MAINTAINERS
-----------
Current maintainers:
 * Vicky Nandode (vickynandode) - https://drupal.org/u/vickynandode
