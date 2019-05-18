<?php
namespace Drupal\monster_menus;

use Drupal\Core\Database\Database;

/**
 * Class used internally, to provide the ability to store the most recent
 * result and re-retrieve it later.
 */
class GetTreeResults {

  private $query_obj, $prev, $backed;
  public $start_level, $level_offset;

  public function __construct($query) {
    $this->query_obj = Database::getConnection()->query($query);
  }

  public function next() {
    if (!empty($this->backed)) {
      $this->backed = FALSE;
      return $this->prev;
    }
    return $this->prev = $this->query_obj->fetchObject();
  }

  public function back() {
    $this->backed = TRUE;
  }

}
