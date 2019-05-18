<?php

namespace Drupal\cloud\Plugin\Derivative;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides plugin definitions for custom local task.
 */
class CloudServerTemplateTab extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);
    $template = \Drupal::entityTypeManager()->getStorage('cloud_server_template')->load($parameters['cloud_server_template']);
    $parameters['cloud_context'] = $template->getCloudContext();
    return $parameters;
  }

}
