<?php
namespace Drupal\monster_menus;

/**
 * @class Class used to hold group data for import with mm_create_path().
 */
class MMExportGroup extends MMCreatePathGroup {

  public static function __set_state($array) {
    _mm_export_prepend_groups_mmtid($array);
    return new self($array);
  }

}
