<?php

namespace Drupal\elastic_search\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for Field mapper plugin plugins.
 */
interface EntityTypeDefinitionsInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * @param string $entityType
   * @param string $bundleType
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  public function getFieldDefinitions(string $entityType, string $bundleType);

}
