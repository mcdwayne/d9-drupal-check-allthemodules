<?php

namespace Drupal\entity_collector\Service;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Drupal\entity_collector\Entity\EntityCollectionType;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\EntityCollectionSourceFieldManager;
use Drupal\user\PrivateTempStoreFactory;


/**
 * Class EntityCollectionManager.
 */
class EntityCollectionManager implements EntityCollectionManagerInterface {

  use StringTranslationTrait;

  /**
   * The user temp store.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Entity Collection Source Field Manager.
   *
   * @var \Drupal\entity_collector\EntityCollectionSourceFieldManager
   */
  protected $collectionSourceFieldManager;

  /**
   * Lock Backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * EntityCollectionManager constructor.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   The user temp store.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory, EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, EntityCollectionSourceFieldManager $collectionSourceFieldManager, LockBackendInterface $lock) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->collectionSourceFieldManager = $collectionSourceFieldManager;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCollectionBundleType(EntityCollectionInterface $entityCollection) {
    return $this->getEntityCollectionType($entityCollection->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCollection($entityCollectionId) {
    return $this->entityTypeManager->getStorage('entity_collection')
      ->load($entityCollectionId);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCollectionType($entityCollectionTypeId) {
    return $this->entityTypeManager->getStorage('entity_collection_type')
      ->load($entityCollectionTypeId);
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveCollection(EntityCollectionTypeInterface $entityCollectionType, EntityCollectionInterface $collection) {
    $entityCollectionStore = $this->tempStoreFactory->get('active_entity_collections');
    $entityCollectionStore->set($entityCollectionType->id(), $collection->id());

    /** @var CacheTagsInvalidator $cacheTagsInvalidator */
    $cacheTagsInvalidator = \Drupal::service('cache_tags.invalidator');
    $cacheTagsInvalidator->invalidateTags($this->getListCacheTags($entityCollectionType->id(), $this->currentUser->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheTags($entityCollectionTypeId, $userId) {
    return [
      'entity_collection_list:' . $entityCollectionTypeId . '_' . $userId,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isValidCollectionForUser(EntityCollectionInterface $collection, AccountInterface $user) {
    return ($collection->getOwnerId() == $user->id() || in_array($user->id(), $collection->getParticipantsIds()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionItemList(EntityCollectionTypeInterface $entityCollectionType) {
    $activeCollectionId = $this->getActiveCollectionId($entityCollectionType);
    $collections = $this->getCollections($entityCollectionType);
    $collections = array_reverse($collections, TRUE);

    $build = [
      '#theme' => 'item_list',
      '#items' => [],
      '#attributes' => [
        'class' => [
          'js-entity-collection-selection-list',
        ],
      ],
    ];

    $items = array_map(function (EntityCollectionInterface $entityCollection) use ($activeCollectionId) {
      return [
        '#type' => 'entity_collection_link',
        '#entityCollection' => $entityCollection,
        '#options' => [
          'attributes' => [
            'class' => [
              $entityCollection->id() == $activeCollectionId ? 'active' : '',
            ],
          ],
        ],
      ];
    }, $collections);

    if (empty($items)) {
      $items = [
        ['#markup' => $this->t('No :type collections available', [':type' => $entityCollectionType->label()])],
      ];
    }

    $build['#items'] = $items;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveCollectionId(EntityCollectionTypeInterface $entityCollectionType) {
    $entityCollectionStore = $this->tempStoreFactory->get('active_entity_collections');
    return $entityCollectionStore->get($entityCollectionType->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getCollections(EntityCollectionTypeInterface $entityCollectionType, AccountInterface $user = NULL) {
    if (!isset($user)) {
      $user = $this->currentUser;
    }
    return array_merge($this->getOwnedCollectionList($entityCollectionType, $user), $this->getParticipatingCollectionList($entityCollectionType, $user));
  }

  /**
   * Get entity collections owned by the user.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *   The entity collection type.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\entity_collector\Entity\EntityCollectionInterface[]
   *   The collection owned by the user.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getOwnedCollectionList(EntityCollectionTypeInterface $entityCollectionType, AccountInterface $user) {
    return $this->entityTypeManager->getStorage('entity_collection')
      ->loadByProperties([
        'owner' => $user->id(),
        'type' => $entityCollectionType->id(),
      ]);
  }

  /**
   * Get the participating collections list.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *   The entity collection type.
   * @param \Drupal\Core\Session\AccountInterface $user
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\entity_collector\Entity\EntityCollectionInterface[]
   *   The collections the user participates in.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getParticipatingCollectionList(EntityCollectionTypeInterface $entityCollectionType, AccountInterface $user) {
    return $this->entityTypeManager->getStorage('entity_collection')
      ->loadByProperties([
        'participants' => $user->id(),
        'type' => $entityCollectionType->id(),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveCollection(EntityCollectionTypeInterface $entityCollectionType) {
    $entityCollectionStore = $this->tempStoreFactory->get('active_entity_collections');
    $entityCollectionId = $entityCollectionStore->get($entityCollectionType->id());

    if (empty($entityCollectionId)) {
      return $entityCollectionId;
    }

    return $this->entityTypeManager->getStorage('entity_collection')
      ->load($entityCollectionId);
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionTypeFormField(array $form, array $entityCollectionTypeOptions, array $config) {
    $form['entity_collection_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Collection Type'),
      '#description' => $this->t(''),
      '#empty_option' => $this->t('Please select'),
      '#options' => $entityCollectionTypeOptions,
      '#default_value' => isset($config['entity_collection_type']) ? $config['entity_collection_type'] : NULL,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheContexts($entityCollectionTypeId, $userId) {
    return [
      'user',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addItemToCollection(EntityCollectionInterface $entityCollection, $entityId) {

    if ($this->entityExistsInEntityCollection($entityCollection, $entityId)) {
      return;
    }
    $fieldDefinition = $this->getSourceFieldDefinition($entityCollection);
    $entityCollection->{$fieldDefinition->getName()}[] = $entityId;
    $entityCollection->save();
  }

  /**
   * {@inheritdoc}
   */
  public function entityExistsInEntityCollection(EntityCollectionInterface $entityCollection, $entityId) {
    $fieldDefinition = $this->getSourceFieldDefinition($entityCollection);
    $index = $this->getIndexInReferenceField($entityCollection->get($fieldDefinition->getName()), $entityId);
    return isset($index);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldDefinition($entityCollection) {
    $entityCollectionType = EntityCollectionType::load($entityCollection->bundle());
    return $this->collectionSourceFieldManager->getSourceFieldDefinition($entityCollectionType, $entityCollectionType->getSource());
  }

  /**
   * Get the index in the entity reference field, matching the given value.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   * @param int $value
   *
   * @return int|null|string
   */
  private function getIndexInReferenceField(FieldItemListInterface $field, $value) {
    $index = NULL;
    foreach ($field->getValue() as $key => $fieldValue) {
      if ($fieldValue['target_id'] != $value) {
        continue;
      }
      $index = $key;
    }
    return $index;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItemFromCollection(EntityCollectionInterface $entityCollection, $entityId) {
    if (!$this->entityExistsInEntityCollection($entityCollection, $entityId)) {
      return;
    }

    $fieldDefinition = $this->getSourceFieldDefinition($entityCollection);
    $field = $entityCollection->get($fieldDefinition->getName());
    $index = $this->getIndexInReferenceField($field, $entityId);
    $field->removeItem($index);
    $entityCollection->save();
  }

  /**
   * {@inheritdoc}
   */
  public function acquireLock($lockName) {
    if (!$this->lock->acquire($lockName)) {
      $this->lock->wait($lockName);
      if (!$this->lock->acquire($lockName)) {
        throw new \Exception("Couldn't acquire lock to update entity collection.");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function releaseLock($lockName) {
    $this->lock->release($lockName);
  }

  /**
   * {@inheritdoc}
   */
  public function removeParticipantFromCollection(EntityCollectionInterface $entityCollection, AccountInterface $user) {
    if (!$this->isValidCollectionForUser($entityCollection, $user)) {
      return;
    }

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
    $field = $entityCollection->get('participants');
    $index = $this->getIndexInReferenceField($field, $user->id());
    if ($index !== NULL) {
      $field->removeItem($index);
    }

    if (!$field->isEmpty() || $entityCollection->getOwnerId() === $user->id()) {
      $owner = $field->first() === NULL ? 0 : $field->first()->getValue()['target_id'];
      $entityCollection->setOwnerId((int) $owner);
      $field->removeItem(0);
    }

    $entityCollection->save();
  }

}
