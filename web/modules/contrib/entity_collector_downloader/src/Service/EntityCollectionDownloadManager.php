<?php

namespace Drupal\entity_collector_downloader\Service;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\Service\EntityCollectionManagerInterface;

/**
 * Class EntityCollectionDownloadManager
 *
 * @package Drupal\entity_collector_downloader\Service
 */
class EntityCollectionDownloadManager implements EntityCollectionDownloadManagerInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Collection Manager.
   *
   * @var \Drupal\entity_collector\Service\EntityCollectionManagerInterface
   */
  protected $entityCollectionManager;

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * EntityCollectionDownloadManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\entity_collector\Service\EntityCollectionManagerInterface $entityCollectionManager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityCollectionManagerInterface $entityCollectionManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityCollectionManager = $entityCollectionManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * @inheritDoc
   */
  public function getAllPossibleDownloadFields(EntityCollectionTypeinterface $entityCollectionType) {
    /** @var ConfigEntityInterface[] $entityBundles */
    $entityBundles = $this->entityTypeManager->getStorage($this->getEntityConfigTypeId($entityCollectionType))
      ->loadMultiple();
    $downloadFields = [];
    foreach ($entityBundles as $entityBundle) {
      $downloadFields += $this->getPossibleDownloadFields($entityBundle);
    }
    return $downloadFields;
  }

  /**
   * @inheritDoc
   */
  public function getEntityConfigTypeId(EntityCollectionTypeInterface $entityCollectionType) {
    return $this->entityTypeManager->getStorage($entityCollectionType->getSource())
      ->getEntityType()
      ->getBundleEntityType();
  }

  /**
   * @inheritDoc
   */
  public function getPossibleDownloadFields(ConfigEntityInterface $entityBundle) {
    $fields = $this->entityFieldManager->getFieldDefinitions($entityBundle->getEntityType()
      ->getBundleOf(), $entityBundle->id());
    $downloadFields = [];

    foreach ($fields as $field) {
      if ($field->getType() != 'file' && $field->getType() != 'image') {
        continue;
      }
      $downloadFields[$field->getName()] = $field;
    }

    return $downloadFields;
  }

  /**
   * @inheritDoc
   */
  public function getActiveDownloadFieldNames(EntityCollectionTypeInterface $entityCollectionType) {
    return $entityCollectionType->getThirdPartySetting('entity_collector_downloader', 'entity_collection_downloader_fields', []);
  }

  /**
   * @inheritDoc
   */
  public function getConfigEntityFieldDownloadOptions(EntityCollectionTypeInterface $entityCollectionType, ConfigEntityInterface $entityBundle, AccountInterface $currentUser, $fieldName) {
    $downloadOptionConfigIds = $this->getActiveDownloadOptionIds($entityCollectionType);
    /** @var \Drupal\file_downloader\Entity\DownloadOptionConfigInterface[] $downloadOptionConfigEntities */
    $downloadOptionConfigEntities = $this->entityTypeManager->getStorage('download_option_config')
      ->loadMultiple($downloadOptionConfigIds);
    $machingFieldDownloadOptions = [];

    foreach ($downloadOptionConfigEntities as $downloadOptionConfigEntity) {
      if (!$downloadOptionConfigEntity->accessDownload($currentUser)) {
        continue;
      }

      $extensions = $downloadOptionConfigEntity->getExtensionList();
      $fieldExtensions = $this->getFieldExtensions($entityCollectionType, $entityBundle, $fieldName);

      if (!empty($extensions) && empty(array_intersect($extensions, $fieldExtensions))) {
        continue;
      }
      $machingFieldDownloadOptions[$downloadOptionConfigEntity->id()] = $downloadOptionConfigEntity;
    }
    return $machingFieldDownloadOptions;
  }

  /**
   * @inheritDoc
   */
  public function getActiveDownloadOptionIds(EntityCollectionTypeInterface $entityCollectionType) {
    return $entityCollectionType->getThirdPartySetting('entity_collector_downloader', 'entity_collection_downloader_options', []);
  }

  /**
   * @inheritDoc
   */
  public function getFieldExtensions(EntityCollectionTypeInterface $entityCollectionType, ConfigEntityInterface $configEntity, $fieldName) {
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entityCollectionType->getSource(), $configEntity->id());
    $field = $fieldDefinitions[$fieldName];
    $fieldExtensions = $field->getSetting('file_extensions');
    $fieldExtensions = explode(' ', $fieldExtensions);
    $fieldExtensions = array_map('trim', $fieldExtensions);
    return $fieldExtensions;
  }

}
