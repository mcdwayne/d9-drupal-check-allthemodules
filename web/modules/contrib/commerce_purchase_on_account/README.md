# Commerce Purchase on account
<h2><strong>Simple payment gateway and method to order on account</strong></h2>

<strong>Project URL</strong> - https://www.drupal.org/project/commerce_purchase_on_account

This module provides a simple commerce payment- gateway and method, which will allow the customer to complete an order 
with only leaving his billing Address and additional data from the billing profile. 

Using the "Purchase on account" gateway will result in a purchase on account payment method only! Use this if you only 
have this payment method on your site and want to have a clean checkout form.

To use the payment method along other payment methods, you can inject the method to an existing payment gateway, other 
then the provided one. Go to /admin/commerce/config/payment-gateways/commerce-purchase-on-account to configure in which
gateway it should be injected. You can then activate the "Purchase on account" payment method on the selected gateway 
settings page. Don't forget to clear the cache after changing the settings.
