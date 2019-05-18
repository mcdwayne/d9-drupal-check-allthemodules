##Migrate Source iCal

This module provides a source plugin to migrate iCal data.

Requirements:
-

Usage:

# Example configuration for migrating date from .ics.
id: events
class: null
field_plugin_method: null
cck_plugin_method: null
migration_tags: null
migration_group: ical
label: Events
source:
  plugin: ical
  high_water_property:
    name: lastmodified
  path: 'https://example.com/basic.ics'
  identifier: upc
  identifierDepth: 1
  fields:
    - uid
    - summary
    - description
    - lastmodified
    - dtstart
    - dtend
    - location
  keys:
    - uid
process:
  type:
    plugin: default_value
    default_value: events
  title: summary
  body: description
  field_event_date/value:
    -
      plugin: format_date
      from_format: Ymd
      to_format: Y-m-d
      source: dtstart
  field_event_date/end_value:
    -
      plugin: format_date
      from_format: Ymd
      to_format: Y-m-d
      source: dtend
  sticky:
    plugin: default_value
    default_value: 0
  status: published
  uid:
    plugin: default_value
    default_value: 1
destination:
  plugin: 'entity:node'
migration_dependencies: {  }
