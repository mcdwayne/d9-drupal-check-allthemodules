# Migrate Override

The purpose of this module is to allow an editor to prevent a regularly recurring update feed from overwriting edited values in an entity.

Condsider a site maintaining a list of content from an external source, either a feed, or spreadsheet.  This list might maintain and update content within this list. And editor might want to override a single field of a single entity while leaving the rest of the feed intact.  This module allows them to do that.

By enabling the module for a particular entity bundle and associated fields, the module will prevent any migrations using the content entity destination from overwriting that field, allowing an editor to make changes and have them persist even if the migration is ran again.

# Requirements

This module requires the following modules:
 * Migrate (https://www.drupal.org/project/migrate)
 * Field

# Maintainers

Current maintainers:
 * Michael Lutz (mikelutz) - https://www.drupal.org/u/mikelutz
 * AngryWookie (angrywookie) - https://www.drupal.org/u/angrywookie
