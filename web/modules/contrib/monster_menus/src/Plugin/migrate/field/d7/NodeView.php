<?php

namespace Drupal\monster_menus\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for mm schedule migrations.
 *
 *
 * @MigrateField(
 *   id = "node_view_field",
 *   core = {7},
 *   type_map = {
 *     "viewfield" = "entity_reference",
 *   },
 *   source_module = "mm_view",
 *   destination_module = "monster_menus",
 * )
 */
class NodeView extends FieldPluginBase {}