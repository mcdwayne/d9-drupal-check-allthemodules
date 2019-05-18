# Commerce iATS Module for Drupal Commerce

A module to implement the iATS Payments payment processing services for Drupal's
Commerce suite.

http://iatspayments.com/

## Features

* iATS Payments Rest API integration
* Credit card payment processing
* Hosted-form compatibility
* Recurring payments

## Requirements

Commerce iATS depends on the Drupal Commerce suite's Payment module. You'll need 
to install Commerce along with its dependencies in order to use this module. See
https://www.drupal.org/project/commerce.

Note: Drupal Commerce also depends on a additional libraries. The recommended 
method of installing these is via Composer:

`composer require commerceguys/addressing:~1.0 commerceguys/intl`

See the Commerce installation documentation for detailed, up to date 
instructions.

### Installation

Download and install the Commerce iATS module per the standard Drupal 
contributed module installation process. See https://www.drupal.org/node/1897420
for comprehensive instructions.

## Payment Methods

Credit card payments can be processed through the iATS payment gateway. To 
configure a payment gateway to use for iATS:

1. Go to admin/commerce/config/payment-gateways

2. Choose the "iATS" plugin

3. Enter your iATS processor credentials

4. Choose between the "Hosted form" to use the iATS hosted iframe or "Direct 
   submission" processing through Drupal

5. Save your payment gateway

The iATS payment gateway will now be available for use during checkout. For more
information on configuring your site's checkout functionality, see 
https://www.drupal.org/node/2969597 or the documentation provided by Drupal 
Commerce.

## Recurring Payments

Recurring payment functionality is provided via the Commerce Recurring Framework
module (https://www.drupal.org/project/commerce_recurring). Using this you can
configure recurring subscriptions and various billing schedules. See
https://www.drupal.org/node/2969595 for complete instructions on configuring 
recurring payments.
