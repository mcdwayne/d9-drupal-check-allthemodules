<?php

namespace Drupal\entity_http_exception\Utils;

/**
 * Utility class for EntityHttpException module.
 */
class EntityHttpExceptionUtils {

  /**
   * Helper function to get entity types on the site.
   *
   * @return array
   *   An array of given entity type.
   */
  public function getEntityTypes() {
    return [
      'node_type' => [
        'title' => 'Content type',
        'key' => 'node'
      ],
      'taxonomy_vocabulary' => [
        'title' => 'vocabularies',
        'key' => 'taxonomy_term'
      ],
    ];
  }

  /**
   * Helper function to get entity bundles on the site.
   *
   * @return array
   *   An array of bundles of given type.
   */
  public function getEntityBundles($entity_type) {

    $bundles = \Drupal::entityTypeManager()->getStorage($entity_type)
      ->loadMultiple();

    foreach ($bundles as $bundle_name => $bundle) {
      $bundles_array[$bundle_name] = $bundle->label();
    }

    return $bundles_array;
  }

  /**
   * Gets the http exception unpublish node key used in this module.
   *
   * @param string $bundle_name
   *   Machine name of bundle.
   *
   * @return string
   *   key name of settings field.
   */
  public function getUnpublishedNodesKey($bundle_name) {
    return $bundle_name . '_unpublished_node';
  }

  /**
   * Gets the http exception publish node key used in this module.
   *
   * @param string $bundle_name
   *   Machine name of bundle.
   *
   * @return string
   *   key name of settings field.
   */
  public function getPublishedNodesKey($bundle_name) {
    return $bundle_name . '_published_node';
  }

  /**
   * Gets the http exception code key used in this module.
   *
   * @param string $entity_type
   *   Machine name of entity_type.
   * @param string $bundle_name
   *   Machine name of bundle.
   *
   * @return string
   *   key name of settings field.
   */
  public function getHttpExceptionCodeKey($entity_type, $bundle_name) {
    return $entity_type . '_' . $bundle_name . '_code';
  }

}
