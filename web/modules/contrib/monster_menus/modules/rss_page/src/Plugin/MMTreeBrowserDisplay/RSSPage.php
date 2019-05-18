<?php

namespace Drupal\rss_page\Plugin\MMTreeBrowserDisplay;

use Drupal\monster_menus\MMTreeBrowserDisplay\MMTreeBrowserDisplayInterface;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Fallback;

/**
 * Provides the MM Tree display generator for pages browsed as input for RSS
 * Page.
 *
 * @MMTreeBrowserDisplay(
 *   id = "mm_tree_browser_display_rss",
 *   admin_label = @Translation("MM Tree RSS Page display"),
 * )
 */
class RSSPage extends Fallback implements MMTreeBrowserDisplayInterface {

  const BROWSER_MODE_RSS = 'rss';

  /**
   * @inheritDoc
   */
  public static function supportedModes() {
    return [self::BROWSER_MODE_RSS];
  }

  /**
   * @inheritDoc
   */
  public function label($mode) {
    return t('Select a page to display');
  }

  /**
   * @inheritDoc
   */
  public function showReservedEntries($mode) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRightButtons($mode, $query, $item, $permissions, &$actions, &$dialogs) {
    static::selectLink($mode, $query, $item, $permissions, $actions);
  }

}
