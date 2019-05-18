<?php

namespace Drupal\media_entity_usage\Service;

/**
 * Class MediaReferenceDiscovery
 *
 * @package Drupal\media_entity_usage\Service
 */
class MediaReferenceDiscovery {

  /**
   * Return all bundles of given type that have references to media entity
   *
   * @param string $entity_type_id
   *
   * @return array|bool
   */
  public function getPossibleBundles($entity_type_id) {

    $fields = $this->getMediaEntityFields($entity_type_id);
    return $fields ? array_keys($fields) : false;
  }

  /**
   * Return all media entity reference fields for given bundle
   *
   * @param string $entity_type_id
   * @param string $bundle
   *
   * @return array|bool
   */
  public function getPossibleFields($entity_type_id, $bundle) {

    return $this->getMediaEntityFields($entity_type_id, $bundle);
  }

  /**
   * Return all media entity reference fields by entity type and bundle name
   *
   * @param string $entity_type_id
   * @param string|null $bundle
   *
   * @return array|bool
   */
  private function getMediaEntityFields($entity_type_id, $bundle = null) {

    $def = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $entity_type = $def->getBundleEntityType();

    $types = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->loadMultiple();

    $results = [];

    foreach (array_keys($types) as $type) {
      $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $type);
      foreach ($fields as $field_name => $field_config) {
        /** @var \Drupal\field\Entity\FieldConfig $field_config */
        if ($field_config->getType() == 'entity_reference' && $field_config->getSetting('handler') == 'default:media') {
          if (!isset($results[$type])) {
            $results[$type] = [];
          }
          $results[$type][] = $field_name;
        }
      }
    }

    if ($bundle) {
      return isset($results[$bundle]) ? $results[$bundle] : false;
    }

    return $results;
  }
}