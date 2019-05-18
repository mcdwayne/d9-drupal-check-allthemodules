<?php

namespace Drupal\entity_counter;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for counter transactions.
 */
class CounterTransactionStorage extends SqlContentEntityStorage {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Constructs a CounterTransactionStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, QueueFactory $queue) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager);

    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    if (!isset($values['entity_counter'])) {
      throw new EntityStorageException('Missing entity counter for this counter transaction.');
    }
    $entity = parent::doCreate($values);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    $return = parent::doPreSave($entity);

    /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $entity */
    if (!$entity->getEntityCounterSourceId()) {
      throw new EntityStorageException('Missing entity counter source for this counter transaction.');
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $entity */
    /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $original_entity */
    $original_entity = $entity->original;

    if ((empty($original_entity) && $entity->isQueued()) ||
      ($entity->isQueued() && !$original_entity->isQueued()) ||
      ($entity->getOperation() == CounterTransactionOperation::CANCEL && $original_entity->getOperation() != CounterTransactionOperation::CANCEL)) {
      $this->queue->get('entity_counter_transaction', TRUE)->createItem(['revision_id' => $entity->getRevisionId()]);
    }

    parent::doPostSave($entity, $update);
  }

}
