CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Features


INTRODUCTION
---------------
ePayco is a Colombian payment gateway. This module is an aim to
integrate as much as possible the ePayco features with Drupal
Commerce.


REQUIREMENTS
---------------
This module requires:

 * Composer (for installation and library downloading).
 * Commerce 2.x
 * ePayco PHP library.


INSTALLATION
---------------
Just open a command-line tool (bash for most UNIX-Like OSs), and go
to the project root folder. Execute following command:

 composer require drupal/commerce_epayco

Then, just go to admin/modules, and enable module as usual.


CONFIGURATION
---------------
Once module is installed:

 * Go to admin/commerce/config/commerce-epayco/api-data, and add there a
   new entity. Fill there all needed values according to your ePayco dashboard.

 * Then, go to admin/commerce/config/payment-gateways (as usual) and add a
   "ePayco (Off-site)" payment gateway. For field "Configuration entity", just
   choose the entity you created in previous step. That's all.

 * Optional: Go to admin/people/permissions, and enable some roles to allow
   overriding some ePayco settings at their own store pages, or managing
   configuration entities.


FEATURES
---------------
- Off-site payments with Drupal commerce 2.x.
- Setup global gateway settings and override those settings per 
  store (kinda multistore) and role.
- Alter payment data dinamically with custom module, using the
  hook_commerce_epayco_payment_data().
- Drush command to check pending payments.
- Basic integration with Rules.
