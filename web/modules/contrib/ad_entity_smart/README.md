Smart AdServer
==============

This module provides integration between the [Advertising Entity](https://www.drupal.org/project/ad_entity) module and [Smart AdServer](http://smartadserver.com/). 

## Setup

1. Install the module
1. Visit /admin/structure/ad_entity/global-settings
1. Navigate below to the :Smart AdServer types" group.
1. Set the settings or your Smart AdServer account: Site ID, Nerwork ID, Domain.
1. Create ad entities at /admin/structure/ad_entity
1. Set the ad name and Format id while creating an ad entity. In addition use the default view handler.
1. Create display configuration for your ad entity at /admin/structure/ad_entity/display
1. Display the ad at your page. E.g. you can show it as a block at /admin/structure/block.

## Alter targeting

You can alter ads targeting either in PHP or in javascript.

### Alter targeting in PHP

Use `hook_ad_entity_smart_target_alter`.

### Alter targeting in javascript

You can alter targeting in javascript using `Drupal.ad_entity_smart.targetAlters` callbacks.

Example:

```
  Drupal.ad_entity_smart = Drupal.ad_entity_smart || {};
  Drupal.ad_entity_smart.targetAlters = Drupal.ad_entity_smart.targetAlters || {};
  Drupal.ad_entity_smart.targetAlters.test = function(targeting) {
    targeting['test'] = 'test';
  };
```
