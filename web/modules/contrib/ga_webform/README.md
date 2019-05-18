Googalytics Webform
===================

This module provides integration of [Webform](https://www.drupal.org/project/webform) into
[Googalytics](https://www.drupal.org/project/ga) module in order to be able to track Webform submissions in Google
Analytics.

It bridges the two modules by providing a Webform submission handler preparing the tracking event, as well as a
Googalytics tracking event subscriber that actually sends the command.

[Issue Tracker](https://www.drupal.org/project/issues/ga_webform?version=8.x)

## Requirements

* Drupal 8
* [Googalytics](https://www.drupal.org/project/ga)
* [Webform](https://drupal.org/project/webform) 

## Installation

It is recommended to use [Composer](https://getcomposer.org/) to get this module with all dependencies:

```
composer require "drupal/ga_webform"
```

See the [Drupal](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies)
documentation for more details.

## Credits

Googalytics Webform module was originally developed and is currently maintained
by [Mag. Andreas Mayr](https://www.drupal.org/u/agoradesign).

All initial development was sponsored by
* [agoraDesign KG](https://www.agoradesign.at)
