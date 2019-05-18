<?php
/**
 * @file
 * Contains \Drupal\monster_menus\Element\MMNodelist.
 */

namespace Drupal\monster_menus\Element;

use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Nodes;

/**
 * Provides a graphical chooser for nodes within the MM Tree.
 *
 * @FormElement("mm_nodelist")
 */
class MMNodelist extends MMCatlist {

  /**
   * @inheritdoc
   */
  public function getInfo() {
    return [
        '#mm_list_mode' => Nodes::BROWSER_MODE_NODE,
      ] + parent::getInfo();
  }

  public static function preRender($element) {
    if (empty($element['#title'])) {
      $element['#title'] = $element['#mm_list_max'] == 1 ? t('Node:') : t('Nodes:');
    }
    MMCatlist::preRenderMMList($element['#mm_list_mode'], $element, 0, t('Path:'));
    return $element;
  }

}
