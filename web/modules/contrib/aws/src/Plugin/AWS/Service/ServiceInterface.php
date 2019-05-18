<?php

namespace Drupal\aws\Plugin\AWS\Service;

use Drupal\aws\Entity\ProfileInterface;

/**
 * Provides an interface defining an AWS Service Client.
 */
interface ServiceInterface {

  /**
   * Instantiates a new AWS Client with the given profile.
   *
   * @param \Drupal\aws\Entity\ProfileInterface $profile
   *   The AWS profile object.
   */
  public function loadProfile(ProfileInterface $profile);

}
