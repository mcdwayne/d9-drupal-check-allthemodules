Advertising Entity: Video Intelligence
======================================

This module provides integration between the [Advertising Entity](https://www.drupal.org/project/ad_entity) module and [Video Intelligence](https://www.vi.ai/). 

## Setup

1. Install the module
1. Visit /admin/structure/ad_entity/global-settings
1. Navigate below to the "Video Intelligence types" group.
1. Set all the necessary the settings for your Video Intelligence account: Channel ID, Publisher ID, etc.
1. Create ad entity at /admin/structure/ad_entity
1. Set the ad name and optionally keywords while creating an ad entity. In addition use the default view handler.
1. Create display configuration for your ad entity at /admin/structure/ad_entity/display
1. Display the ad at your page. E.g. you can show it as a block at /admin/structure/block.

## Alter keywords

You can alter VI ad keywords  using `hook_ad_entity_vi_keywords_alter`.
