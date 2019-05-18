<?php

namespace Drupal\monster_menus\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for mm schedule migrations.
 *
 *
 * @MigrateField(
 *   id = "mult_thumb",
 *   core = {7},
 *   type_map = {
 *     "mm_fields_mm_nodelist" = "entity_reference",
 *   },
 *   source_module = "mm_media",
 *   destination_module = "monster_menus",
 * )
 */
class MultThumb extends FieldPluginBase {}