Entity Autocomplete Extended
------

### Introduction

This module extends the core autocomplete widget for entity reference fields.
It allows for configuration of the maximum number of matching results shown.
(The core widget has a hard-coded limit of 10, which is the default limit for
this widget.)

### Field Widget Configuration

1. Enable the module
2. Manage the form display for any entity bundle with an entity reference
field.
3. Select "Autocomplete Extended" or "Autocomplete Extended (Tags Style)" as
the widget for the entity reference field.
4. Confirm that the settings summary includes the line "Maximum number of 
matching results shown: 10"
5. Click on the gear icon for the widget to configure the widget, and set the
"Maximum number of matching results shown" value to desired value.
6. Click update and confirm summary reflects change.

### Form/Render API

This module also provides a new `entity_autocomplete_extended` render element
type. The element type has all the properties of the `entity_autocomplete`
element, as well as an additional `#results_limit` property that sets the max
number of matching results shown. The element can be used in a form builder or
alter function. The default value of `#results_limit` is 10.

Example:
```
$form['field_entity_reference'] = [
  '#type' => 'entity_autocomplete_extended',
  '#results_limit' => 25,
  ...
];
```

***See: <https://www.drupal.org/project/entity_autcomplete_extended>***
