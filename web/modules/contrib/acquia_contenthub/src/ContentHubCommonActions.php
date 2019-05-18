<?php

namespace Drupal\acquia_contenthub;

use Acquia\ContentHubClient\CDF\CDFObjectInterface;
use Acquia\ContentHubClient\CDFDocument;
use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\acquia_contenthub\Event\ContentHubPublishEntitiesEvent;
use Drupal\acquia_contenthub\Event\DeleteRemoteEntityEvent;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\EntityInterface;
use Drupal\depcalc\DependencyCalculator;
use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContentHubCommonActions {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The entity cdf serializer.
   *
   * @var \Drupal\acquia_contenthub\EntityCdfSerializer
   */
  protected $serializer;

  /**
   * The dependency calculator.
   *
   * @var \Drupal\depcalc\DependencyCalculator
   */
  protected $calculator;

  /**
   * The ContentHub client factory.
   *
   * @var \Drupal\acquia_contenthub\Client\ClientFactory
   */
  protected $factory;

  /**
   * ContentHubCommonActions constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\acquia_contenthub\EntityCdfSerializer $serializer
   *   The entity cdf serializer.
   * @param \Drupal\depcalc\DependencyCalculator $calculator
   *   The dependency calculator.
   * @param \Drupal\acquia_contenthub\Client\ClientFactory $factory
   *   The ContentHub client factory.
   */
  public function __construct(EventDispatcherInterface $dispatcher, EntityCdfSerializer $serializer, DependencyCalculator $calculator, ClientFactory $factory) {
    $this->dispatcher = $dispatcher;
    $this->serializer = $serializer;
    $this->calculator = $calculator;
    $this->factory = $factory;
  }

  /**
   * Get a single merged CDF Document of entities and their dependencies.
   *
   * This is useful for getting a single merged CDFDocument of various entities
   * and all their dependencies. It will
   *
   * @param \Drupal\Core\Entity\EntityInterface ...$entities
   *   The entities for which to generate a CDFDocument.
   *
   * @return \Acquia\ContentHubClient\CDFDocument
   * @throws \Exception
   */
  public function getLocalCdfDocument(EntityInterface ...$entities) {
    $document = new CDFDocument();
    $wrappers = [];
    foreach ($entities as $entity) {
      $entityDocument = new CDFDocument(...array_values($this->getEntityCdf($entity, $wrappers, FALSE)));
      $document->mergeDocuments($entityDocument);
    }
    return $document;
  }

  /**
   * Gets the CDF objects representation of an entity and its dependencies.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which to calculate dependencies and generate CDFObjects.
   * @param array (optional) $entities
   *   The array of collected DependentEntityWrappers.
   * @param bool $return_minimal
   *   Whether to dispatch the PUBLISH_ENTITIES event subscribers.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject[]
   * @throws \Exception
   */
  public function getEntityCdf(EntityInterface $entity, array &$entities = [], bool $return_minimal = TRUE) {
    $wrapper = new DependentEntityWrapper($entity);
    $stack = new DependencyStack();
    $this->calculator->calculateDependencies($wrapper, $stack);
    /** @var \Drupal\depcalc\DependentEntityWrapper[] $entities */
    $entities = NestedArray::mergeDeep([$wrapper->getUuid() => $wrapper], $stack->getDependenciesByUuid(array_keys($wrapper->getDependencies())));
    if ($return_minimal) {
      // Modify/Remove objects before publishing to ContentHub service.
      $event = new ContentHubPublishEntitiesEvent($entity->uuid(), ...array_values($entities));
      $this->dispatcher->dispatch(AcquiaContentHubEvents::PUBLISH_ENTITIES, $event);
      $entities = $event->getDependencies();
    }

    return $this->serializer->serializeEntities(...array_values($entities));
  }

  /**
   * Import a group of entities by their uuids from the ContentHub Service.
   *
   * The uuids passed are just the list of entities you absolutely want,
   * ContentHub will calculate all the missing entities and ensure they are
   * installed on your site.
   *
   * @param string ...$uuids
   *   The list of uuids to import.
   *
   * @return \Drupal\depcalc\DependencyStack
   * @throws \Exception
   */
  public function importEntities(string ...$uuids) {
    $document = $this->getCdfDocument(...$uuids);
    return $this->importEntityCdfDocument($document);
  }

  /**
   * Imports a list of entities from a CDFDocument object.
   *
   * @param \Acquia\ContentHubClient\CDFDocument $document
   *   The CDF document representing the entities to import.
   *
   * @return \Drupal\depcalc\DependencyStack
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function importEntityCdfDocument(CDFDocument $document) {
    $stack = new DependencyStack();
    $this->serializer->unserializeEntities($document, $stack);
    return $stack;
  }

  /**
   * Retrieves entities and dependencies by uuid and returns a CDFDocument
   *
   * @param string ...$uuids
   *   The list of uuids to retrieve.
   *
   * @return \Acquia\ContentHubClient\CDFDocument
   * @throws \Exception
   */
  public function getCdfDocument(string ...$uuids) {
    foreach ($uuids as $uuid) {
      if (!Uuid::isValid($uuid)) {
        throw new \Exception(sprintf("Invalid uuid %s.", $uuid));
      }
    }
    $document = $this->getClient()->getEntities($uuids);
    $uuids_count = count($uuids);
    $document_count = count($document->getEntities());
    if ($uuids_count > $document_count) {
      throw new \Exception(sprintf("Did not retrieve all requested entities. %d of %d retrieved. Missing entities: %s", $document_count, $uuids_count, implode(", ", array_diff($uuids, array_keys($document->getEntities())))));
    }
    $missing_dependencies = [];
    foreach ($document->getEntities() as $cdf) {
      // @todo add the hash to the CDF so that we can check it here to see if we need to update.
      foreach ($cdf->getDependencies() as $dependency => $hash) {
        // If the document doesn't have a version of this dependency, it might
        // be missing.
        if (!$document->hasEntity($dependency)) {
          $missing_dependencies[] = $dependency;
        }
      }
    }
    // Retrieve missing dependencies.
    if ($missing_dependencies) {
      $dependent_entities = $this->getCdfDocument(...array_unique($missing_dependencies));
      $document->mergeDocuments($dependent_entities);
    }
    return $document;
  }

  /**
   * Get the remote entity CDFObject if available.
   *
   * @param string $uuid
   *   The uuid of the remote entity to retrieve.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObjectInterface|array|null
   */
  public function getRemoteEntity(string $uuid) {
    try {
      $client = $this->getClient();
      $entity = $client->getEntity($uuid);
      if (!$entity instanceof CDFObjectInterface) {
        if (isset($entity['error']['message'])) {
          throw new \Exception($entity['error']['message']);
        }

        throw new \Exception('Unexpected error.');
      }
      return $entity;
    }
    catch (\Exception $e) {
      \Drupal::logger('acquia_contenthub')
        ->error('Error during remote entity retrieval: @error_message', ['@error_message' => $e->getMessage()]);
    }
    return NULL;
  }

  /**
   * Delete a remote entity if we own it.
   *
   * @param string $uuid
   *   The uuid of the remote entity to delete.
   *
   * @return bool|void
   * @throws \Exception
   */
  public function deleteRemoteEntity(string $uuid) {
    if (!Uuid::isValid($uuid)) {
      throw new \Exception(sprintf("Invalid uuid %s.", $uuid));
    }
    $remote_entity = $this->getRemoteEntity($uuid);
    if (!$remote_entity) {
      return;
    }
    $client = $this->getClient();
    if ($client->getSettings()->getUuid() !== $remote_entity->getOrigin()) {
      return;
    }
    $response = $client->deleteEntity($uuid);
    if ($response->getStatusCode() === 202) {
      // Clean up the interest list.
      if ($settings = $client->getSettings()) {
        $webhook_uuid = $settings->getWebhook('uuid');
        $client->deleteInterest($uuid, $webhook_uuid);
      }
      $event = new DeleteRemoteEntityEvent($uuid);
      $this->dispatcher->dispatch(AcquiaContentHubEvents::DELETE_REMOTE_ENTITY, $event);
    }
    return $response->getStatusCode() === 202;
  }

  /**
   * Gets the client or throws a common exception when it's unavailable.
   *
   * @return \Acquia\ContentHubClient\ContentHubClient|bool
   * @throws \Exception
   */
  protected function getClient() {
    $client = $this->factory->getClient();
    if (!$client) {
      throw new \Exception("Client is not properly configured. Please check your ContentHub registration credentials.");
    }
    return $client;
  }

}
