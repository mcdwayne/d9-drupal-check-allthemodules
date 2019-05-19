Statistics API DATA
---------

This module provided a custom content entity, meant for
Statistics API plugins to use to record statistical information.

The primary component is a bundles custom content entity.
Any module that creates plugins can create a new bundle and
assign fields to it.  Plugins can then create data entries
by creating entity instances.

## Management

The Statistics API data structure pages holds most of the controls

    /admin/structure/sapi

- data entry types : manage the type bundles
- data entries : manage the recorded entries
- settings : a settings form (currently empty)

### Entry types

Here you can create different data bundles

### Entries

Here you can manage the actual entry entities themselves

## Creating a Data Entry

````
<?php

/**
 * If we have a SAPI data bundle/type called MySAPIDataType
 * that has a field added called MyDataField, then we can
 * create and save an entity like this
 */

$value = "new value";

/** var \Drupal\sapi_data\SAPIDataInterface $entry */
$entry = \Drupal::service('entity_type.manager')->getStorage('sapi_data')->create(['type'=>'MySAPIDataType']);

$entry->MyDataField = $value;

$entry->save();

````
