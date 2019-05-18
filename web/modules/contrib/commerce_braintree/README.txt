Requirements 
============

Drupal 8.x (https://www.drupal.org/docs/8/install)
Commerce 2.x (https://docs.drupalcommerce.org/commerce2)
Commerce Braintree module (https://www.drupal.org/project/commerce_braintree)
Braintree Merchant Account (https://www.braintreepayments.com/)

Installation
============

1. Download, install, and enable the following to your sites modules directory.
	Check https://www.drupal.org/docs/8/install/step-2-install-dependencies-with-composer 
	for more instructions on how to install dependencies with composer in Drupal 8

	With Composer instalation of any module and automatic download of libraries is done with command "composer require drupal/module_name"
	composer require drupal/commerce_braintree 

	Enable Braintree module - Extend
	(admin/modules)

	Add Braintree as Payment gateway - Commerce > Configuration > Payment > Payment gateways > Add Paymentgateway
	(admin/commerce/config/payment-gateways)


2. To proceede to next step you will need Braintree merchant account

  Create or gather your Braintree website login credentials.
    https://www.braintreepayments.com

    a. Create a sandbox account for testing via
       https://www.braintreepayments.com/get-started

    b. Create a production account for going live via
       https://signups.braintreepayments.com/


 Log into Braintree using either the production or sandbox login options
     to get you API keys for this module.

     Gather the following credentials by visiting Account > My User > View API Keys.
     Click on the 1st (and likely only) public key link to view the key details.
     Note: The order they are displayed does not match the order they are entered
           in Drupal. Pay attention! :)

     * Merchant ID
     * Public key
     * Private key

     Next, you'll need the Merchant account ID (not the same as Merchant ID above).
     You can find this by going to  Account > Merchant Account Info

     Merchant account ID's are tied to currencies and should match the
     currencies you have enabled on your Drupal Commerce store. If you only have one
     currency then grab the only Merchant account ID listed in Braintree.
     Otherwise, make sure you match these up correctly.

     To create new Merchant account ID go to Account > Merchant Account Info > New Merchant Account

Enable Payment Methods
	
	To enable PayPal Payment Method go to Settings > Processing Options and toggle the switch 

Select Payment method types you want to be available in your store
	
	 Credit card
	 PayPal Express
	 PayPal Credit

Testing
=======

To test your implementation, add a product to your cart, proceed thorough checkout
and enter a credit card. We recommend using a sandbox account for this before
attempting to go live. If you're using a sandbox account, you can use the following
credit card to test a transaction

Credit Card #: 4111 1111 1111 1111
Expiration Date: Any 4 digit expiration greater than today's date
CVC (if enabled): Any 3 digit code



Note about currencies
=====================

You must configure your Braintree account on the Braintree website to accept all
currencies that your Commerce store accepts.

If you do not do this, Commerce orders will be charged at the default currency of your
Braintree account regardless of what currency is shown in your store (for example,
if a product in your store has a price of 10,000 JPY, but your Braintree account is only
set up to handle USD, then the price charged to your customers will be 10,000 USD,
not 10,000 JPY).

You can learn more about currency handling on the Braintree website:
https://articles.braintreepayments.com/get-started/currencies