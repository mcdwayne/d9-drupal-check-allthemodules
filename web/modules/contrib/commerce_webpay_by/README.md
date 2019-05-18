# Drupal 8 Commerce 2 module: Commerce Webpay.by Payment gateway
----------------------------------------------------------------

CONTENTS OF THIS FILE
---------------------

- Introduction
- Requirements
- Installation
- Configuration
- Maintainers


INTRODUCTION
------------

This module implements a Drupal Commerce payment method, to embed the payment
services provided by [Webpay.by](https://webpay.by/) payment gateway, which
supports next payment methods:
- Internet acquiring (Accept payment by bank cards on your website, in
Belarusian rubles and foreign currencies **RUB**, **USD**, **EUR**)
- System "Payment" **SSIS** (бел. **АРІП** / рус. **ЕРИП**) (the presence of the
website is not necessary)

[Offisial documentation](https://webpay.by/en/developers-guide) by payment
gateway.

Module also supports tokens, to configure success / canceled urls. And choosing
currency supported by gateway (RUB, USD, EUR).

For a full description of the project visit the project page:
https://www.drupal.org/project/commerce_webpay_by

To submit bug reports and feature suggestions, or to track changes:
http://drupal.org/project/issues/commerce_webpay_by


REQUIREMENTS
------------

 * Commerce Payment (from [Commerce](http://drupal.org/project/commerce) core)
 * Commerce Order (from [Commerce](http://drupal.org/project/commerce) core)
 * [Token](http://drupal.org/project/token)


INSTALLATION
------------

**By the composer (recommended)**:
```bash
composer require drupal/commerce_webpay_by
```

**Manually**:

Install the Commerce Webpay.by Payment module, as usual, by copying the sources
to a modules directory, such as `modules/contrib` or `modules`. 

See: https://drupal.org/documentation/install/modules-themes/modules-8 for
further information.


CONFIGURATION
-------------

- In your Drupal site, enable the module.
- Go to `admin/commerce/config/payment-gateways` 
(*Commerce* -> *Configuration* -> *Payment gateways*), and add a new payment
method.
- Configure your API keys, and so one (please, read official
[docs](https://webpay.by/en/developers-guide)).
- Enable the "**Sandbox**" mode to work in test environment, or the "**Live**" 
mode to get real payments.


MAINTAINERS
-----------

Current maintainers:
- Anton Karpov (awd-studio) - http://drupal.org/user/2427420
