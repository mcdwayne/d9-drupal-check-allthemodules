<?php
namespace Drupal\monster_menus;

use Drupal\monster_menus\MMCreatePath\MMCreatePathCat;

/**
 * @class Class used to hold page data for import with mm_create_path().
 */
class MMExportCat extends MMCreatePathCat {

  public static function __set_state($array) {
    _mm_export_prepend_groups_mmtid($array);
    return new self($array);
  }

}
