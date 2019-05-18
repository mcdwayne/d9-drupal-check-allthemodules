<?php

namespace Drupal\private_message_flood\Service;

/**
 * Provides services for the Private Message Flood Protection module.
 */
interface PrivateMessageFloodServiceInterface {

  /**
   * Determines whether a user has reached their private message post limit.
   *
   * Flood limits are set per role. This function will find the lowest weighted
   * role that the user currently holds, and retrieve the flood limit values for
   * that role. It will then check if a flood limit has been set for said role,
   * and check if the user has gone over the limit. Flooding can be set either
   * to limit the number of posts per duration, or the number of threads per
   * duration. This function checks whichever has been set.
   *
   * @return bool
   *   A boolean indicating whether or not they have reached their flood limit.
   *   TRUE means they have reached the limit, and should be stopped from
   *   posting. FALSE means they can post as normal.
   */
  public function checkUserFlood();

}
