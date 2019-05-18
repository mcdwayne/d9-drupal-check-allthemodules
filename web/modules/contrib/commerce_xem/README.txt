
# Commerce Xem module

Provides a Xem cryptocurrency Commerce payment method. 

## Requirements

Commerce Xem is a Commerce payment method. 
You need the Drupal 8 Commerce module with the Drupal Commerce Payment module enabled. 
https://www.drupal.org/project/commerce

## Install

1. Install your Drupal Commerce as usual

2. Go to [admin/commerce/config/payment-gateways] and click on "Add payment gateway"

3. Choose the payment plugin named "QRCode Xem payment method"

4. Fill all fields, like any other payment method. On the mode checkboxes, 
"Test" will use TestNet servers. "Live" will use MainNet servers. 

5. Type your Xem public key, where the customers payment will be send

You will see Xem payment method like others on the checkout page. 

## Features

This modules gives to your Drupal Commerce a Xem cryptocurrency integration. 
This module create a Xem Drupal Commerce currency. 

The ISO code "999" is fake, because at the moment, Drupal commerce does not give
 the ability to add "non official" currencies. 
A numeric ISO 4217 code is required, and cryptocurrencies does not 
have this kind of code, even Bitcoin. 

This module is also integrated with the Commerce Currency Resolver module. 
It allows automatic conversion of your prices on the checkout process, using 
Coinmarketcap API.

For example, you can type your prices in USD on the Back Office and your prices 
will be converted with the USD/XEM automatically. 


If you want to display your prices in XEM follow this steps :

1. Download and install the Commerce Currency Resolver module

2. In Commerce Currency Resolver settings tab, select "XEM" as the default 
currency

3. In Commerce Currency Resolver conversion tab, select "coinmarketcap" as the 
Exchange rate API. Type a fake API key, Coinmarketcap does not need that. 

4. That's it, your checkout process will be in XEM now !


Check the project page : 
https://www.drupal.org/project/commerce_currency_resolver

## About Xem

Want to know more about Nem and their Xem cryptocurrency ? 
Check their website : https://nem.io/