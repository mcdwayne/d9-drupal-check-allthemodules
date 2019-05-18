<?php

namespace Drupal\monster_menus\Plugin\MMTreeBrowserDisplay;

use Drupal\Component\Utility\Html;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\MMTreeBrowserDisplay\MMTreeBrowserDisplayInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Provides the MM Tree display generator for MMTree entities.
 *
 * @MMTreeBrowserDisplay(
 *   id = "mm_tree_browser_display_default",
 *   admin_label = @Translation("MM Tree default display"),
 * )
 */
class Fallback implements MMTreeBrowserDisplayInterface {

  const BROWSER_MODE_PAGE = 'pag';
  const BROWSER_MODE_ADMIN_PAGE = 'apg';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function supportedModes() {
    return [self::BROWSER_MODE_PAGE, self::BROWSER_MODE_ADMIN_PAGE];
  }

  /**
   * @inheritDoc
   */
  public function label($mode) {
    return $mode == self::BROWSER_MODE_ADMIN_PAGE ? t('Browse pages') : t('Select a page');
  }

  /**
   * @inheritDoc
   */
  public function showReservedEntries($mode) {
    $user = \Drupal::currentUser();
    return $user->hasPermission('administer all menus') || $user->hasPermission('view all menus');
  }

  /**
   * {@inheritdoc}
   */
  public function alterLeftQuery($mode, $query, &$params) {
    $params[Constants::MM_GET_TREE_FILTER_NORMAL] = $params[Constants::MM_GET_TREE_FILTER_USERS] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRightButtons($mode, $query, $item, $permissions, &$actions, &$dialogs) {
    if ($mode == self::BROWSER_MODE_ADMIN_PAGE) {
      static::adminLinks($mode, $query, $item, $permissions, $actions);
    }
    else if ($mode == self::BROWSER_MODE_PAGE) {
      static::selectLink($mode, $query, $item, $permissions, $actions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewRight($mode, $query, $perms, $item, $database) {
    $choice = function($item, $cat, $usr, $group) {
      if (!empty($item->is_user)) {
        return $usr;
      }
      if (!empty($item->is_group)) {
        return $group;
      }
      return $cat;
    };

    $content = '';
    if (empty($item->is_virtual)) {
      $content = '<table>';
      if (empty($item->is_group)) {
        $content .= '<tr><td align="right"><b>' . t('URL alias:') . '</b>&nbsp;</td><td>' . Html::escape($item->alias) . '</td></tr>';
      }
      if (isset($item->nodecount)) {
        $content .= '<tr><td align="right"><b>' . t('Items on page:') . '</b>&nbsp;</td><td>' . $item->nodecount . '</td></tr>';
      }
      $content .= '<tr><td align="right"><b>' . t('Owner:') . '</b>&nbsp;</td><td>' . mm_content_uid2name($item->uid) . '</td></tr>';
      $can = array();
      $avail_perms = array(
        Constants::MM_PERMS_WRITE => $choice($item,
          t('delete/edit page'),
          t('delete/edit user'),
          t('delete/edit group')),
        Constants::MM_PERMS_SUB => $choice($item,
          t('add sub-pages'),
          $item->name == Constants::MM_ENTRY_NAME_USERS ? t('add users') : t('add sub-pages'),
          t('add sub-groups')),
        Constants::MM_PERMS_APPLY => !empty($item->is_group) ? t('apply this group') : t('assign content to page'),
        Constants::MM_PERMS_READ => !empty($item->is_group) ? t('see group\'s members') : t('read contents of page'),
      );
      foreach ($avail_perms as $type => $desc) {
        if ($perms[$type]) {
          $can[] = $desc;
        }
      }
      $content .= '<tr valign="top"><td align="right"><b>' . t('You can:') . '</b>&nbsp;</td><td>' . implode('<br />', $can) . '</td></tr>';
      if ($item->is_group && ($users = static::getUsersInGroup($item))) {
        $content .= '<tr valign="top"><td align="right"><b>' . t('Users in group:') . '</b>&nbsp;</td><td>' . $users . '</td></tr>';
      }
      $content .= '</table>';
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function getTreeTop($mode) {
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getBookmarksType($mode) {
    return 'browser';
  }

  /**
   * Generate buttons specific to the /admin/mm pages.
   *
   * @param $mode
   *   Display mode constant.
   * @param ParameterBag $query
   *   The request query object.
   * @param $item
   *   Object describing the tree entry.
   * @param array $permissions
   *   The item's permissions.
   * @param $actions
   *   Array of action buttons to alter.
   */
  public static function adminLinks($mode, $query, $item, $permissions, &$actions) {
    $x = mm_ui_strings($item->is_group);

    if ($permissions[Constants::MM_PERMS_READ]) {
      $actions['contents'] = [
        '#type' => 'button',
        '#value' => t('View Contents'),
        '#attributes' => [
          'title' => t('View this @thingpos contents', $x),
          'onclick' => "location.href='" . mm_content_get_mmtid_url($item->mmtid)->toString() . "'",
        ]
      ];
    }

    if ($permissions[Constants::MM_PERMS_WRITE] || $permissions[Constants::MM_PERMS_SUB]) {
      $actions['settings'] = [
        '#type' => 'button',
        '#value' => t('Change Settings'),
        '#attributes' => [
          'title' => t('Edit this @thing', $x),
          'onclick' => "location.href='" . Url::fromRoute('monster_menus.handle_page_settings', ['mm_tree' => $item->mmtid])->toString() . "'",
        ]
      ];
    }
  }

  /**
   * @param $mode
   *   Display mode constant.
   * @param ParameterBag $query
   *   The request query object.
   * @param $item
   *   Object describing the tree entry.
   * @param array $permissions
   *   The item's permissions.
   * @param $actions
   *   Array of buttons to alter.
   */
  public static function selectLink($mode, $query, $item, $permissions, &$actions) {
    if (empty($item->is_virtual) && static::userCan($query, $permissions)) {
      $js_parms = $item->is_group ? "'" . mm_ui_js_escape(static::getUsersInGroup($item)) . "'" : 0;
      $actions['select'] = [
        '#type' => 'button',
        '#value' => t('Select'),
        '#attributes' => [
          'onclick' => "Drupal.mm_browser_page_add($item->mmtid, $js_parms)",
        ]
      ];
    }
  }

  private static function getUsersInGroup($item) {
    static $users;

    if (empty($users)) {
      $users = mm_content_get_users_in_group($item->mmtid, '<br />', FALSE, 20, TRUE, $dialogs);
      if ($users == '') {
        $users = t('(none)');
      }
    }
    return $users;
  }

  /**
   * Determine if a given tree entry should be selectable to the user.
   *
   * @param ParameterBag $query
   *   The request query object.
   * @param array $permissions
   *   Array of permissions to test.
   * @return bool
   *   TRUE if the user has any of the permissions.
   */
  public static function userCan(ParameterBag $query, $permissions) {
    $user_can = TRUE;
    if ($selectable = $query->get('browserSelectable', '')) {
      $user_can = FALSE;
      foreach (str_split($selectable) as $check) {
        if (!empty($permissions[$check])) {
          return TRUE;
        }
      }
    }
    return $user_can;
  }

}