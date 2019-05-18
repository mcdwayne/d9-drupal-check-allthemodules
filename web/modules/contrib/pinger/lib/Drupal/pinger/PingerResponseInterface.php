<?php

/**
 * @file
 * Contains \Drupal\pinger\Entity\PingerResponseInterface.
 */

namespace Drupal\pinger;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines getter methods for Pinger Response entity base fields.
 */
interface PingerResponseInterface extends ContentEntityInterface {

  /**
   * Returns the Site ID of the response.
   *
   * @return int
   *
   */
  public function getSite();

  /**
   * Returns the response code.
   *
   * @return string
   *
   */
  public function getResponseCode();

  /**
   * Returns the duration of the request.
   *
   * @return int
   *
   */
  public function getResponseTime();

  /**
   * Returns the timestamp of the response.
   *
   * @return int
   *
   */
  public function getTimestamp();

}
