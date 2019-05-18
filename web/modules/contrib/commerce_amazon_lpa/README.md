Amazon Pay and Login with Amazon
-------------------------

This module integrates Amazon Pay and Login with Amazon into Drupal and [Drupal Commerce][drupalcommerce].

Amazon Pay provides Amazon buyers a secure, trusted, and convenient way to log in and pay for their purchases on your site. Buyers use Amazon Pay to share their profile information (name and email address) and access the shipping and payment information stored in their Amazon account to complete their purchase. Learn more at

* [Amazon Pay US][amazonpay_us]
* [Amazon Pay UK][amazonpay_uk]
* [Amazon Pay DE][amazonpay_de]

[amazonpay_us]: https://payments.amazon.com
[amazonpay_uk]: https://payments.amazon.co.uk
[amazonpay_de]: https://payments.amazon.de
[drupalcommerce]: https://www.drupal.org/project/commerce

## Requirements

You must have [Drupal Commerce][drupalcommerce] and the Cart, Customer, and Payment submodules enabled.

The shop owner must have an Amazon merchant account. Sign up now:
* US : https://pay.amazon.com/us/merchant?ld=SPEXUSAPA-drupal%20commerce-CP-DP-2017-Q1
* UK : https://pay.amazon.com/uk/merchant?ld=SPEXUKAPA-drupal%20commerce-CP-DP-2017-Q1
* DE : https://pay.amazon.com/de/merchant?ld=SPEXDEAPA-drupal%20commerce-CP-DP-2017-Q1

## Features

The module's integration provides the following features:

* When using the *Amazon Pay and Login with Amazon* mode, users logging in with their Amazon accounts will have an account created in Drupal.
* Ability to provide the normal checkout experience or only provide Amazon based checkout.
* Multilingual support
* Support for United States, United Kingdom, and Germany regions.

The module's documentation can be found on Drupal.org at https://www.drupal.org/docs/7/modules/commerce-amazon-pay

When entering the Amazon checkout, user's will be prompt to log in with their Amazon account before beginning. However, no Drupal account will be created.

## Maintainers

Current maintainer:
* Matt Glaman ([mglaman])

Development sponsored by **[Commerce Guys][commerceguys]**:

Commerce Guys are the creators of and experts in Drupal Commerce, the eCommerce solution that capitalizes on the virtues and power of Drupal, the premier open-source content management system. We focus our knowledge and expertise on providing online merchants with the powerful, responsive, innovative eCommerce solutions they need to thrive.

[mglaman]: https://www.drupal.org/u/mglaman
[commerceguys]: https://commerceguys.com/
