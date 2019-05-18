CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation


INTRODUCTION
------------

This module allows Drupal Commerce customers to pay using the 
Barclaycard Smartpay Hosted Payment page.
https://www2.barclaycard.co.uk/business/accepting-payments/online-payment-solutions

Smartpay HPP is part of the Adyen Payment Platform.
https://www.adyen.com/
This gateway will be compatible with other providers using Hosted Payment Pages.

NOTICE:
From July 2016, Adyen will no longer support SHA1 HPP's.
Please make sure your Skin HMAC keys are updated to use the new SHA256 algorithm.

INSTALLATION
------------

 1. Download and copy the 'commerce_smartpay' folder into your modules directory.

 2. Enable the module under /admin/modules within the group 'Commerce (contrib)'.

 3. Add a new Payment gateway under 'admin/commerce/config/payment-gateways'
    You will need your Merchant Account, Skin Code and HMAC keys for your
    Test and Live environments.