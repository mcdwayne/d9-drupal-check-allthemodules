# Context Layout

Apply contextual layouts. This module integrates layout functionality with the
[context](http://drupal.org/project/context) module. It uses the Layout API
introduced into core in version 8.3. As a result, this module will not be
compatible with Drupal 8.2 or below.

## Usage

1. Register layouts in your theme or module, see [drupal.org documentation](https://www.drupal.org/docs/8/api/layout-api/how-to-register-layouts)
2. Add a context block reaction and specify a layout in the "layout" dropdown
3. Optional: Define global context layout behaviour (`/admin/config/system/context_layout`)

## Dependencies

* [Context](http://drupal.org/project/context) 8.x-4.0-beta2 or higher

## Further Reading

* [Context](http://drupal.org/project/context)
* [Layout API](https://www.drupal.org/docs/8/api/layout-api)

## Roadmap

* Investigate and implement tests
