<?php

namespace Drupal\monster_menus\MMTreeBrowserDisplay;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

interface MMTreeBrowserDisplayInterface extends ContainerFactoryPluginInterface {

  /**
   * Get the list of display modes supported by this plugin instance.
   *
   * @return array
   *   An array of display mode constants.
   */
  public static function supportedModes();

  /**
   * Get the human-readable page title for this display mode.
   *
   * @param $mode
   *   Display mode constant.
   * @return string
   *   The label.
   */
  public function label($mode);

  /**
   * Determine if reserved tree entries should be displayed to the user.
   *
   * @param $mode
   *   Display mode constant.
   * @return bool
   *   TRUE if reserved tree entries should be displayed.
   */
  public function showReservedEntries($mode);

  /**
   * @param $mode
   *   Display mode constant.
   * @param ParameterBag $query
   *   Query parameters.
   * @param $params
   *   Array containing parameters to be passed to mm_content_get_tree().
   */
  public function alterLeftQuery($mode, $query, &$params);

  /**
   * Alter the buttons appearing in the right hand pane when an item is selected
   * on the left.
   *
   * @param $mode
   *   Display mode constant.
   * @param ParameterBag $query
   *   Query parameters.
   * @param $item
   *   Object describing the tree entry.
   * @param $permissions
   *   The page's permissions.
   * @param $actions
   *   Array of buttons to alter.
   * @param $dialogs
   *   Array of modal dialog settings to alter.
   */
  public function alterRightButtons($mode, $query, $item, $permissions, &$actions, &$dialogs);

  /**
   * Get the right hand column details for an item chosen in the left.
   *
   * @param string $mode
   *   Display mode constant.
   * @param ParameterBag $query
   *   Query parameters.
   * @param array $perms
   *   The page's permissions.
   * @param $item
   *   Object describing the tree entry.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @return string HTML code.
   *   HTML code.
   */
  public function viewRight($mode, $query, $perms, $item, $database);

  /**
   * Get the MM Tree ID of the topmost page to present to the user.
   *
   * @param $mode
   *   Display mode constant.
   * @return int
   *   MM Tree ID of the topmost page to present to the user.
   */
  public function getTreeTop($mode);

  /**
   * Convert the tree browser mode to a bookmark type.
   *
   * @param $mode
   *   Display mode constant.
   * @return string
   *   The bookmark type.
   */
  public function getBookmarksType($mode);

}