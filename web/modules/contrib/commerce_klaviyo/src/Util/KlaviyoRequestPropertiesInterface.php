<?php

namespace Drupal\commerce_klaviyo\Util;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface KlaviyoRequestPropertiesInterface.
 *
 * @package Drupal\commerce_klaviyo\Util
 */
interface KlaviyoRequestPropertiesInterface {

  /**
   * Gets the properties as an array.
   *
   * @return array
   *   The array of properties.
   */
  public function getProperties();

  /**
   * Gets the source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The source entity.
   */
  public function getSourceEntity();

  /**
   * Sets the source entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity.
   *
   * @return $this
   */
  public function setSourceEntity(EntityInterface $source_entity);

}
