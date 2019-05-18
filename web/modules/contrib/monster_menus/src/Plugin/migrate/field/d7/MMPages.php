<?php

namespace Drupal\monster_menus\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for mm pages migrations.
 *
 *
 * @MigrateField(
 *   id = "mm_pages",
 *   core = {7},
 *   type_map = {
 *     "mm_fields_mm_catlist" = "mm_catlist",
 *   },
 *   source_module = "mm_schedule",
 *   destination_module = "monster_menus",
 * )
 */
class MMPages extends FieldPluginBase {}