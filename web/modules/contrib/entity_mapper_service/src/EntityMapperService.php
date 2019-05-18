<?php

namespace Drupal\entity_mapper_service;

use Drupal\Core\Entity\EntityInterface;

/**
 * {@inheritdoc}
 */
class EntityMapperService implements EntityMapperServiceInterface {

  protected $servicegroup;

  /**
   * EntityMapperService constructor.
   *
   * @param string $servicegroup
   *   Machine name for a group of services.
   */
  public function __construct($servicegroup) {
    $this->service_group = $servicegroup;
  }

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

    // Populate the services we will use to map our entity.
    $services = $this->services($entity->getEntityTypeId(), $entity->bundle());

    // Call each mapper in order from most general to most specific
    // (entity, entity type, bundle).
    foreach ($services as $service) {
      $values = $service->map($transformation, $entity, $values);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function services($entity_type, $entity_bundle) {

    // Initialize an empty array as our default return value.
    $services = [];

    // Enumerate the possible service names.
    $service_names = [
      "{$this->service_group}.entity_mapper",
      "{$this->service_group}.{$entity_type}_mapper",
      "{$this->service_group}.{$entity_type}_{$entity_bundle}_mapper",
    ];

    // Instantiate each service if it exists.
    foreach ($service_names as $service_name) {
      if (\Drupal::hasService($service_name)) {
        $services[] = \Drupal::service($service_name);
      }
    }

    return $services;

  }

}
