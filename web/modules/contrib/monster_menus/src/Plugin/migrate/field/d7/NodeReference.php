<?php

namespace Drupal\monster_menus\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for mm schedule migrations.
 *
 *
 * @MigrateField(
 *   id = "node_reference_field",
 *   core = {7},
 *   type_map = {
 *     "node_reference" = "entity_reference",
 *   },
 *   source_module = "mm_schedule",
 *   destination_module = "monster_menus",
 * )
 */
class NodeReference extends FieldPluginBase {}