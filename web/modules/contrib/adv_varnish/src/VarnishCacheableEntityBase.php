<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\VarnishCacheableEntityBase.
 */

namespace Drupal\adv_varnish;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\Entity;

class VarnishCacheableEntityBase extends PluginBase implements VarnishCacheableEntityInterface {

  /**
   * @var \Drupal\Core\Entity\EntityInterface $entity
   */
  protected $entity;

  /**
   * @inheritdoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
    $this->entity = $configuration['entity'];
  }

  /**
   * Generate cache config key for given entity.
   *
   * @return string
   */
  public function generateSettingsKey() {
    $entity = $this->entity;
    $type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    return 'entities_settings.' . $type . '.' . $bundle ;
  }
  
}
