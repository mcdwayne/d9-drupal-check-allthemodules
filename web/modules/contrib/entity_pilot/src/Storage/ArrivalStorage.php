<?php

namespace Drupal\entity_pilot\Storage;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot\ArrivalStorageInterface;
use Drupal\entity_pilot\CustomsInterface;
use Drupal\entity_pilot\FlightInterface;
use Drupal\entity_pilot\Utility\FlightStub;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Provides storage for the 'ep_arrival' entity type.
 */
class ArrivalStorage extends SqlContentEntityStorage implements ArrivalStorageInterface {

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The customs service.
   *
   * @var \Drupal\entity_pilot\CustomsInterface
   */
  protected $customs;

  /**
   * Constructs a CommentStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The entity pilot arrivals queue.
   * @param \Drupal\entity_pilot\CustomsInterface $customs
   *   The entity pilot baggage handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityTypeInterface $entity_info,
  Connection $database,
  EntityManagerInterface $entity_manager,
                              TranslationInterface $translation,
  CacheBackendInterface $cache,
  Serializer $serializer,
                              QueueInterface $queue,
  CustomsInterface $customs,
  LanguageManagerInterface $language_manager) {
    parent::__construct($entity_info, $database, $entity_manager, $cache, $language_manager);
    $this->stringTranslation = $translation;
    $this->serializer = $serializer;
    $this->queue = $queue;
    $this->customs = $customs;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('string_translation'),
      $container->get('cache.entity'),
      $container->get('serializer'),
      $container->get('queue')->get('entity_pilot_arrivals'),
      $container->get('entity_pilot.customs'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

    // Add some indexes for status and remote id.
    $schema['entity_pilot_arrival']['indexes']['status'] = ['status'];
    $schema['entity_pilot_arrival']['indexes']['remote_id'] = ['remote_id'];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedStates() {
    return [
      FlightInterface::STATUS_PENDING => $this->t('Pending'),
      FlightInterface::STATUS_READY => $this->t('Ready'),
      FlightInterface::STATUS_QUEUED => $this->t('Queued'),
      FlightInterface::STATUS_LANDED => $this->t('Landed'),
      FlightInterface::STATUS_ARCHIVED => $this->t('Archived'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function queue(ArrivalInterface $entity) {
    $this->queue->createItem(FlightStub::create($entity->getRevisionId()));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // @todo Remove this when https://www.drupal.org/node/2481729 is resolved.
    $this->_serviceIds = [];
    $vars = get_object_vars($this);
    foreach ($vars as $key => $value) {
      if (is_object($value) && isset($value->_serviceId)) {
        // If a class member was instantiated by the dependency injection
        // container, only store its ID so it can be used to get a fresh object
        // on unserialization.
        $this->_serviceIds[$key] = $value->_serviceId;
        unset($vars[$key]);
      }
      // Special case the container, which might not have a service ID.
      elseif ($value instanceof ContainerInterface) {
        $this->_serviceIds[$key] = 'service_container';
        unset($vars[$key]);
      }
    }

    unset($vars['queue']);
    return array_keys($vars);
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    // @todo Remove this when https://www.drupal.org/node/2481729 is resolved.
    $container = \Drupal::getContainer();
    foreach ($this->_serviceIds as $key => $service_id) {
      $this->$key = $container->get($service_id);
    }
    $this->_serviceIds = [];
    $this->queue = $container->get('queue')->get('entity_pilot_departures');
  }

  /**
   * {@inheritdoc}
   */
  public function resetCacheAndLoad($id) {
    $this->resetCache([$id]);
    return $this->load($id);
  }

}
