<?php

namespace Drupal\media_entity_usage\Service;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class MediaUsagePersistance
 *
 * @package Drupal\media_entity_usage\Service
 */
abstract class MediaUsagePersistance {

  protected $submodule;

  public function __construct($submodule) {
    $this->submodule = $submodule;
  }

  /**
   * Checks if entity has media reference fields
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   */
  public function canHandle(EntityInterface $entity) {
    $bundles = \Drupal::service('media_entity_usage.reference_discovery')->getPossibleBundles($entity->getEntityType()->id());
    return in_array($entity->bundle(), $bundles);
  }

  /**
   * Removes all media usages of given type for entity
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Database\Query\Delete
   */
  public function purge(EntityInterface $entity) {
    $query = \Drupal::database()->delete('media_usage')
      ->condition('submodule', $this->submodule)
      ->condition('entity_type', $entity->getEntityType()->id())
      ->condition('bundle_name', $entity->bundle())
      ->condition('eid', $entity->id())
      ->condition('langcode', $entity->language()->getId())
    ;
    return $query->execute();
  }

  /**
   * Stores all media usages for entity
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array
   *
   * @return mixed
   */
  public function store(EntityInterface $entity, array $media = []) {
    $queryFields = ['mid', 'entity_type', 'bundle_name', 'eid', 'langcode', 'submodule'];
    foreach ($media as $mid) {
      $queryValues = [
        $mid,
        $entity->getEntityType()->id(),
        $entity->bundle(),
        $entity->id(),
        $entity->language()->getId(),
        $this->submodule,
      ];
      \Drupal::database()->insert('media_usage')
        ->fields($queryFields)
        ->values($queryValues)
        ->execute();
    }
    return true;
  }

  /**
   * Returns array of media ids for given entity
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array|bool
   */
  public function getMedia(EntityInterface $entity) {
    $results = [];
    /** @var \Drupal\media_entity_usage\Service\MediaReferenceDiscovery $discovery */
    $discovery = \Drupal::service('media_entity_usage.reference_discovery');
    $fields = $discovery->getPossibleFields($entity->getEntityType()->id(), $entity->bundle());
    $data = $entity->toArray();
    foreach ($fields as $field) {
      if (isset($data[$field])) {
        foreach ($data[$field] as $value) {
          if (!in_array($value['target_id'], $results)) {
            $results[] = $value['target_id'];
          }
        }
      }
    }
    return $results ?: false;
  }
}