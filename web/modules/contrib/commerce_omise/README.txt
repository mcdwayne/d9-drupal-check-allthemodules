README file for Commerce Omise

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
This project integrates Omise online payments into
the Drupal Commerce payment and checkout systems.
https://www.omise.co/docs
* For a full description of the module, visit the project page:
  https://www.drupal.org/project/commerce_omise
* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/commerce_omise


REQUIREMENTS
------------
This module requires the following:
* Submodules of Drupal Commerce package (https://drupal.org/project/commerce)
  - Commerce core,
  - Commerce Payment (and its dependencies);
* Omise PHP Library (https://github.com/omise/omise-php);
* Omise merchant account (https://dashboard.omise.co/signup).


INSTALLATION
------------
* This module needs to be installed via Composer, which will download
the required libraries.
composer require "drupal/commerce_omise"
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies


CONFIGURATION
-------------
* Create new omise payment gateway
  Administration > Commerce > Configuration > Payment gateways > Add payment gateway
  Omise specific settings available:
  - Secret key.
  - Public key.
  All those API credentials are provided by the Omise merchant account. It is
  recommended to enter test credentials and then override these with live
  credentials in settings.php. This way live credentials will not be exported to code.


HOW IT WORKS
------------
* General considerations:
  - Shop owner must have an Omise merchant account
    Sign up
    https://dashboard.omise.co/signup
  - Customer should have a valid credit card.

* Checkout workflow:
  It follows the Drupal Commerce Credit Card workflow.
  The customer should enter its credit card data
  or to select one of the existing Omise payment methods.

* Payment Terminal
  The store owners can Void, Capture and Refund the Omise payments.


TROUBLESHOOTING
---------------


MAINTAINERS
-----------
Current maintainers:
* Nguyen Quoc (quocnht) - https://www.drupal.org/u/quocnht
