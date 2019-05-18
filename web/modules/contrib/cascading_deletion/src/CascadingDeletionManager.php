<?php

namespace Drupal\cascading_deletion;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Class CascadingDeletionManager
 *
 * @package Drupal\cascading_deletion
 */
class CascadingDeletionManager {

  /**
   * @var array
   */
  private $config;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDefault;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * CascadingDeletionManager constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(LanguageManagerInterface $languageManager,
                              CacheBackendInterface $cacheBackend,
                              EntityFieldManagerInterface $entityFieldManager,
                              EntityTypeManagerInterface $entityTypeManager) {

    $this->languageManager = $languageManager;
    $this->cacheDefault = $cacheBackend;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;

    $this->setConfiguration();
  }

  /**
   * Set configuration from cache or build a new one.
   */
  private function setConfiguration() {

    // Check if data are in cache.
    $cid = 'cascading_deletion:' . $this->languageManager
        ->getCurrentLanguage()
        ->getId();
    $cache = $this->cacheDefault->get($cid);

    // Cached case.
    if ($cache) {
      $this->config = $cache->data;
    }
    else {

      $this->config = $this->buildConfiguration();

      // Cache the collected endpoints.
      $this->cacheDefault->set($cid, $this->config);
    }

  }

  /**
   * Loop on all entity reference fields and build the cascading deletion
   * config.
   *
   * @return array
   */
  private function buildConfiguration() {

    // This array in used to manage deletion.
    $config = [];

    // Get all entity reference fields.
    $fieldsArray = $this->entityFieldManager
      ->getFieldMapByFieldType('entity_reference');

    // Loop on entity types.
    foreach ($fieldsArray as $entityTypeId => $fieldArray) {

      // Loop on fields.
      foreach ($fieldArray as $fieldName => $fieldSettings) {

        // Loop on bundles.
        foreach ($fieldSettings['bundles'] as $bundleName => $bundle) {

          // Check if current field has cascading deletion and get the parent target type.
          if ($parentEntityTypeId = $this->getCascadingParentTargetType($entityTypeId, $bundle, $fieldName)) {

            $config[$parentEntityTypeId][] = [
              'entity_type' => $entityTypeId,
              'field_name' => $fieldName,
            ];
          }
        }
      }
    }

    return $config;
  }

  /**
   * Check if cascading deletion is enabled and return the parent target type.
   *
   * The cascading deletion setting can be set on field base or field config
   *  definition. It is so necessary check on both parts.
   * First of all the check is done on field config. Then, if the field config
   *  is not found, check on field base given from entity field definitions.
   * In the case di cascading deletion is found and it is enabled, the parent
   *  target type will be return. NULL otherwise.
   *
   * @param string $entityTypeId
   * @param string $bundle
   * @param string $fieldName
   *
   * @return string|null
   *   The parent target type.
   */
  protected function getCascadingParentTargetType($entityTypeId, $bundle, $fieldName) {

    $parentTargetType = NULL;

    // Build the field config name and check on it.
    $fieldConfigName = "$entityTypeId.$bundle.$fieldName";
    if (!is_null($fieldConfig = FieldConfig::load($fieldConfigName))) {

      // Get settings from third party settings.
      $enabled = $fieldConfig->getThirdPartySetting('cascading_deletion', 'enabled');
      if ($enabled) {

        // Load Storage Config to get entity type target id.
        $fieldStorage = $fieldConfig->getFieldStorageDefinition();
        $parentTargetType = $fieldStorage->getSetting('target_type');
      }
    }
    // Check on field base.
    else {

      // Get field definition.
      $entityFields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle);
      $entityField = $entityFields[$fieldName];

      // Check on settings of base field definition.
      $entityFieldSetting = $entityField->getSetting('cascading_deletion');
      if (isset($entityFieldSetting['enabled']) &&
        $entityFieldSetting['enabled']) {

        $parentTargetType = $entityField->getSetting('target_type');
      }
    }

    return $parentTargetType;
  }

  /**
   * @param $entityTypeId
   * @param $entityId
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function delete($entityTypeId, $entityId) {

    if (isset($this->config[$entityTypeId])) {

      // Loop on cascading deletion settings.
      foreach ($this->config[$entityTypeId] as $entityConfig) {

        // Get all entities of related type.
        $query = $this->entityTypeManager->getStorage($entityConfig['entity_type'])
          ->getQuery('AND')
          ->condition($entityConfig['field_name'], $entityId);

        $ids = $query->execute();

        // Delete all entities related to parent entity that will be deleted.
        if ($ids) {

          $storage_handler = $this->entityTypeManager
            ->getStorage($entityConfig['entity_type']);
          $entities = $storage_handler->loadMultiple($ids);
          $storage_handler->delete($entities);
        }
      }
    }
  }
}