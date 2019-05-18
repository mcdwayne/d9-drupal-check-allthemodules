README file for Commerce Pay.JP

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
This project integrates Pay.JP online payments into
the Drupal Commerce payment and checkout systems.
https://pay.jp/docs/started
* For a full description of the module, visit the project page:
  https://www.drupal.org/project/commerce_payjp
* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/commerce_payjp


REQUIREMENTS
------------
This module requires the following:
* Submodules of Drupal Commerce package (https://drupal.org/project/commerce)
  - Commerce core,
  - Commerce Payment (and its dependencies);
* Pay.JP PHP Library (https://github.com/payjp/payjp-php);
* Pay.JP merchant account (https://pay.jp/signup).


INSTALLATION
------------
* This module needs to be installed via Composer, which will download
the required libraries.
composer require "drupal/commerce_payjp"
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies


CONFIGURATION
-------------
* Create new Payjp payment gateway
  Administration > Commerce > Configuration > Payment gateways > Add payment gateway
  Payjp specific settings available:
  - Secret key.
  - Public key.
  All those API credentials are provided by the Pay.JP merchant account. It is
  recommended to enter test credentials and then override these with live
  credentials in settings.php. This way live credentials will not be exported to code.


HOW IT WORKS
------------
* General considerations:
  - Shop owner must have an Pay.JP merchant account
    Sign up
    https://pay.jp/signup
  - Customer should have a valid credit card.

* Checkout workflow:
  It follows the Drupal Commerce Credit Card workflow.
  The customer should enter its credit card data
  or to select one of the existing Pay.JP payment methods.

* Payment Terminal
  The store owners can Void, Capture and Refund the Pay.JP payments.


TROUBLESHOOTING
---------------


MAINTAINERS
-----------
Current maintainers:
* Nguyen Quoc (quocnht) - https://www.drupal.org/u/quocnht
