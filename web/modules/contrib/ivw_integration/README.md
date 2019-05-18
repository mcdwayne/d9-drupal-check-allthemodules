# IVW Integration
## Introduction

Integrates the german audience measuring organisation 
[IVW](http://www.ivw.eu/). Displays a configurable SZM pixel. The pixel
can be overriden for terms and all articles relating to this term, or 
by single articles.

## Requirements

 * Token module

## Installation

Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8
for further information.

## Configuration

You need to be registered  for IVW before using the module. You well get
a site name and mobile site name for your site which can be configured
on admin/config/system/ivw.
On this page you can also configure various aspects of your measurement.
If you select a checkbox, that makes a value overridable, you can 
override this settings on terms and nodes, that have a field of the 
field type "ivw_integration_settings"
