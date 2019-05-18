<?php

namespace Drupal\elastic_search\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Defines an interface for Field mapper plugin plugins.
 */
interface ElasticEnabledEntityInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * @return string
   */
  public function getChildType(string $entity_type, string $bundle_type): string;

}
