<?php

namespace Drupal\content_synchronizer\Processors\Type;

use Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager;
use Drupal\content_synchronizer\Service\GlobalReferenceManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\TypedData\TypedData;

/**
 * The type processor Base.
 */
class TypeProcessorBase extends PluginBase implements TypeProcessorInterface {

  /**
   * The entity processor plugin manager.
   *
   * @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager
   */
  protected $pluginManager;

  /**
   * The global reference manager service.
   *
   * @var \Drupal\content_synchronizer\Service\GlobalReferenceManager
   */
  protected $referenceManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager $pluginManager */
    $this->pluginManager = \Drupal::service(EntityProcessorPluginManager::SERVICE_NAME);

    $this->referenceManager = \Drupal::service(GlobalReferenceManager::SERVICE_NAME);

  }

  /**
   * Get the data to export.
   *
   * @param \Drupal\Core\TypedData\TypedData $propertyData
   *   The property data to export.
   *
   * @return array
   *   The exported data.
   */
  public function getExportedData(TypedData $propertyData) {
    return [];
  }

  /**
   * Init the $propertyId value in the entity to import.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entityToImport
   *   The entity to import.
   * @param string $propertyId
   *   The property id.
   * @param array $data
   *   The data to import.
   */
  public function initImportedEntity(EntityInterface $entityToImport, $propertyId, array $data) {
  }

}
