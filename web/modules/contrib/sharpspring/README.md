## Introduction

A Drupal module to track site activity with [SharpSpring](http://www.sharpspring.com/). Adapted from the [GA contrib](https://www.drupal.org/project/google_analytics) module.

This includes the ability to track: landing pages, search terms, and referrers. Additionally, you can store your Sharpspring API credentials to be made available to related modules or for other advanced integrations.


## Requirements

- Drupal 7.x
- Sharpspring account


## Recommended Modules

- [Sharpspring Webforms](https://www.drupal.org/project/sharpspring_webforms): Extends the SharpSpring module's functionality to add SharpSpring lead tracking to Drupal Webforms. Works with Webform version 3 and 4.
- [Sharpspring Personalize](https://www.drupal.org/project/sharpspring_personalize): Makes SharpSpring user data available to the [Personalize](https://www.drupal.org/project/personalize) module for conditional rules.


## Installation

- Install as you would normally install a contributed Drupal module. See: https://drupal.org/documentation/install/modules-themes/modules-7 for further information.


## Configuration

- Navigate to admin/config/system/sharpspring to configure the SharpSpring module.
- Add your Web Property ID and Web Property Domain in the Tracking Settings fieldset. For more information about how to find these visit [Sharpspring's help documentation](http://help.sharpspring.com/customer/portal/articles/1497453-how-to-insert-sharpspring-tracking-code-how-to-add-additional-sites).
- *Note: without a Web Property ID and Web Property Domain, this module will not function.*
- Optionally, in the API Settings fieldset, add your Account ID and Secret Key. This module does not use these values out of the box, but will make them available to related modules and for advanced integrations.
