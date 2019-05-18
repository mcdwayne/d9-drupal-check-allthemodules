<?php

namespace Drupal\entity_pilot\Storage;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\entity_pilot\BaggageHandlerInterface;
use Drupal\entity_pilot\DepartureInterface;
use Drupal\entity_pilot\DepartureStorageInterface;
use Drupal\entity_pilot\FlightInterface;
use Drupal\entity_pilot\Utility\FlightStub;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Provides storage for the 'ep_departure' entity type.
 */
class DepartureStorage extends SqlContentEntityStorage implements DepartureStorageInterface {

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
   * The baggage handler service.
   *
   * @var \Drupal\entity_pilot\BaggageHandlerInterface
   */
  protected $baggageHandler;

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
   *   The entity pilot departures queue.
   * @param \Drupal\entity_pilot\BaggageHandlerInterface $baggage_handler
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
  BaggageHandlerInterface $baggage_handler,
  LanguageManagerInterface $language_manager) {
    parent::__construct($entity_info, $database, $entity_manager, $cache, $language_manager);
    $this->stringTranslation = $translation;
    $this->serializer = $serializer;
    $this->queue = $queue;
    $this->baggageHandler = $baggage_handler;
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
      $container->get('queue')->get('entity_pilot_departures'),
      $container->get('entity_pilot.baggage_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

    // Add some indexes for status and remote id.
    $schema['entity_pilot_departure']['indexes']['status'] = ['status'];
    $schema['entity_pilot_departure']['indexes']['remote_id'] = ['remote_id'];

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
  public function getDependencies(DepartureInterface $entity) {
    $dependencies = [];
    $passenger_uuids = [];
    foreach ($entity->getPassengers() as $passenger) {
      $passenger_uuids[$passenger->uuid()] = $passenger->uuid();
      $dependencies += array_merge($dependencies, $this->baggageHandler->calculateDependencies($passenger));
    }
    return array_diff_key($dependencies, $passenger_uuids);
  }

  /**
   * {@inheritdoc}
   */
  public function queue(DepartureInterface $entity) {
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

}
