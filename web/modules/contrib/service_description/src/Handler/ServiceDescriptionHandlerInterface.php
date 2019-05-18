<?php

namespace Drupal\service_description\Handler;

/**
 * Defines an interface to list available service descriptions.
 */
interface ServiceDescriptionHandlerInterface {

  /**
   * Gets all available service descriptions.
   *
   * @return array
   *   An array of descriptions.
   */
  public function getDescriptions();

}
