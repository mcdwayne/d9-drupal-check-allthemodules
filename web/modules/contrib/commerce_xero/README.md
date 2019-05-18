# Commerce Xero

Provides integration between your Drupal Commerce site and Xero accounting system.

**Warning** This branch is not production-ready, but offers a starting point for iterative development. See [DEVELOPER.md](./DEVELOPER.md) for details. 

## Requirements

* Install code via composer:

`composer require drupal/commerce_xero`

* Install code via git for development:

`git clone --branch 8.x-1.x https://git.drupal.org:/project/commerce_xero.git modules/commerce_xero`
`git clone --branch 8.x-1.x https://git.drupal.org:/project/xero.git modules/xero`
`git clone --branch 8.x-2.x https://git.drupal.org:/project/commerce.git modules/commerce`
`git clone --branch 8.x-1.x https://git.drupal.org:/project/address.git modules/address`
`composer config repositories.drupal composer https://packages.drupal.org/8`
`composer require commerceguys/intl commerceguys/addressing:~1.0 mradcliffe/xeroclient drush/drush:~9.0.0`
