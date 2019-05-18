<?php

namespace Drupal\chatbot_api_entities;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dispatcher handling entities hooks.
 */
class EntityHookDispatcher implements ContainerInjectionInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Collection storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $collectionStorage;

  /**
   * Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a new EntityHookDispatcher object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Drupal queue Factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, QueueFactory $queueFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->collectionStorage = $entityTypeManager->getStorage('chatbot_api_entities_collection');
    $this->queueFactory = $queueFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('queue')
    );
  }

  /**
   * Handles entity lifecycle events.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity that was created, updated or deleted.
   * @param string $event_name
   *   Event that occurred.
   */
  public function handleEntityEvent(EntityInterface $entity, $event_name = 'insert') {
    $queue = $this->queueFactory->get('chatbot_api_entities_push');
    foreach ($this->getRelatedChatbotEntityCollections($entity) as $collection) {
      $queue->createItem([
        'collection_id' => $collection->id(),
        'created' => time(),
      ]);
    }
  }

  /**
   * Gets related chatbot entity collections.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to find matching collections for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Matching chatbot entity collections.
   */
  protected function getRelatedChatbotEntityCollections(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      // We don't do anything for non content entities.
      return [];
    }
    return $this->collectionStorage->loadMultiple($this->collectionStorage->getQuery()
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('bundle', $entity->bundle())
      ->execute());
  }

}
