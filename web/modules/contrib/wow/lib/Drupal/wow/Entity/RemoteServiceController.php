<?php

/**
 * @file
 * Definition of Drupal\wow\Entity\RemoteServiceController
 */

namespace Drupal\wow\Entity;

use Drupal\wow\Response;
use Drupal\wow\ResponseException;

/**
 * Base class for entity service controller.
 *
 * This communicate to the service, adding required special handling for remote
 * entity objects.
 */
abstract class RemoteServiceController {

  /**
   * The entity type name.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity info array.
   *
   * @var array
   *
   * @see entity_get_info()
   */
  protected $entityInfo;

  /**
   * Name of the entity's ID field in the entity database table.
   *
   * @var string
   */
  protected $idKey;

  /**
   * The storage controller.
   *
   * @var EntityAPIControllerInterface
   */
  protected $storage;

  /**
   * Constructs an EntityServiceController abstract class.
   *
   * @param string $entityType
   *   The entity type.
   * @param array $entityInfo
   *   The entity info array of an entity type.
   * @param \EntityAPIControllerInterface $storage
   *   The entity storage controller.
   */
  public function __construct($entityType, array $entityInfo, \EntityAPIControllerInterface $storage) {
    $this->entityType = $entityType;
    $this->entityInfo = $entityInfo;
    $this->idKey = $entityInfo['entity keys']['id'];
    $this->storage = $storage;
  }

  /**
   * Handle the response.
   *
   * @param \Drupal\wow\Entity\Remote $entity
   * @param \Drupal\wow\Response $response
   */
  protected function handleResponse(Remote $entity, Response $response) {
    // Updates the lastFetched time stamp after a request.
    $entity->lastFetched = $response->getDate()->getTimestamp();

    if ($response->getCode() == 304 || $response->getCode() == 404) {
      // @TODO: remove '404' from IF statement in Drupal 8. This wont be needed
      // anymore.
      // Updates the lastFetched time stamp. This will avoid the trigger of a
      // refresh when deleting an entity for instance if the refresh method is
      // set at load time.
      db_update($this->entityInfo['base table'])
        ->condition($this->idKey, $entity->{$this->idKey})
        ->fields(array('lastFetched' => $entity->lastFetched))
        ->execute();
    }

    switch ($response->getCode()) {
      case 404:
        // The entity was existing but can't be found anymore.
        // The status returned is 404 Not Found. Deletes the local entity.
        $entity->delete();
      default:
      case 500:
        // For both 404 and 500 HTTP status code, break the code execution flow
        // by throwing an exception.
        throw new ResponseException($response);
        break;

      case 200:
        // Merge the response data with the entity.
        $this->merge($entity, $response);
        break;
    }
  }

  /**
   * Merges an entity with a response object.
   *
   * @param \Drupal\wow\Entity\Remote $entity
   *   The entity object to merge.
   * @param \Drupal\wow\Response $response
   *   The response object as returned by the service.
   */
  public function merge(Remote $entity, Response $response) {
    foreach ($response->getData() as $key => $value) {
      $entity->{$key} = $value;
    }
  }

}
