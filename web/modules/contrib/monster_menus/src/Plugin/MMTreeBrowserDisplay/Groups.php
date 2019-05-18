<?php

namespace Drupal\monster_menus\Plugin\MMTreeBrowserDisplay;

use Drupal\monster_menus\Constants;
use Drupal\monster_menus\MMTreeBrowserDisplay\MMTreeBrowserDisplayInterface;

/**
 * Provides the MM Tree display generator for MMTree groups.
 *
 * @MMTreeBrowserDisplay(
 *   id = "mm_tree_browser_display_group",
 *   admin_label = @Translation("MM Tree group display"),
 * )
 */
class Groups extends Fallback implements MMTreeBrowserDisplayInterface {

  const BROWSER_MODE_GROUP = 'grp';
  const BROWSER_MODE_ADMIN_GROUP = 'agp';

  /**
   * @inheritDoc
   */
  public static function supportedModes() {
    return [self::BROWSER_MODE_GROUP, self::BROWSER_MODE_ADMIN_GROUP];
  }

  /**
   * @inheritDoc
   */
  public function label($mode) {
    return $mode == self::BROWSER_MODE_ADMIN_GROUP ? t('Browse groups') : t('Select a group');
  }

  /**
   * @inheritDoc
   */
  public function showReservedEntries($mode) {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function alterLeftQuery($mode, $query, &$params) {
    $params[Constants::MM_GET_TREE_FILTER_GROUPS] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRightButtons($mode, $query, $item, $permissions, &$actions, &$dialogs) {
    if ($mode == self::BROWSER_MODE_ADMIN_GROUP) {
      static::adminLinks($mode, $query, $item, $permissions, $actions);
    }
    if ($mode == self::BROWSER_MODE_GROUP) {
      static::selectLink($mode, $query, $item, $permissions, $actions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTreeTop($mode) {
    return mm_content_groups_mmtid();
  }

  /**
   * {@inheritdoc}
   */
  public function getBookmarksType($mode) {
    return 'browser_grp';
  }

}
