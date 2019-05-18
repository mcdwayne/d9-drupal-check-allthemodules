Googalytics Commerce
====================

This module provides integration of [Drupal Commerce](https://www.drupal.org/project/commerce) into
[Googalytics](https://www.drupal.org/project/ga) module by implementing the Google Analytics Ecommerce Tracking commands
upon placing an order.

At least currently, only the [Ecommerce Tracking](https://developers.google.com/analytics/devguides/collection/analyticsjs/ecommerce)
is supported, not the Enhanced one. This is ideal, if you only want to track placed orders (totals, as well as
individual order items).

There's also an open issue in Googalytics module itself about providing Enhanced Ecommerce Tracking integration:
[#2712365: Add Enhanced Ecommerce Commands](https://www.drupal.org/project/ga/issues/2712365)

Currently, the module is plug & play. It of course depends on both Googalytics (ga) and Commerce Order (commerce_order)
sub module of Drupal Commerce. If you install this module, it will automatically start tracking purchases, as long as
the ga module is configured to actively track.

[Issue Tracker](https://www.drupal.org/project/issues/ga_commerce?version=8.x)

## Requirements

* Drupal 8
* [Googalytics](https://www.drupal.org/project/ga)
* [Commerce 2](https://drupal.org/project/commerce) (commerce_order sub module) 

## Installation

It is recommended to use [Composer](https://getcomposer.org/) to get this module with all dependencies:

```
composer require "drupal/ga_commerce"
```

See the [Drupal](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies)
documentation for more details.

## Credits

Googalytics Commerce module was originally developed and is currently maintained
by [Mag. Andreas Mayr](https://www.drupal.org/u/agoradesign).

All initial development was sponsored by
* [agoraDesign KG](https://www.agoradesign.at)
