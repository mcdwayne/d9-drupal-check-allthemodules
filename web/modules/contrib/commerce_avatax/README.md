CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The [Drupal Commerce Connector for Avatax] is a Drupal compliant module that
integrates the Drupal Commerce with [AvaTax from Avalara, Inc.] and is used for
tax calculations and tax compliance.

AvaTax reduces the audit risk to a company with a cloud-based sales tax
services that makes it simple to do rate calculation while managing exemption
certificates, filing forms and remitting payments.

The tax is calculated based on the delivery address, the sales tax codes
assigned to line item in the order, and the sales tax rules applicable to the
states in which Nexus has been configured.

Access to a full development account can be requested by contacting Avalara,
Inc.

The service uses the AvaTax Rest api v2 for processing transactions.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/commerce_avatax

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/commerce_avatax


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * Commerce - https://www.drupal.org/project/commerce


INSTALLATION
------------

 * As with Drupal Commerce, and its contributed modules, you must install with
   Composer.

```bash
composer require drupal/commerce_avatax:^1.0
```

 * Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module and its
       dependencies.
    2. Navigate to Administration > Commerce > Configuration > AvaTax settings
       for configuration settings.
    3. Select the mode to use when calculating taxes: Development or Production.
    4. Enter the Account ID, License key, and Company code. Validate
       credentials.
    5. You can get the code from https://taxcode.avatax.avalara.com.
    6. Save configuration.


MAINTAINERS
-----------

 * Jonathan Sacksick (jsacksick) - https://www.drupal.org/u/jsacksick
 * Bojan Živanović (bojanz) - https://www.drupal.org/u/bojanz
 * Matt Glaman (mglaman) - https://www.drupal.org/u/mglaman
 * Steve Oliver (steveoliver) - https://www.drupal.org/u/steveoliver

[Drupal Commerce Connector for Avatax]: https://www.drupal.org/project/commerce_avatax
[AvaTax from Avalara, Inc.]: https://www.avalara.com/products/sales-and-use-tax/avatax-2
