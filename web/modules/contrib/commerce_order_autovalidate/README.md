Commerce Order auto-validation
===============
This module automatically validates Drupal Commerce orders that are paid in full. This module therefore only makes
sense to use, if you use an order workflow that uses the "validate" transition.

[Issue Tracker](https://www.drupal.org/project/issues/commerce_order_autovalidate?version=8.x)

## Requirements

Commerce Order auto-validation depends on Drupal Commerce of course, given a strict dependency on commerce_order and
commerce_payment sub modules.

## Installation

It is recommended to use [Composer](https://getcomposer.org/) to get this module with all dependencies:

```
composer require "drupal/commerce_order_autovalidate"
```

See the [Drupal](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies)
documentation for more details.

### Credits

Commerce Order auto-validation module was originally developed and is currently maintained by
[Mag. Andreas Mayr](https://www.drupal.org/u/agoradesign).

All initial development was sponsored by [agoraDesign KG](https://www.agoradesign.at).
