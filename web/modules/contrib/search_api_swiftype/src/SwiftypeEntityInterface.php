<?php

namespace Drupal\search_api_swiftype;

/**
 * Defines the interface for a Swiftype entity.
 */
interface SwiftypeEntityInterface {

  /**
   * Get the Swiftype client service.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientService
   *   The Swiftype client service.
   */
  public function getClientService();

  /**
   * Get internal Id of the entity.
   *
   * @return string
   *   The internal entity Id.
   */
  public function getId();

  /**
   * Get raw entity data.
   *
   * @return array
   *   The raw data of the Swiftype entity.
   */
  public function getRawData();

}
