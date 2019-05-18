<?php

namespace Drupal\reactify_menu\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Class MenuResource provides menu endpoints.
 *
 * @RestResource (
 *   id = "menu_resource",
 *   label = @Translation("Menu resource"),
 *   uri_paths = {
 *     "canonical" = "/rest/menu/{menu}"
 *   }
 * )
 *
 * @package Drupal\reactify\Plugin\rest\resource
 */
class MenuResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public function get($menu) {
    $menu_name = $menu;
    $menu_parameters = \Drupal::menuTree()->getCurrentRouteMenuTreeParameters($menu_name);
    $loaded_menu = \Drupal::menuTree()->load($menu_name, $menu_parameters);
    $result = [];

    foreach ($loaded_menu as $item) {
      $link = $item->link;
      $url = $link->getUrlObject();
      array_push($result, [
        'id' => $link->getBaseId() . $link->getWeight(),
        'title' => $link->getTitle(),
        'url' => $url->getInternalPath(),
        'weight' => $link->getWeight(),
      ]);
    };

    $response = new ResourceResponse($result);
    $response->addCacheableDependency($result);
    return $response;
  }

}
