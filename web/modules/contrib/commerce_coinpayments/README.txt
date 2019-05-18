Commerce Coinpayments
=====================
CoinPayments.net Payments Module for Drupal Commerce, which accepts all
cryptocurrencies for payments in your drupal site. To check the supported
coins visit - https://www.coinpayments.net/supported-coins

Installation:
=============
 - Extract the files in your module directory (typically modules/contrib).
 - Visit the modules page and enable the module.
 - From the modules page you will find links to
    - permission settings
    - help

This module is safe to use on a production site.
Just be sure to grant 'access commerce coinpayments ipn' permission to all
users (including ANONYMOUS USERS).

How to Configure Module:
========================
 - Go to Commerce Store, Configuration, Payment gateways.
 - Click on "Add payment gateway" button to add new payment gateway.
 - Insert Name & Display name for new payment gateway & select
   "CoinPayments.net - Pay with Bitcoin, Litecoin, and other
   cryptocurrencies (Off-site redirect)" plugin from the given list.
 - Enter your Merchant ID & IPN Secret and click Save.
 - And you should be all set to use this module.

How to Test Module:
===================
 - First you need to create store (https://www.example.com/admin/commerce/config/stores)
   before creating any commerce product in drupal site.
 - Then create a new product(https://www.example.com/admin/commerce/products).
 - Add newly created product to cart using “add to cart” button on product page.
 - Goto the cart page - https://www.example.com/cart & click on checkout button & proceed to checkout.
 - Fill up the details & click on “continue to review” button & then click on “pay & complete purchase”.
 - User will be redirected to coinpayments page & after selecting currency barcode will generate.
 - After successful payment you will see the order status completed on
   link “https://www.example.com/admin/commerce/orders”

Author/Maintainers
==================
- Yogesh Pawar https://www.drupal.org/u/yogesh-pawar
