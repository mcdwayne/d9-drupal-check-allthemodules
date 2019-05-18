<?php

namespace Drupal\json_ld_schema\Entity;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for JSON LD entity plugins.
 */
abstract class JsonLdEntityBase extends PluginBase implements JsonLdEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(EntityInterface $entity, $view_mode): CacheableMetadata {
    return new CacheableMetadata();
  }

}
