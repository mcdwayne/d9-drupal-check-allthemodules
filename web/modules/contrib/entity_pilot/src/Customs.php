<?php

namespace Drupal\entity_pilot;

use Drupal\Component\Graph\Graph;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Error;
use Drupal\entity_pilot\EntityResolver\UnsavedUuidResolverInterface;
use Drupal\entity_pilot\Event\EntityPilotEvents;
use Drupal\entity_pilot\Event\PreparePassengersEvent;
use Drupal\hal\LinkManager\TypeLinkManagerInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Defines a class for dealing with filtering incoming entities.
 */
class Customs implements CustomsInterface {

  use LegacyMessagingTrait;
  use StringTranslationTrait;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The rest resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourceManager;

  /**
   * The typed link manager service.
   *
   * @var \Drupal\rest\LinkManager\TypeLinkManagerInterface
   */
  protected $typeLinkManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The graph of incoming objects.
   *
   * @var array
   */
  protected $graph = [];

  /**
   * A list of vertex objects keyed by their link.
   *
   * @var array
   */
  protected $vertexes = [];

  /**
   * The plugin manager for determining if an entity already exists.
   *
   * @var \Drupal\entity_pilot\ExistsPluginManagerInterface
   */
  protected $existsPluginManager;

  /**
   * Array of deserialized entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $denormalized = [];

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Resolver service.
   *
   * @var \Drupal\entity_pilot\EntityResolver\UnsavedUuidResolverInterface
   */
  protected $resolver;

