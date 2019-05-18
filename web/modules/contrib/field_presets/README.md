# Field Presets Module

## Introduction

The Field Presets module enables creating of fields using a preset. On creation of a new field using the module, the following happens:

- Field storage configuration is created
- Field configuration is created
- Field widget is set and field is added to the bottom of all form displays
- Field formatter is set and field is added to the bottom of all view displays

The module adds a "Add field using preset" local task on all entity manage fields pages.

This module is essentially a developer module and depends on presets being created and supplied in a *.field_presets.yml file. Two examples have been included in the `field_presets.field_presets.yml.dist` file.


## Requirements

No special requirements.


## Installation

Install as you would normally install a contributed Drupal module. See: https://www.drupal.org/documentation/install/modules-themes/modules-8


## Configuration

This module has no configuration settings. All configuration is through the presets themselves which are defined in *.field_presets.yml files.


## Preset structure

A preset has the following structure:

preset_id:
  machine_prefix:
  label:
  storage:
    ...
  instance:
    ...
  widget:
    ...
  formatter:
    ...

Explanation of tags:
- preset_id: the unique preset identifier
- machine_prefix: (optional) prefix to insert into the machine name of the field
- label: short label for this preset which will show in the select box on the
  add field using preset page
- storage: settings for the field storage go underneath this tag
- instance: (optional) for the field instance go underneath this tag
- widget: settings for the field widget go underneath this tag
- formatter: settings for the field formatter go underneath this tag

Note that a number of defaults are used for the storage/instance/widget/formatter sections (see src/FieldPresetManager.php for details) and so many tags can be omitted. Contrast the two example presets that are included.


## Maintainers

Current Maintainers:
* Michael Welford - https://www.drupal.org/u/mikejw

This project has been sponsored by:
* The University of Adelaide - https://www.drupal.org/university-of-adelaide
  Visit: http://www.adelaide.edu.au
