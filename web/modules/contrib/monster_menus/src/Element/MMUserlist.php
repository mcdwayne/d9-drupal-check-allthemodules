<?php
/**
 * @file
 * Contains \Drupal\monster_menus\Element\MMUserlist.
 */

namespace Drupal\monster_menus\Element;

use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Users;

/**
 * Provides a graphical user chooser.
 *
 * @FormElement("mm_userlist")
 */
class MMUserlist extends MMCatlist {

  public function getInfo() {
    $info = parent::getInfo();
    unset($info['#mm_list_info_func']);
    $info['#mm_list_mode'] = Users::BROWSER_MODE_USER;
    return $info;
  }

  public static function preRender($element) {
    if (empty($element['#title'])) {
      $element['#title'] = $element['#mm_list_max'] == 1 ? t('User:') : t('Users:');
    }
    MMCatlist::preRenderMMList($element['#mm_list_mode'], $element, 0, t('Path:'));
    return $element;
  }

}