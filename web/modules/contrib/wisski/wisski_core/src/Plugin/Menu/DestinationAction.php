<?php

namespace Drupal\wisski_core\Plugin\Menu;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Modifies the action link to add destination. Destination is the caller
 * 
 * Taken from and @see Drupal\devel\Plugin\Menu\DestinationMenuLink
 */
class DestinationAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    // Append the current path as destination to the query string.
    $options['query']['destination'] = Url::fromRouteMatch($route_match)->toString();
    return $options;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable once https://www.drupal.org/node/2582797 lands.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}

