<?php

namespace Drupal\menu_firstchild;

use Drupal\Core\Url;

/**
 * Class MenuItemParser
 *
 * @package Drupal\menu_firstchild
 */
class MenuItemParser {

  /**
   * Parses a menu item and modifies it if menu_firstchild is enabled.
   *
   * @param array $item
   *   Menu item array.
   *
   * @return array
   *   Menu item array.
   */
  public function parse(array $item) {
    // If menu_firstchild is enabled on the menu item, continue parsing it.
    if ($this->enabled($item) && !empty($item['below'])) {
      $child = reset($item['below']);
      $url = $this->childUrl($child);

      // Create a new URL so we don't copy attributes etc.
      if (!$url->isExternal()) {
        $item['url'] = Url::fromRoute($url->getRouteName(), $url->getRouteParameters());
      }
      else {
        $item['url'] = Url::fromUri($url->getUri());
      }

      // Add a class on the menu item so it can be themed accordingly.
      $item['attributes']->addClass('menu-firstchild');
    }

    // Parse all children if any are found.
    if (!empty($item['below'])) {
      foreach ($item['below'] as &$below) {
        $below = $this->parse($below);
      }
    }

    return $item;
  }

  /**
   * Returns the URL of a child menu item, taking menu_firstchild into account.
   *
   * @param array $item
   *   Menu item array.
   *
   * @return \Drupal\Core\Url
   *   URL to use in the link.
   */
  protected function childUrl(array $item) {
    $enabled = $this->enabled($item);

    // If menu_firstchild is enabled on the menu item and it has children,
    // take the URL of the first child.
    if ($enabled && !empty($item['below'])) {
      $child = reset($item['below']);
      $url = $this->childUrl($child);
    }
    // If menu_firstchild is enabled on the menu item but there's no menu
    // items below the menu item, return route <none>.
    elseif ($enabled) {
      $url = Url::fromRoute('<none>');
    }
    // If menu_firstchild isn't enabled on the menu item, return the item's
    // URL.
    else {
      $url = $item['url'];
    }

    return $url;
  }

  /**
   * Returns whether menu_firstchild is enabled on a menu item.
   *
   * @param array $item
   *   Menu item array.
   *
   * @return bool
   *   Returns TRUE if menu_firstchild is enabled on the menu item.
   */
  protected function enabled(array $item) {
    $options = $item['url']->getOption('menu_firstchild');
    return !empty($options['enabled']);
  }

}
