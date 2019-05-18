<?php

namespace Drupal\entity_collector\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;

/**
 * Interface EntityCollectionManagerInterface
 *
 * @package Drupal\entity_collector\Service
 */
interface EntityCollectionManagerInterface {

  /**
   * Set the active collection.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *   The entity collection type.
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $collection
   *   The entity collection.
   * @param \Drupal\Core\Session\AccountInterface|NULL $user
   *   The user entity.
   */
  public function setActiveCollection(EntityCollectionTypeInterface $entityCollectionType, EntityCollectionInterface $collection);

  /**
   * Get the active collection for the user.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *   The entity collection type.
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionInterface;
   *   The entity collection.
   */
  public function getActiveCollection(EntityCollectionTypeInterface $entityCollectionType);

  /**
   * Get the active collection id for the user.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *   The entity collection type.
   *
   * @return mixed;
   *   The entity collection id.
   */
  public function getActiveCollectionId(EntityCollectionTypeInterface $entityCollectionType);

  /**
   * Get the collection list for the user.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *   The entity collection type.
   * @param \Drupal\Core\Session\AccountInterface|NULL $user
   *   The user entity.
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionInterface[]
   *   A list of entity collections.
   */
  public function getCollections(EntityCollectionTypeInterface $entityCollectionType, AccountInterface $user = NULL);

  /**
   * Is the collection valid for the user?
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $collection
   *   The collection.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user entity.
   *
   * @return bool
   *   True if valid, false if not.
   */
  public function isValidCollectionForUser(EntityCollectionInterface $collection, AccountInterface $user);

  /**
   * Get the entity collection type.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionTypeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getEntityCollectionBundleType(EntityCollectionInterface $entityCollection);

  /**
   * Get the collection item list.
   *
   * @return array
   *   The item list.
   */
  public function getCollectionItemList(EntityCollectionTypeInterface $entityCollectionType);

  /**
   * Get the form field to select a collection type.
   *
   * @param array $form
   *   The form you are adding the field to.
   * @param array $entityCollectionTypeOptions
   *   List of entity collection types.
   * @param array $config
   *   The block config.
   * @return mixed
   *   The form field.
   */
  public function getCollectionTypeFormField(array $form, array $entityCollectionTypeOptions, array $config);

  /**
   * Get the list of tags for entity collection lists.
   *
   * @param string $entityCollectionTypeId
   * @param int $userId
   *
   * @return string[]
   */
  public function getListCacheTags($entityCollectionTypeId, $userId);

  /**
   * Get the list of contexts for entity collection lists.
   *
   * @param string $entityCollectionTypeId
   * @param int $userId
   *
   * @return string[]
   */
  public function getListCacheContexts($entityCollectionTypeId, $userId);

  /**
   * Add the item to a collection.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   * @param $entity_id
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addItemToCollection(EntityCollectionInterface $entityCollection, $entity_id);

  /**
   * Remove the item from a collection.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   * @param $value
   *
   * @return int|null|string
   */
  public function removeItemFromCollection(EntityCollectionInterface $entityCollection, $entity_id);

  /**
   * Check if a entity exists within a collection.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   * @param $entity_id
   *
   * @return bool
   */
  public function entityExistsInEntityCollection(EntityCollectionInterface $entityCollection, $entity_id);

  /**
   * Get the source field definition.
   *
   * @param EntityCollectionInterface $entityCollection
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   */
  public function getSourceFieldDefinition($entityCollection);

  /**
   * Acquire lock on the Lock Backend for an entity collection event.
   *
   * @param string $lockName
   *
   * @throws \Exception
   */
  public function acquireLock($lockName);

  /**
   * Release lock on the Lock Backend for an entity collection event.
   *
   * @param string $lockName
   *
   * @return mixed
   */
  public function releaseLock($lockName);

  /**
   * Get the entity collection.
   *
   * @param int $entityCollectionId
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionInterface
   */
  public function getEntityCollection($entityCollectionId);

  /**
   * Get the entity collection type.
   *
   * @param int $entityCollectionTypeId
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionTypeInterface
   */
  public function getEntityCollectionType($entityCollectionTypeId);

  /**
   * Remove the particpant from a collection.
   *
   * @param EntityCollectionInterface $entityCollection
   * @param AccountInterface $user
   */
  public function removeParticipantFromCollection(EntityCollectionInterface $entityCollection, AccountInterface $user);
}
