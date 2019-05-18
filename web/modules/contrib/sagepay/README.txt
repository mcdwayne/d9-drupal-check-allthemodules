CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This is a payment gateway for Ubercart ported to Drupal 8 version, built with
Ubercart 8.4.x version that implements the "Direct Integration & Protocol
Guideline", allowing Ubercart to accept credit card payments via Sage Pay
without redirecting the user offsite. Original module for d7 version can be
found here:

 * https://www.drupal.org/project/uc_sagepay.

3D-Secure transactions through the "Verified By Visa" and "MasterCard
SecureCode" schemes are not yet implemented, and is on @TODO list, simply
because I do not possess an sagepay account with this feature enabled.

 * For a full description of the module visit:
   https://www.drupal.org/project/sagepay

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/sagepay


REQUIREMENTS
------------

This module requires:

 * Ubercart - https://www.drupal.org/project/ubercart


INSTALLATION
------------

Install the Sagepay module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Store > Payment Methods > Sagepay to
       configure it.
    3. Now, on checkout if the user selects Sagepay payment, the payment process
       will go through the sagepay api.


MAINTAINERS
-----------

 * Adrian ABABEI (web247) - https://www.drupal.org/u/web247
 * Nicolae Procopan (bumik) - https://www.drupal.org/u/thebumikgmailcom

Project maintained and supported by:

 * Optasy - https://www.drupal.org/optasy-0
 * ALLWEB247 - https://www.drupal.org/allweb247
 * Optasy - https://www.drupal.org/optasy
