Test Case ID: admin
Author: Aaron Klump
Created: February 27, 2019
---
## Test Scenario

The admin form loads and saves new info as expected.

## Pre-Conditions

1. Make sure [Image Style Quality module](https://www.drupal.org/project/image_style_quality) is uninstalled.
1. Log in with proper permissions.

## Test Data

    _Retina filename suffix default: "@2x"
    _Retina filename regex default: (.+)(SUFFIX)\.(png|jpg|jpeg|gif)$
    _JPEG Quality Multiplier default: 1
    Retina filename suffix: "@.75x @1.5x @2x"
    Retina filename regex: (.+)(SUFFIX)\.(png|jpg|jpeg|gif|xyz)$
    Include the javascript settings...: checked
    JPEG Quality Multiplier: .5

## Test Execution

1. Visit the [admin page](/admin/config/media/image-styles/auto-retina)
1. Click on the _Advanced_ caption to expose more options.
  - Assert you see the admin form and there are no error messages related to this module.
  - Assert default value of Retina filename suffix is {{ _Retina filename suffix default }}
  - Assert default value of _JPEG Quality Multiplier_ is {{ _JPEG Quality Multiplier default }}
  - Assert there is a note in the description to install the _Image Style Quality_ module.
  - Assert default value of Retina filename regex is {{ _Retina filename regex default }}
  - Assert default value of Include the javascript settings... is unchecked
  - Assert you see a tab called _List_
  - Assert you see a tab called _Auto Retina_
1. Change _Retina filename suffix_ to {{ Retina filename suffix }}
1. Change _JPEG Quality Multiplier_ to {{ JPEG Quality Multiplier }}
1. Change _Retina filename regex_ to {{ Retina filename regex }}
1. Change _Include the javascript settings..._ to {{ Include the javascript settings... }}
1. Save the form
  - Assert reloaded value of _Retina filename suffix_ is {{ Retina filename suffix }}
  - Assert reloaded value of _Retina filename regex_ is {{ Retina filename regex }}
  - Assert reloaded value of _JPEG Quality Multiplier_ is {{ JPEG Quality Multiplier }}
  - Assert reloaded value of _Include the javascript settings..._ is {{ Include the javascript settings... }}
1. Enable the [Image Style Quality module](https://www.drupal.org/project/image_style_quality).
1. Reload the form
    - Assert the description for _JPEG Quality Multiplier_ no longer encourages you to install the _Image Style Quality_ module.
