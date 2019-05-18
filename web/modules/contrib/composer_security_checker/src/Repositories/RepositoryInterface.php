<?php

namespace Drupal\composer_security_checker\Repositories;

/**
 * Class RepositoryInterface.
 *
 * @package Drupal\composer_security_checker
 */
interface RepositoryInterface {

  /**
   * Get a list of available updates for installed Composer packages.
   *
   * @return \Drupal\composer_security_checker\Collections\AdvisoryCollection
   *   A collection of Advisories.
   */
  public function getAvailableUpdates();

}
