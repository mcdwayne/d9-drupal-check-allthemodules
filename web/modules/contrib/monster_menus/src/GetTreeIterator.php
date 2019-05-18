<?php
namespace Drupal\monster_menus;

/**
 * Class used with mm_content_get_tree to take an action as each item is found.
 */
class GetTreeIterator {

  /**
   * @param $item
   *   Tree item to be operated upon. IMPORTANT: Do not depend on $item->state
   *   here. It is not correct.
   * @return int
   *   Function must return 1 if no error, 0 if error, -1 if this item and any
   *   of its children should be skipped.
   */
  public function iterate($item) {
    return 1;
  }

}
