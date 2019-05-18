# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project follows [Drupal.org versioning](http://drupal.org/i/2815471).

## [Unreleased]

## [8.x-1.4] - 2019-04-22
Fixes related to code standards, string translations, installed and required configuration, ps-country and ps-language meta tags, asynchronously load external Pricespider JS, calling PriceSpider.rebind() during non-Document Drupal.attachBehaviors(). 

### Fixed
* [#3018878](https://www.drupal.org/node/3018878) by [butlerbryanc](https://www.drupal.org/u/butlerbryanc), [jasonawant](https://www.drupal.org/u/jasonawant): PriceSpider doesn't rebind on async renders
* [#3012143](https://www.drupal.org/node/3012143) by [kamkejj](https://www.drupal.org/u/kamkejj), [jasonawant](https://www.drupal.org/u/jasonawant): JS Library doesn't properly define the async attribute
* [#3011881](https://www.drupal.org/node/3011881) by [kamkejj](https://www.drupal.org/u/kamkejj), [jasonawant](https://www.drupal.org/u/jasonawant): Code standards updates
* [#3011872](https://www.drupal.org/node/3011872) by [kamkejj](https://www.drupal.org/u/kamkejj), [jasonawant](https://www.drupal.org/u/jasonawant): PriceSpiderService getting country code from language doesn't work
* [#3011858](https://www.drupal.org/node/3011858) by [kamkejj](https://www.drupal.org/u/kamkejj), [jasonawant](https://www.drupal.org/u/jasonawant): AdminSettingsForm fields not required, default values and code styling
* [#3024782](https://www.drupal.org/node/3024782) by [kkohlbrenner](https://www.drupal.org/u/kkohlbrenner), [jasonawant](https://www.drupal.org/u/jasonawant), [cosmicdreams](https://www.drupal.org/u/cosmicdreams): Install file incorrectly named
* [#2895595](https://www.drupal.org/node/2895595) by [jayesh_makwana](https://www.drupal.org/u/jayesh_makwana), [kamkejj](https://www.drupal.org/u/kamkejj), [jasonawant](https://www.drupal.org/u/jasonawant): $this->t() should be used instead of t() for Drupal 8 version