<?php

namespace Drupal\global_gateway\Mapper;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base class for mapper plugins.
 */
abstract class MapperPluginBase extends PluginBase implements MapperInterface {

  protected $region;

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function setRegion($region) {
    $this->region = $region;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return \Drupal::entityTypeManager()
      ->getStorage(self::getPluginDefinition()['entity_type_id'])
      ->load($this->region);
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity(array $values = []) {
    if (isset($values['region'])) {
      $values['region'] = strtolower($values['region']);
    }
    return \Drupal::entityTypeManager()
      ->getStorage(self::getPluginDefinition()['entity_type_id'])
      ->create($values);
  }

}
