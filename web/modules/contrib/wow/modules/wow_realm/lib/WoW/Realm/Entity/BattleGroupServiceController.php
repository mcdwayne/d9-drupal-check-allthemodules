<?php

/**
 * Definition of BattleGroupServiceController.
 */

namespace WoW\Realm\Entity;

use WoW\Core\Callback\CallbackException;

use WoW\Core\Entity\EntityServiceController;

/**
 * Service controller class for battlegroups.
 */
class BattleGroupServiceController extends EntityServiceController {

  /**
   * The battle groups data API provides the list of battle groups for a region.
   *
   * @param string $region
   *   The region to fetch data against.
   *
   * @return array
   *   An array of battle groups keyed by slug.
   */
  public function fetchAll($region) {
    // Returns the execution of the request on this service.
    return $this->service($region)
      ->newRequest('data/battlegroups/')
        ->onResponse()
          // If a problem occurs, throw an exception.
          ->mapException(0)
          // Merge all the battle groups returned by the service.
          ->mapCallback(200, new BattleGroupMerge($this->storage))
          ->execute();
  }

}
