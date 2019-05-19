INTRODUCTION
------------

Ubercart QuickPay is the payment gateway module for Drupal version 8 and above.
The module uses the Danish Payment Gateway ‘QuickPay’ to make payment.

The module supports two different payment methods

 * QuickPay Embedded method and
 * QuickPay Form method

QuickPay Embedded – QuickPay Embedded method accepts payment
through Credit card.
It won’t redirect users to the QuickPay hosted payment window. That makes
the payment process quick and easy.

Checkout must be HTTPS secured and PCI verified in order to receive payment
through QuickPay Embedded method for any Drupal system.

QuickPay Form – QuickPay Form method lets you get payment via QuickPay
hosted payment window. It supports multiple languages and accepts
different payment methods.

It supports multiple languages like English, Spanish, Danish, French etc.
You can opt for any of the payment method from Master card, visa card etc.

This module does have the payment refund functionality.
But, you can’t do partial refund using the QuickPay Embedded payment method.
Payment must be captured by the system to make it valid for the refund.
That can be done manually as well as automatically.
You can enable the auto capture in order to activate the
instant refund of the payment.

Auto capture payment option is a default setting for
the QuickPay Embedded method.
You can also do that in QuickPay Form method

If somehow you will get any kinds of error related to quickpay API credential
then you have to change the order id prefix from admin configuration of
payment method. Otherwise, you will get "Order id already exists" error
while processing the order again.



Supported Features
 * Supported multiple payment methods
 * Language selection for payment window
 * Payment auto capture
 * Payment Refund

REQUIREMENTS
------------

This module requires the following modules:
 * Ubercart (https://www.drupal.org/project/ubercart)
 * PCI verification required – Yes for QuickPay Embedded method

INSTALLATION
------------

 * Use composer to install QuickPay (composer require drupal/uc_quickpay).
 * If the composer is not installed then first you need to install
   composer after that you need to install quickpay library using
   (composer require quickpay/quickpay-php-client).
 * After installing QuickPay library, Enable Ubercart QuickPay module.
 * Follow the standard method to install/enable it.

 See: https://drupal.org/projects/uc_quickpay

CONFIGURATION
-------------

 * Go to  Administration » Store » Configuration:
  - Click on Payment methods and select payment method which are 
    provided by the module.

 * Select any one payment method and fill some mandatory information
   which provided by quickpay when you create an account.
   This form has contained third party API Keys which you will find out at:
   https://manage.quickpay.net/

 * If you select "Form Payment method" you need to add callback URL
   at Quickpay.
   QuickPay Admin » Settings » Merchant.
   e.g http://www.example.com/callback/

MAINTAINERS
-----------
 * KrishaWeb Technologies (https://www.drupal.org/u/krishaweb)
 * Girish Panchal (https://www.drupal.org/u/girishpanchal)
