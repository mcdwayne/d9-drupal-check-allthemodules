
Commerce PagSeguro
-------------------

ABOUT
-----
This module provides a Drupal Commerce payment gateway for PagSeguro and aims to provide payment
mothods for most of PagSeguro's services including Lightbox, Checkout Transparente,
Boleto and Debit payments.


FEATURES
--------
o Allows switching between "test" and "live" modes.
o Supports Lightbox, Checkout Transparente, Boleto and Debit payments.

REQUIREMENTS AND DEPENDENCIES
-----------------------------
- Drupal Commerce 2.x (https://www.drupal.org/project/commerce)
- CPF module (https://www.drupal.org/project/cpf)


INSTALLATION
------------
Install the module as usual, more info can be found on:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

Alternatively you can use Composer which will also take care of all the dependencies:
composer require drupal/commerce_pagseguro


USAGE
------
Once the module has been installed, you'll need to:

o Get your PagSeguro credentials:
- From the sandbox account - https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html
- From the live account - https://pagseguro.uol.com.br/preferencias/integracoes.jhtml
- You'll need an email for the account and both test and live tokens.

o Create the required PagSeguro fields stored in the user's account
- Go to Administration > Configuration > People > Account settings > Manage Fields (/admin/config/people/accounts/fields)
- Create the following fields: 
CPF (Field type: CPF)
Date of birth (Field type: Date)
Full name (Field type: Text field plain)
Phone number (Field type: Integer)

o Add the PagSeguro Payment Gateway:
- Go to Administration > Commerce > Configuration > Payment gateways > Add payment gateway (admin/commerce/config/payment-gateways/add)
- This is where you create your payment gateway for Pagseguro. Add and select the fields and credentials 
created from the steps above.
- You should now be able to go through the checkout process and use the payment methods specified.

TROUBLESHOOTINg & ISSUES
-------------------------
Any issues, please open a ticket at https://www.drupal.org/project/issues/commerce_pagseguro

READ MORE
----------
See the project page on drupal.org: http://drupal.org/project/commerce_pagseguro
