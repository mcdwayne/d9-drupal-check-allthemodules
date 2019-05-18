CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers
 
 INTRODUCTION
 ------------
 
 This module provides integration with CreditGuard Redirect Payment service.
 
  * For a full description of the module, visit the project page:
    https://www.drupal.org/project/cg_payment
 
  * To submit bug reports and feature suggestions, or to track changes:
    https://www.drupal.org/project/issues/cg_payment
    
REQUIREMENTS
------------

 This module has the following dependencies:

  * PHP SOAP extension (http://php.net/manual/en/book.soap.php)
  * dofinity/creditguard PHP library (https://github.com/dofinity/creditguard)    
 
INSTALLATION
------------
 
 * **Recommended:** Install with Composer:
   `composer require 'drupal/cg_payment:^1.0'`
 * Alternative: Install as you would normally install a contributed Drupal
   module.
   Visit: https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.
 * In case you chose the alternative option, you should also download the
  dofinity/creditguard PHP library into the vendor directory of your Drupal
  installation.
 

CONFIGURATION
-------------
 
 * Configure user permissions in Administration » People » Permissions:

   - Access transaction overview page
   - Administer transaction settings
   - Administer transaction types

 * Configure CreditGuard integration in Administration » Configuration »
   Web services » CreditGuard Payment

PAYMENT PROCESS FLOW
---

 * Get the redirect URL from CreditGuard and redirect the user to it
 * The user enters their credit card details, and payment token is generated
 * The user returns to the site's "payment complete" page » the token details
   are being validated » another request to charge the token is being made

FAQ
---

Q: Can I override the payment complete page's template file?

A: Yes, copy payment-complete.html.twig to your theme's templates directory.

Q: Can I test the integration with CreditGuard without writing any code? 

A: Yes, just navigate to Administration » Configuration » Web services »
 CreditGuard Payment, fill your terminal ID and mid (marchant ID) and click
 "Test integration"

Q: I have added a terminal ID and marchant ID fields to my custom entity,
 Can I add validation for that fields so it will stop us from submitting
 wrong IDs?

A: Yes, we have a validation plugin (constraint) for that, see
 cg_payment.api.php .

MAINTAINERS
-----------

Current maintainers:
 * Rotem Reiss (rreiss) - https://www.drupal.org/u/rreiss
 * Yonatan Rab (yonatan0) - https://www.drupal.org/u/yonatan0 
