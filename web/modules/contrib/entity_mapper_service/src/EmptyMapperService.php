<?php

namespace Drupal\entity_mapper_service;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class EntityMapperService.
 *
 * A placeholder mapping class used to register services.
 *
 * @package Drupal\entity_mapper
 */
class EmptyMapperService implements EntityMapperServiceInterface {

  /**
   * Maps entities into an associative array.
   *
   * @param string $transformation
   *   String identifying the type of mapping to perform.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to be mapped.
   * @param array $values
   *   Initial values for array.
   *
   * @return array
   *   Public function map array.
   */
  public function map($transformation, EntityInterface $entity, array $values=[]) {
    return $values;
  }

}
