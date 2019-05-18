README file for Commerce QualPay Module


CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Installation
* Configuration
* How It Works
* Troubleshooting
* Maintainers


INTRODUCTION
------------
This project integrates QualPay online payments into
the Drupal Commerce payment and checkout systems.
https://www.qualpay.com/developer/guides

* For a full description of the module, visit the project page:
  https://www.drupal.org/project/commerce_qualpay
* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/node/add/project-issue/commerce_qualpay


REQUIREMENTS
------------
This module requires the following:
* Submodules of Drupal Commerce package (https://drupal.org/project/commerce)
  - Commerce core
  - Commerce Payment (and its dependencies)
* QualPay Merchant account (https://app-test.qualpay.com/login/)


INSTALLATION
------------
* This module needs to be installed via Composer, which will download
the required libraries.
composer require "drupal/commerce_qualpay"
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies


CONFIGURATION
-------------
* Create a new QualPay payment gateway.
  Administration > Commerce > Configuration > Payment gateways > Add payment gateway
  QualPay settings available:
  - Enable QaulPay
  - Set Payment mode (Test or Live)
  - Enter Security Key and Merchant Id (Create Sandbox login in QualPay to get details : https://app-test.qualpay.com/login/)

  Use the API credentials provided by your QualPay merchant account. It is
  recommended to enter test credentials and then override these with live
  credentials in settings.php. This way, live credentials will not be stored in the db.


HOW IT WORKS
------------
* General considerations:
  - The store owner must need a QualPay merchant account.
    Sign up here:
    https://app-test.qualpay.com/login/
  - Customers should have a valid credit card.
    - QualPay provides several dummy credit card numbers for testing:
      https://www.qualpay.com/developer/api/testing

* Checkout workflow:
  It follows the Drupal Commerce Credit Card workflow.
  The customer should enter his/her credit card data
  or select one of the credit cards saved with QualPay
  from a previous order.

* Payment Terminal
  The store owner can Void, Capture and Refund the QualPay payments.


TROUBLESHOOTING
---------------
* No troubleshooting pending for now.


MAINTAINERS
-----------
Current maintainer:
* Saurabh Dhariwal - https://www.drupal.org/u/saurabhdhariwal

This project has been developed by:
* Addwebsolution - https://www.addwebsolution.com/