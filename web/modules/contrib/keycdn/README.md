Introduction
=======================

This module:
    1. Sets the Cache-Tag http header required by KeyCDN for tag based Purging in Drupal 8. 
       No config is required for this.
    2. Offers a purger plugin for invalidating via tag at KeyCDN. See below.

To enable purging, browse to admin/config/development/performance/purge and 
add the purger provided by this module. To configure the purger, you need 
a KeyCDN key and region name information. Visit https://www.keycdn.com/ to 
get that info.

Maintainers
=================
- Moshe Weitzman <weitzman@tejasa.com> https://www.drupal.org/u/moshe-weitzman
- Rohit Joshi (joshi.rohit100) https://www.drupal.org/u/joshirohit100

