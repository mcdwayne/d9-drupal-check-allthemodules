# Contributing to Datetime Extras

This module aims to be a set of widgets, formatters, and other tools to extend
the core Datetime and Datetime Range modules. It serves as both a staging ground
to get new features into core, and also as a place to centrally collect and
maintain features that should live in contrib.

While existing as a contrib module, this code SHOULD follow Drupal best
practices, such as:
- [coding standards](https://www.drupal.org/docs/develop/standards)
- [test coverage](https://www.drupal.org/docs/8/testing)
For code to be included in core, features MUST follow these practices.

## Naming Conventions

### Field plugin IDs

```{field-type}_{feature-name}```

Every field-related plugin in here will operate on a specific field
type. Generally either ```datetime``` or ```daterange```. That MUST be the first
part of the plugin ID.

Since the plugins already know what kind of thing they are (widget, formatter,
etc) via namespace and otherwise, we don't want that also in the ID.

The ```_{feature-name}``` part tells you what feature is provided by that plugin
and guarantees uniqueness. If the feature wants multiple words, separate with
more underscores.

For example:

 - ```daterange_duration```
 - ```daterange_compact```
 - ```datetime_datelist_no_time```

### Other plugin IDs

TBD

None yet, but maybe we'll start adding other things (blocks, etc).

### Config IDs

MUST start with ```datetime_extras.``` (including the period).

Otherwise, attempt to match the ```_{feature-name}``` part of the ID from any
related plugins.

### Class names

Plugin classes MUST follow the agreed upon plugin ID format, plus existing core
conventions.

Examples:

```daterange_duration``` => ```DateRangeDurationWidget```
```daterange_compact``` => ```DateRangeCompactFormatter```
```datetime_datelist_no_time``` => ```DateTimeDatelistNoTimeWidget```

### Services

Service IDs MUST have at least:

```datetime_extras.{service-name}```

Service IDs SHOULD have the form:

```datetime_extras.{feature-name}.{service-name}```

Example ID: ```datetime_extras.daterange_compact.formatter```
Example class: ```Drupal\datetime_extras\DateRangeCompactFormatter```

***

Last Modified: 2019/02/09
