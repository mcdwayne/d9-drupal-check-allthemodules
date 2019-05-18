<?php
/**
 * @file
 * Contains \Drupal\monster_menus\Element\MMGrouplist.
 */

namespace Drupal\monster_menus\Element;

use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Groups;

/**
 * Provides a graphical chooser for MM Tree group entries.
 *
 * @FormElement("mm_grouplist")
 */
class MMGrouplist extends MMCatlist {

  /**
   * @inheritdoc
   */
  public function getInfo() {
    return [
        '#mm_list_mode' => Groups::BROWSER_MODE_GROUP,
      ] + parent::getInfo();
  }

  public static function preRender($element) {
    if (empty($element['#title'])) {
      $element['#title'] = $element['#mm_list_max'] == 1 ? t('Group:') : t('Groups:');
    }
    MMCatlist::preRenderMMList($element['#mm_list_mode'], $element, mm_content_groups_mmtid(), t('Users in selected group:'));
    return $element;
  }

  // Helper function to pre-generate an entry in the list.
  private static function makeEntry($mmtid, $name, &$url, &$info, &$popup_URL) {
    $parents = mm_content_get_parents($mmtid);
    array_shift($parents);  // skip root
    $url = implode('/', $parents);

    if (!isset($popup_URL)) {
      $popup_URL = $url;
    }

    $url .= "/$mmtid";

    $info = mm_content_get_users_in_group($mmtid, '<br />', FALSE, 20, TRUE);
    if ($info == '') {
      $info = t('(none)');
    }
  }

}