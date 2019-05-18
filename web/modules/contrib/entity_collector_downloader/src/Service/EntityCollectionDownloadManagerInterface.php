<?php

namespace Drupal\entity_collector_downloader\Service;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\user\UserInterface;

/**
 * Interface EntityCollectionManagerInterface
 *
 * @package Drupal\entity_collector\Service
 */
interface EntityCollectionDownloadManagerInterface {

  /**
   * Get the all the possible download fields for the given collection type.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *
   * @return array
   */
  public function getAllPossibleDownloadFields(EntityCollectionTypeinterface $entityCollectionType);

  /**
   * Get the available download fields for the given collection entity bundle
   * type.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entityBundle
   *
   * @return array
   */
  public function getPossibleDownloadFields(ConfigEntityInterface $entityBundle);

  /**
   * Get the field names configured to be used on the download page of the
   * given collection type.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *
   * @return array
   */
  public function getActiveDownloadFieldNames(EntityCollectionTypeInterface $entityCollectionType);

  /**
   * Get the active download config option ids for the given collection type.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *
   * @return array
   */
  public function getActiveDownloadOptionIds(EntityCollectionTypeInterface $entityCollectionType);

  /**
   * Get the download option config entities for the given field, collection
   * type and entity bundle.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $configEntity
   * @param string $fieldName
   *
   * @return \Drupal\file_downloader\Entity\DownloadOptionConfigInterface[]
   */
  public function getConfigEntityFieldDownloadOptions(EntityCollectionTypeInterface $entityCollectionType, ConfigEntityInterface $entityBundle, AccountInterface $currentUser, $fieldName);

  /**
   * Get the entity config type id.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *
   * @return string
   */
  public function getEntityConfigTypeId(EntityCollectionTypeInterface $entityCollectionType);

  /**
   * Get the field extensions allowed to be used with the field for the given config entity.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $configEntity
   * @param string $fieldName
   *
   * @return array
   */
  public function getFieldExtensions(EntityCollectionTypeInterface $entityCollectionType, ConfigEntityInterface $configEntity, $fieldName);

}
