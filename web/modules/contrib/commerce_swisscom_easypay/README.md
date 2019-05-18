# Commerce Swisscom Easypay

Provides Commerce integration for [Swisscom Easypay](https://www.swisscom.ch/en/residential/mobile/additional-services/mobile-payment.html).

This payment gateway redirects to the user to the Swisscom Easypay checkout page.
The purchase is charged via user's mobile phone bill.

> Note: Payment only works for Swisscom customers with a mobile subscription. 

## Installation

Install the module via composer:

```
composer require drupal/commerce_swisscom_easypay
```

Activate the module in Drupal and create a new Payment Gateway of type `Swisscom Easypay (Checkout Page)`.

## Configuration

* **Merchant ID** The merchant ID received from Swisscom
* **Secret Key** The secret key received from Swisscom. Note that the key is different per environment (STAGE or LIVE)
* **Checkout page title** The title being displayed on the checkout page
* **Checkout page description** A description displayed on the checkout page
* **Checkout page image** Absolute URL to an image which will be presented on the checkout page
* **Payment information for customer** The payment info of the service, which will be printed on the bill of the customer



