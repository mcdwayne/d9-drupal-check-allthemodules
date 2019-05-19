CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * International payments
 * Issues
 * FAQ
 * Maintainers


INTRODUCTION
------------

The Ubercart GoCardless Client module provides an integration with the
Ubercart e-commerce suite, and GoCardless.com. Sites that implement the 
module are 'clients' of https://seamless-cms.co.uk, which handles the 
management of direct debit mandates with GoCardless on behalf of the client
site.

The module integrates into Ubercart like any other Payment service module,
and allows customers to create a direct debit mandate for paying for products
upon checking out.

GoCardless is very competitive compared with other payment services and 
charges 1% on transactions, with a minimum of 20p in the UK, and a similar
amount in other currencies.

There are two kinds of payment service available from GoCardless: Subscriptions and One-off Payments. 

Subscriptions are automatic recurring payment, and  work well for users
that want to take the same payment on a regular basis (for instance £5 per 
week, or £20 on the first of each month).

One-off Payments allow you, the end user, to trigger a payment against a 
direct debit mandate at any time with the API. This allows you to charge 
your end customers ad-hoc amounts.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/uc_gc_client

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/uc_gc_client


REQUIREMENTS
------------

 * An SSL certificate (https) is required in order to use the module in
   live mode although it is not required for sandbox.

 * This module requires the following modules:

   - Ubercart Payment (https://www.drupal.org/project/ubercart)
   - Ubercart Product Attibutes (https://www.drupal.org/project/ubercart)
   - Date (https://www.drupal.org/project/date)


RECOMMENDED MODULES
-------------------

 * Date Popup (https://www.drupal.org/project/date):
   This module ships with the Date module and when enabled it provides a
   user friendly popup widget for date fields.
 * Fieldset Helper (https://www.drupal.org/project/fieldset_helper):
   Provides enhanced user experience for customers and administrators
   by remembering the state of a Drupal collapsible fieldsets.


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.


CONFIGURATION
-------------

 * Install and enable the Ubercart payment service in the normal way:
   admin >> store >> settings >> payment >> method >> gc_client 

 * Before you can use your site with GoCardless you need to 'Connect' as a 
   client of Seamless CMS. This is initiated from the payment method's 
   settings page at Admin >> Store >> Configuration >> Payment methods >> 
   GoCardless. Simply click on the 'Connect' button and follow the steps.
   If you do not have an account with GoCardless yet you will be required to
   create on during this process. You will be redirected to 
   Seamless-CMS.co.uk as the final stage in the Connection process, before
   being returned back to your site.

 * Ensure that the Payment method pane is enabled at
   admin >> store >> settings >> checkout 

 * Additional settings are provided for each specific Ubercart product. These 
   are configured by clicking the GoCardless Settings tab, at the
   bottom of the product's node edit form.

 * The module provides a Payment Interval attribute, with four presets: 
   weekly, fortnightly, monthly and yearly. Enabling this will allow your
   customers to choose the payment plan of their choice. Alternatively you can
   configure a product to a specific fixed payment interval.

 * Several hooks are provided to enable other modules to interact with this
   one at key moments, such as before setting up a new direct debit mandate,
   or before creating a payment, or after receiving a webhook from GoCardless.
   More information on using these is provided in uc_gc_client.api.php.



INTERNATIONAL PAYMENTS
----------------------

The default region for GoCardless.com is the UK, which uses the Bacs direct 
debit scheme. To use international payments, you must first contact 
GoCardless, and request that they enable the required region(s), so that you
can use the relevant direct debit schemes. Instructions on how to do this 
are at: https://support.gocardless.com/hc/en-gb/articles/115002833785-Collecting-from-multiple-regions.

Having done this, international payments are enabled with the checkbox on
the payment method's settings page.

After enabling international payments, choose which countries GC can handle,
at: admin >> store >> settings >> countries >> gocardless.

If you are using other payment methods in addition to GoCardless, you must 
enable and configure the uc_ajax module, to ensure that the correct countries
are listed in the Delivery and Billing panes at checkout.

After enabling uc_ajax go to admin >> store >> settings >> checkout >> ajax:

  1. Add 'Payment method' as a Triggering form element 
  2. Select Delivery information and Billing information as Panes to update
  3. Submit 

You must also make sure that GC isn't the default payment method, because it
needs to be actively selected in checkout in order to load the correct 
countries. (The default payment method is the first enabled method in the list
at admin >> store >> settings >> payment.)


FAQ
---

Q: Where can I get an affordable SSL certificate for my site?

A: SSL certificates are available for free from https://www.sslforfree.com. 
   Let's Encrypt is the first free and open Certificate Authority. Since they
   are a charity it is recommended that you make a small donation to their
   service to help make it sustainable.


Q: Why not integrate directly with GoCardless rather than as a client of
   Seamless CMS?

A: It is perfectly possible to do this and GoCardless provide very good
   instructions for using their API. However, as a partner of GoCardless, 
   Seamless CMS generates an income of 10% of GoCardless' fees 
   (0.1% of each transaction). It is intended that by building up this 
   business, I can develop a modest income stream to ensure that the module
   is properly maintained, and I am able to respond efficiently to security
   threats, issues, and feature requests. So please help spread the word!


MAINTAINERS
-----------

Current maintainers:
 * Rob Squires (roblog) - https://www.drupal.org/u/roblog
