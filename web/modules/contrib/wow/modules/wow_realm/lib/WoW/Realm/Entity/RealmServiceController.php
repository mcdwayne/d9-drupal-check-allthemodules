<?php

/**
 * @file
 * Definition of RealmServiceController.
 */

namespace WoW\Realm\Entity;

use WoW\Core\Entity\EntityServiceController;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * Service controller class for realms.
 */
class RealmServiceController extends EntityServiceController {

  /**
   * Realm APIs currently provide realm status information.
   *
   * @param Realm $realm
   *   The realm to fetch.
   *
   * @return Response
   *   A response object.
   */
  public function fetch(Realm $realm) {
    // Returns the execution of the request on this service.
    return $this->service($realm->region)
      ->newRequest('realm/status')
        ->setQuery('realms', $realm->slug)
        ->onResponse()
          ->mapCallback(200, new RealmMerge($this->storage, $realm))
          ->execute();
  }

  /**
   * Realm APIs currently provide realm status information.
   *
   * The realm status API allows developers to retrieve realm status information.
   * This information is limited to whether or not the realm is up, the type and
   * state of the realm, the current population, and the status of the two world
   * pvp zones.
   *
   * There are no required query parameters when accessing this resource, although
   * an array parameter can optionally be passed to limit the realms returned to
   * one or more.
   *
   * @param string $region
   *   The region to call the service against.
   * @param array $realms
   *   (Optional) A list of realms to fetch (indexed array of slug).
   *
   * @return Response
   *   A response object.
   */
  public function fetchAll($region, array $realms = array()) {
    // Returns the execution of the request on this service.
    return $this->service($region)
      ->newRequest('realm/status')
        ->setQuery('realms', $realms)
        ->onResponse()
          // If a problem occurs, throw an exception.
          ->mapException(0)
          // Merge all the realms returned by the service with existing entities.
          ->mapCallback(200, new RealmMergeAll($this->storage, $realms))
          ->execute();
  }

}
