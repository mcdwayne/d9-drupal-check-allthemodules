<?php

namespace Drupal\toolshed_menu\Menu;

/**
 * Trait for getting the menus and menu link options for configured menus.
 */
trait MenuSelectionTrait {

  /**
   * Array of loaded menus that are available for Toolshed functionality.
   *
   * @var Drupal\Core\Menu\Menu[]
   */
  static private $availMenus;

  /**
   * Gets an array of loaded menus items from the entity menu storage.
   *
   * Gets a list of loaded menu items, but will exclude any menus listed as
   * excluded from the toolshed.menu.settings config.
   *
   * @return string[]
   *   An array of menu names that should be excluded from the options form.
   */
  protected function getAvailableMenus() {
    if (!isset(self::$availMenus)) {
      $modSettings = \Drupal::config('toolshed.menu.settings');
      $excludeMenus = $modSettings->get('exclude_menus');

      $menuStorage = \Drupal::entityTypeManager()->getStorage('menu');
      $menuQuery = $menuStorage->getQuery()->sort('label');

      if (is_array($excludeMenus) && !empty($excludeMenus)) {
        $menuQuery->condition('id', $excludeMenus, 'NOT IN');
      }

      self::$availMenus = $menuStorage->loadMultiple($menuQuery->execute());
    }

    return self::$availMenus;
  }

  /**
   * Retrieve the possible menu root canidates for $menuId as element options.
   *
   * @param string $menuId
   *   The machine ID to use to identify the user menu to use.
   * @param string $menuLabel
   *   The label to use at the root of the menu tree.
   *
   * @return string[]
   *   A value-label array of menu items, in a format that can be used with
   *   form elements as the #options attribute.
   */
  protected function getMenuRootOptions($menuId, $menuLabel) {
    $menuOpts = [];

    if (\Drupal::hasService('menu.parent_form_selector')) {
      $menuFormOptions = \Drupal::service('menu.parent_form_selector');
      $discoveredOpts = $menuFormOptions->getParentSelectOptions('', [$menuId => $menuLabel]);

      foreach ($discoveredOpts as $key => $label) {
        $menuOpts[substr($key, strlen($menuId) + 1)] = $label;
      }
    }

    return $menuOpts;
  }

}
