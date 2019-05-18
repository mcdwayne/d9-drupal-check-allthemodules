<?php

/**
 * @file
 * Contains \Drupal\pinger\Entity\PingerSiteInterface.
 */

namespace Drupal\pinger;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines getter methods for Pinger Site entity base fields.
 */
interface PingerSiteInterface extends ContentEntityInterface {

  /**
   * Returns the URL of the site.
   *
   * @return string
   *   The URL of the file, e.g. https://drupal.org.
   */
  public function getUrl();

}
