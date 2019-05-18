CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Troubleshooting
* Maintainers

Introduction
------------

Commerce Moneris provides a token based integration with the Moneris payment gateway. It currently only supports onsite payments and not the Hosted Pay Page option. It is also not the lowest level of compliance because credit card numbers do pass through the Drupal site, they are not store, but they are routed through.

Requirements
------------

A forked version of the Moneris SDK is installed via composer and required to use this. A forked version is used because the default version provided by moneris is out of date and doesn't work with PHP7 as well as isn't setup for composer.

https://packagist.org/packages/smmccabe/moneris

Installation
------------

Install via composer so you get the appropriate SDK, otherwise nothing extra is required.

Configuration
-------------

You will need to add a payment gateway in Drupal commerce, you should see a Moneris option. Once selected it will show the moneris specific options, you should have a store_id and a api_token provided by Moneris that you can add. You will also want to select US vs CDN, this needs to match your account, as accounts are country specific. It is also recommended to keep AVS validation on, but you account must also support it.

Troubleshooting
---------------

Most problems are related to account configuration, make sure your credentials are correct and that your settings match with Drupal.  The error messages from Moneris should help point you in the right direction.

Maintainers
-----------

Current maintainers:

* Shawn McCabe (smccabe) - https://www.drupal.org/u/smccabe

This project has been sponsored by:

* Acro Media - https://www.acromedia.com/
