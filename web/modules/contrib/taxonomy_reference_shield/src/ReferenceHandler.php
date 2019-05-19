<?php

namespace Drupal\taxonomy_reference_shield;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Calculates reference to a given entity.
 */
class ReferenceHandler implements ReferenceHandlerInterface {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * A class to retrieve entity type bundles.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * A cache backend handler.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * An instance of the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An array of entity type labels.
   *
   * @var array
   *
   * @see \Drupal\Core\Entity\EntityTypeRepositoryInterface::getEntityTypeLabels()
   */
  protected $entityTypeLabels;

  /**
   * An array of bundle labels groupd by entity types.
   *
   * @var array
   *
   * @see \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getAllBundleInfo()
   */
  protected $bundleLabels;

  /**
   * Initializes a new ReferenceHandler object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   A class to retrieve entity type bundles.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An instance of the entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   An instance of the entity type repository manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_bundle_info, CacheBackendInterface $cache, EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityBundleInfo = $entity_bundle_info;
    $this->cache = $cache;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeLabels = $entity_type_repository->getEntityTypeLabels();
    $this->bundleLabels = $entity_bundle_info->getAllBundleInfo();
  }

  /**
   * Calculates an array of all reference to a bundle.
   *
   * This method is designed to drastically improve performance
   * by caching the current taxonomy reference tree.
   */
  protected function referenceFieldMap() {
    // Validate whether map exists in cache.
    $cid = 'taxonomy_reference_shield.field_map';
    $cache = $this->cache->get($cid);
    if ($cache && $cache->data) {
      return $cache->data;
    }

    $tree = [];
    // Get all known entity reference fields.
    $all_reference_fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    foreach ($all_reference_fields as $entity_type => $entity_fields) {
      foreach ($entity_fields as $field_name => $field_data) {
        foreach ($field_data['bundles'] as $bundle) {
          $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
          $field_settings = $definitions[$field_name]->getSettings();
          if ($field_settings['target_type'] != 'taxonomy_term') {
            continue;
          }
          if (isset($field_settings['handler_settings']['target_bundles'])) {
            $target_bundles = array_keys($field_settings['handler_settings']['target_bundles']);
            foreach ($target_bundles as $tb) {
              $tree[$tb][$entity_type][$bundle][] = $field_name;
            }
          }
        }
      }
    }

    // Set the cache and have it be invalidated whenever a field changes.
    $this->cache->set($cid, $tree, CacheBackendInterface::CACHE_PERMANENT, [
      'entity_field_info',
    ]);

    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferences(TermInterface $term, $faster = FALSE) {
    // There are no references if no field exists pointing to this vocabulary.
    $map = $this->referenceFieldMap();
    if (!isset($map[$term->bundle()])) {
      return FALSE;
    }

    // Build the return data.
    $return = [];
    foreach ($map[$term->bundle()] as $entity_type_name => $bundle_data) {
      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_name);
      foreach ($bundle_data as $bundle_name => $fields) {
        $all_bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_name, $bundle_name);
        foreach ($fields as $field_name) {
          if ($field_name == 'parent') {
            // @todo, handle parent fields properly.
            continue;
          }
          $query = $this->entityTypeManager->getStorage($entity_type_name)->getQuery();
          if ($bundle_key = $entity_type_definition->getKey('bundle')) {
            $query->condition($bundle_key, $bundle_name);
          }
          $query->condition($field_name, $term->id());
          $results = $query->execute();
          if ($results) {
            if ($faster) {
              return TRUE;
            }
            $ids = array_keys($results);
            foreach ($ids as $id) {
              $return[$entity_type_name]['bundles'][$bundle_name]['entities'][$id]['fields'][$field_name] = [
                'label' => $all_bundle_fields[$field_name]->getLabel(),
              ];
              $return[$entity_type_name]['label'] = $this->entityTypeLabels[$entity_type_name];
              $return[$entity_type_name]['bundles'][$bundle_name]['label'] = $this->bundleLabels[$entity_type_name][$bundle_name]['label'];
            }
          }
        }
      }
    }

    // Return the calculated data.
    if (!$return) {
      return FALSE;
    }
    return $return;
  }

}
