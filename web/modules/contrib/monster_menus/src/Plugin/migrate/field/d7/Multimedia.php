<?php

namespace Drupal\monster_menus\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for mm schedule migrations.
 *
 *
 * @MigrateField(
 *   id = "multimedia_field",
 *   core = {7},
 *   type_map = {
 *     "media" = "entity_reference",
 *   },
 *   source_module = "mm_media",
 *   destination_module = "monster_menus",
 * )
 */
class Multimedia extends FieldPluginBase {}