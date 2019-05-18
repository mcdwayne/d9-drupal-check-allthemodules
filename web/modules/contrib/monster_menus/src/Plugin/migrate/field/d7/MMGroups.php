<?php

namespace Drupal\monster_menus\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for mm schedule migrations.
 *
 *
 * @MigrateField(
 *   id = "mm_groups_field",
 *   core = {7},
 *   type_map = {
 *     "mm_fields_mm_grouplist" = "mm_grouplist",
 *   },
 *   source_module = "mm_schedule",
 *   destination_module = "monster_menus",
 * )
 */
class MMGroups extends FieldPluginBase {}