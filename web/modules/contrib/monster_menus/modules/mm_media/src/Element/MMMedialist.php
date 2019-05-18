<?php
/**
 * @file
 * Contains \Drupal\monster_menus\Element\MMNodelist.
 */

namespace Drupal\mm_media\Element;

use Drupal\mm_media\Plugin\MMTreeBrowserDisplay\Media;
use Drupal\monster_menus\Element\MMCatlist;

/**
 * Provides a graphical chooser for nodes within the MM Tree.
 *
 * @FormElement("mm_medialist")
 */
class MMMedialist extends MMCatlist {

  /**
   * @inheritdoc
   */
  public function getInfo() {
    return [
      '#mm_list_mode' => Media::BROWSER_MODE_MEDIA,
    ] + parent::getInfo();
  }

  /**
   * @inheritdoc
   */
  public static function preRender($element) {
    MMCatlist::preRenderMMList($element['#mm_list_mode'], $element, 0, t('Path:'));
    return $element;
  }

}
