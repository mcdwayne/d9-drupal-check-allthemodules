<?php

/**
 * @file
 * Definition of DataServiceFactory.
 */

namespace WoW\Core\Data;

use WoW\Core\ServiceInterface;

/**
 * Defines the DataService factory.
 */
class DataServiceFactory {

  /**
   * Instantiates a data service class for a given service.
   *
   * @param ServiceInterface $service
   *   The service for which a data service object should be returned.
   *
   * @return DataService
   *   The data service associated with the specified service.
   */
  public static function get(ServiceInterface $service) {
    // Loops through the configuration of services.
    foreach (wow_service_list() as $language => $db_service) {
      // Check if service is enabled and the region is the same as requested.
      if ($db_service->enabled && $db_service->region == $service->getRegion()) {
        // If it does, build the expires array.
        $expires[$language] = $db_service->expires;
      }
    }

    // Wraps the expires array in a virtual object, which contains methods to
    // update the database on object destruction; then returns the DataService
    // as a decorator of the Service object.
    return new DataService($service, new ExpiresArray($expires));
  }

}
