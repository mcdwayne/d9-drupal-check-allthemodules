<?php

namespace Drupal\hn_config\EventSubscriber;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\hn\Event\HnResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DefaultSubscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    return [
      HnResponseEvent::CREATED_CACHE_MISS => 'alterResponseData',
    ];

  }

  /**
   * Alters the response data.
   *
   * @param \Drupal\hn\Event\HnResponseEvent $event
   *   The event that was dispatched.
   */
  public function alterResponseData(HnResponseEvent $event) {
    $responseData = $event->getResponseData();

    $config = \Drupal::config('hn_config.settings');

    foreach ($config->get('menus') as $menu_id) {
      $responseData['data']['config__menus'][$menu_id] = $this->getMenuItemsById($menu_id);
    }

    foreach ($config->get('entities') as $config_id) {
      $config = \Drupal::config($config_id);
      $responseData['data']['config__entities'][$config_id] = $config->get();
    }

    $event->setResponseData($responseData);
  }

  /**
   * Returns all menu items of a menu.
   *
   * @param string $menuName
   *   Menu id (like: main).
   *
   * @return array
   *   All menu items (nested).
   */
  private function getMenuItemsById($menuName) {

    // Get the menu Tree.
    $menuTree = \Drupal::menuTree();

    // Set the parameters.
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();

    // Load the tree based on this set of parameters.
    $tree = $menuTree->load($menuName, $parameters);
    // Transform the tree using the manipulators you want.
    $manipulators = [
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menuTree->transform($tree, $manipulators);

    // Finally, build a renderable array from the transformed tree.
    $menu = $menuTree->build($tree);

    if (empty($menu['#items'])) {
      return [];
    }

    return $this->normalizeMenuItems($menu['#items']);
  }

  /**
   * This normalizes menu items for the HN endpoint.
   *
   * @param array $items
   *   The menu items.
   *
   * @return array
   *   The normalized menu items.
   */
  private function normalizeMenuItems(array $items) {
    return array_map(function ($key, $item) {

      /** @var \Drupal\Core\Url $url */
      $url = $item['url'];

      $item_return = [
        'key' => $key,
        'title' => $item['title'],
        'url' => $url->toString(),
      ];

      if (!empty($item['below'])) {
        $item_return['below'] = $this->normalizeMenuItems($item['below']);
      }

      return $item_return;

    }, array_keys($items), $items);
  }

}
