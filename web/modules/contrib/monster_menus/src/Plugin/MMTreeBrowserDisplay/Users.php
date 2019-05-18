<?php

namespace Drupal\monster_menus\Plugin\MMTreeBrowserDisplay;

use Drupal\monster_menus\Constants;
use Drupal\monster_menus\MMTreeBrowserDisplay\MMTreeBrowserDisplayInterface;

/**
 * Provides the MM Tree display generator for MMTree user homepages.
 *
 * @MMTreeBrowserDisplay(
 *   id = "mm_tree_browser_display_user",
 *   admin_label = @Translation("MM Tree user homepage display"),
 * )
 */
class Users extends Fallback implements MMTreeBrowserDisplayInterface {

  const BROWSER_MODE_USER = 'usr';
  const BROWSER_MODE_ADMIN_USER = 'aus';

  /**
   * @inheritDoc
   */
  public static function supportedModes() {
    return [self::BROWSER_MODE_USER, self::BROWSER_MODE_ADMIN_USER];
  }

  /**
   * @inheritDoc
   */
  public function label($mode) {
    return $mode == self::BROWSER_MODE_ADMIN_USER ? t('Browse users') : t('Select a user');
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
    $params[Constants::MM_GET_TREE_FILTER_USERS] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRightButtons($mode, $query, $item, $permissions, &$actions, &$dialogs) {
    if ($mode == self::BROWSER_MODE_ADMIN_USER) {
      static::adminLinks($mode, $query, $item, $permissions, $actions);
    }
    if ($mode == self::BROWSER_MODE_USER) {
      static::selectLink($mode, $query, $item, $permissions, $actions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTreeTop($mode) {
    return mm_content_users_mmtid();
  }

  /**
   * {@inheritdoc}
   */
  public function getBookmarksType($mode) {
    return 'browser_usr';
  }

}
