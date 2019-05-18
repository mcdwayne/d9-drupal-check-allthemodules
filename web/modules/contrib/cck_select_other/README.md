## CCK Select Other

Provides a Select Other widget for list fields such as list (text), list (integer), and list (float). Use this module if you want users to provide an alternate value for your _list_ field. You may use [Select (or other)](http://drupal.org/project/select_or_other) if you want to provide an alternate value for your _text_ fields.

### Features

1. Support any list field with the exception of list (boolean).
2. Provide a field formatter.
3. Views field and filter support.
4. Uses Allowed Values in Drupal 8.

### Migrate

- No migration path is ready for Drupal 6 or Drupal 7 yet.

### Known Issues

- [i18n](http://drupal.org/project/i18n) is not supported due to i18n not correctly supporting form element children.

### Technical Information

This module rewrites the Constraint validator for Allowed Values. Any field that has an instance using this widget will bypass allowed values. This is by design due to Drupal 8 "loose coupling" as Drupal 8 does not provide a method to override field validation by a field widget.
