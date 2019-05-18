INTRODUCTION
------------

Reference Map is a utility module for other modules and doesn't provide any
useful functionality out of the box.

This module defines complex relationships between entities based on entity
reference fields. For example, consider a custom pet entity, a Drupal core user
entity, and a family taxonomy vocabulary. The pet has an owner reference field,
and the owner has a family reference field. This module will allow a map to be
created that, given a pet, returns the related family id. In addition, the map
allows for all pets ids to be returned given a family.


REQUIREMENTS
------------

No special requirements.


INSTALLATION
------------

Install the module normally.


CONFIGURATION
-------------

Reference Map's configuration is managed at /admin/config/system/reference-maps.
The module provides a ReferenceMapTypeBase class that includes basic required
functionality for all reference map plugins to be derived from. An Entity
reference map plugin is also included with the module.

Each reference map includes minimum Label, Machine Name and Map fields. The map
field is an indexed array of steps input as YAML. Each step is an associative
array containing the following:

  * entity_type: (Required)
    The entity type that the step applies to.
  * bundles: (Optional)
    An indexed array of bundles that the step applies to. If not provided, the
    step applies to all bundles.
  * field_name: (Required on all but the last step)
    The field referencing the entity in the next step.

The map must include a minimum of two steps, the first containing the source
entity type and the last containing the destination entity type. Each step that
is not the destination (last) step must include a field_name key that points to
the entity type in the next step. Below is a map of the example pet > owner >
family scenario outlined in the introduction.

```YAML

-
  entity_type: pet
  bundles:
    - dog
    - cat
  field_name: field_owner
-
  entity_type: user
  field_name: field_family
-
  entity_type: family

```


USE
---

To use a reference map, you must first load the Reference Map Type Manager
service (plugin.manager.reference_map_type). Next you can load a specific
reference map by calling the getInstance() method on the service and passing in
an array containing a Reference Map Type id (plugin_id) and a Reference Map
Config id (map_id).

```php
getInstance([
  'plugin_id' => 'REFERENCE_MAP_TYPE_ID',
  'map_id' => 'REFERENCE_MAP_CONFIG_ID',
]);
```

Finally you can return all source or destination entities by either passing a
source entity to the follow() method or a destination entity to the
followReverse() method.


MAINTAINERS
-----------

Current maintainers:

  * Charles Bamford https://www.drupal.org/u/c7bamford
  * Jon Antoine https://www.drupal.org/u/jantoine

This project was sponsored by:

  * ANTOINE SOLUTIONS  
    Specialized in consulting and architecting robust web applications powered
    by Drupal.
