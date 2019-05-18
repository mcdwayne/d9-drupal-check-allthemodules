<?php

namespace Drupal\monster_menus\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for mm schedule migrations.
 *
 *
 * @MigrateField(
 *   id = "userlist",
 *   core = {7},
 *   type_map = {
 *     "mm_fields_mm_userlist" = "mm_userlist",
 *   },
 *   source_module = "mm_media",
 *   destination_module = "monster_menus",
 * )
 */
class UserList extends FieldPluginBase {}