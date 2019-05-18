# Changelog

## [Unreleased][unreleased]

No changes at this moment.

## [1.0-alpha2]

- Improved documentation.
- Improved code quality.
- Added container injection support for plugins.
- Removed request() method from JsldPathPlugin, use container injection instead.
- Improved plugin generators for Drush. Now they with examples of container injection.
- From now, each structured data from plugin will have a personal script tag with data, not all in one place.
- Refactored Drush generator command `drush generate jsld`.
- Added predefined examples for common structured data type when using drush generator.

[1.0-alpha2]: https://github.com/Niklan/jsld/compare/8.x-1.0-alpha1...8.x-1.0-alpha2