  /**
   * Cache bin for screened entities.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Constructs the customs manager.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_manager
   *   The rest resource plugin manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\rest\LinkManager\TypeLinkManagerInterface $type_link_manager
   *   The type link manager from the REST module.
   * @param \Drupal\entity_pilot\ExistsPluginManagerInterface $exists_manager
   *   The Entity Pilot exists plugin manager.
   * @param \Drupal\entity_pilot\EntityResolver\UnsavedUuidResolverInterface $resolver
   *   The entity resolver service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   String translation service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache bin for screened entities.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   */
  public function __construct(Serializer $serializer, ResourcePluginManager $resource_manager, EntityManagerInterface $entity_manager, TypeLinkManagerInterface $type_link_manager, ExistsPluginManagerInterface $exists_manager, UnsavedUuidResolverInterface $resolver, TranslationInterface $translation, LoggerChannelFactoryInterface $logger_factory, CacheBackendInterface $cache, EventDispatcherInterface $event_dispatcher) {
    $this->serializer = $serializer;
    $this->resourceManager = $resource_manager;
    $this->entityManager = $entity_manager;
    $this->typeLinkManager = $type_link_manager;
    $this->existsPluginManager = $exists_manager;
    $this->resolver = $resolver;
    $this->stringTranslation = $translation;
    $this->logger = $logger_factory->get('entity_pilot');
    $this->dispatcher = $event_dispatcher;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function screen(ArrivalInterface $flight, $display_errors = TRUE) {
    if ($cache = $this->cache->get($flight->id())) {
      $this->denormalized = $cache->data;
      return $cache->data;
    }
    $this->denormalized = [];
    foreach ($flight->getPassengers() as $id => $passenger) {
      $this->addEdges($passenger, $id);
    }
    $sorted = $this->sortTree($this->graph);
    $event = new PreparePassengersEvent($flight, $flight->getPassengers());
    $this->dispatcher->dispatch(EntityPilotEvents::PREPARE_PASSENGERS, $event);
    $passengers = $event->getPassengers();
    $this->resetTree();
    foreach ($sorted as $id => $details) {
      if (isset($passengers[$id])) {
        $passenger = $passengers[$id];
      }
      else {
        // This passenger was removed during prepare passengers event.
        continue;
      }
      $entity_uri = $passenger['_links']['type']['href'];
      if ($type = $this->typeLinkManager->getTypeInternalIds($entity_uri)) {
        $entity_type_definition = $this->entityManager->getDefinition($type['entity_type']);
        $class = $entity_type_definition->getClass();
      }
      // This entity-type or bundle doesn't exist - continue.
      else {
        if ($display_errors) {
          $this->setMessage($this->t('The entity of type %url cannot be imported by this site and was ignored.', [
            '%url' => $entity_uri,
          ]), 'error');
        }
        $this->logger->error('The entity of type %url cannot be imported by this site and was ignored.', [
          '%url' => $entity_uri,
        ]);
        continue;
      }
      try {
        $id_field = $entity_type_definition->getKey('id');
        unset($passenger[$id_field]);
        if ($revision_field = $entity_type_definition->getKey('revision')) {
          unset($passenger[$revision_field]);
        }
        $unsaved_passenger = $this->serializer->denormalize($passenger, $class, 'hal_json');
        $this->resolver->add($unsaved_passenger);
        $this->denormalized[$id] = $unsaved_passenger;
      }
      catch (ClientException $e) {
        // Error occurred fetching a remote-file, it may no longer exist.
        $request = $e->getRequest();
        $url = $request->getUri();
        if ($display_errors) {
          $this->setMessage($this->t('An error occurred whilst fetching %url, this file will be ignored.', [
            '%url' => $url,
          ]), 'error');
        }
        $this->logger->error('An error occurred whilst fetching %url, this file will be ignored.', [
          '%url' => $url,
        ]);
        continue;
      }
    }
    $this->cache->set($flight->id(), $this->denormalized);
    return $this->denormalized;
  }

  /**
   * Adds a passenger to the dependency graph.
   *
   * @param array $passenger
   *   Normalized passenger in hal+json format.
   * @param string $id
   *   Passenger ID hash.
   */
  protected function addEdges(array $passenger, $id) {
    // @todo - Do away with $id and just use self hrefs - requires changes to
    //   baggage handler.
    // Create a vertex for the graph.
    $vertex = $this->getVertex($id);
    $this->graph[$vertex->id]['edges'] = [];
    if (empty($passenger['_embedded'])) {
      // No dependencies to resolve.
      return;
    }
    // Here we need to resolve our dependencies;.
    foreach ($passenger['_embedded'] as $embedded) {
      foreach ($embedded as $item) {
        $uuid = $item['uuid'][0]['value'];
        $edge = $this->getVertex($uuid);
        $this->graph[$vertex->id]['edges'][$edge->id] = TRUE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function previewPassenger($id) {
    if (!isset($this->denormalized[$id])) {
      throw new \InvalidArgumentException(sprintf('You must screen passengers first, passenger with ID %s has not been screened', $id));
    }
    return $this->denormalized[$id];
  }

  /**
   * Resets the dependency graph.
   */
  protected function resetTree() {
    $this->graph = [];
    $this->vertexes = [];
  }

  /**
   * Sorts current tree.
   *
   * @param array $graph
   *   Graph as an array.
   *
   * @return array
   *   Sorted dependency graph.
   */
  protected function sortTree(array $graph) {
    $graph_object = new Graph($graph);
    $sorted = $graph_object->searchAndSort();
    uasort($sorted, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return array_reverse($sorted);
  }

  /**
   * Returns a vertex object for a given item link.
   *
   * Ensures that the same object is returned for the same item link.
   *
   * @param string $id
   *   Passenger ID.
   *
   * @return object
   *   The vertex object.
   */
  protected function getVertex($id) {
    if (!isset($this->vertexes[$id])) {
      // @todo use a value object here.
      $this->vertexes[$id] = (object) ['id' => $id];
    }
    return $this->vertexes[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function approve(ArrivalInterface $arrival) {
    $saved = [];
    $approved = $arrival->getApproved();
    foreach ($approved as $passenger_id) {
      if ($entity = $this->approvePassenger($passenger_id)) {
        $saved[] = $entity;
      }
    }
    return $saved;
  }

  /**
   * {@inheritdoc}
   */
  public function exists(EntityInterface $passenger) {
    return $this->existsPluginManager->exists($this->entityManager, $passenger);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache(FlightInterface $flight) {
    $this->denormalized = [];
    $this->cache->delete($flight->id());
  }

  /**
   * {@inheritdoc}
   */
  public function approvePassenger($passenger_id) {
    try {
      $entity = $this->previewPassenger($passenger_id);
      if ($exists = $this->exists($entity)) {
        $this->existsPluginManager->preApprove($entity, $exists);
      }
      $entity->save();
      return $entity;
    }
    catch (\Exception $e) {
      if (isset($entity)) {
        $this->logger->error('An error occurred whilst saving %label, this entity could not be updated/imported.', [
          '%label' => $entity->label(),
        ]);
      }
      $this->logger->error('%type: @message in %function (line %line of %file).', Error::decodeException($e));
    }
    return FALSE;
  }

}
