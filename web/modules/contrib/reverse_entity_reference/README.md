INTRODUCTION
============

Reverse Entity Reference adds a computed reverse reference field to all
entities referenced by another entity's field. This module is based on 
Drupal 7's [Entityreference Backreference][]. This modules only provides 
backreferences for `entity_reference` fields towards fieldable entity types.

REQUIREMENTS
============

 * [Dynamic Entity Reference][]

INSTALLATION
============

 * Install as usual, 
   see https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for more information

CONFIGURATION
=============

 * By default this module supports `entity_reference`, however, it can support 
   any reference field that is a subclass of `EntityReferenceItem`. 
   Navigate to `/admin/config/system/reverse-entity-reference` to choose the 
   entity reference support. This is a global setting at the moment.
 * You can also consider using the programmatic alternative, but be careful of
   using field types that don't extend `EntityReferenceItem` because they might
   not work.
 * Other available global configs:
   * `allowed_field_types`
     * A list of field types that should be considered a reference.
   * `disallowed_entity_types`
     * A list of entity types that you do not want to be reverse referenced.
   * `allow_custom_storage` 
     * Allow references from entities with custom storage.
       _May have memory issues if there are many reverse references._

Changing Allowed Field Types config
-----------------------------------

```php
\Drupal::config('reverse_entity_reference.settings')
  ->set('allowed_field_types', $new_allowed_field_types_value)
  ->save();
```

 * You can view reverse entity references by changing the display mode in the
   configurations for each bundle/entity type.
   * i.e. see `/admin/structure/types/manage/page/display` for Page nodes.
 * This can also be done programmatically

Changing Display mode config
----------------------------

```php
// Basic formatters:
// $reverse_reference_formmater = "reverse_entity_reference_entity_view";
// $reverse_reference_formmater = "reverse_entity_reference_entity_id";
// $reverse_reference_formmater = "reverse_entity_reference_label";
\Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load($entity_type . '.' . $bundle . '.' . $view_mode)
  ->setComponent('reverse_entity_reference', array(
    'type' => $reverse_reference_formatter,
    'weight' => -5
  ) + $other_settings)
  ->save();
```

 * Also I have not tested on every class that extends `EntityReferenceItem`
   so it might not work in those cases either. 
 * Covered field types:
   * File
   * Image
   * Entity Reference

EXAMPLE USAGE
=============

The `reverse_entity_reference` field gives a table of all the entities that 
referencing this entity. If you want it for a specific field though you could
use the method shown above to filter out the information you don't need.

```php
$referencing_entities = \Drupal::service('entity_type.manager')
  ->getStorage($referenced_entity_type)
  ->load($referenced_entity_id)
  ->get('reverse_entity_reference')
  ->getValue();

$entity_ids = array_intersect(
  array_column($referencing_entities, 'referring_entity_type'),
  $referencing_entity_types
);

$field_names = array_intersect(
  array_column($referencing_entities, 'field_name'),
  $referencing_field_names
);

$wanted = array_intersect_key($referencing_entities, $entity_ids, $field_names);
```

CONTRIBUTORS
============

Current Maintainers:
 * Gerald Aryeetey (GeraldNDA) -  https://www.drupal.org/u/geraldnda

[Entityreference Backreference]:
https://www.drupal.org/project/entityreference_backreference
[Dynamic Entity Reference]:
https://www.drupal.org/project/dynamic_entity_reference
