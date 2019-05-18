<?php

namespace Drupal\friends;

use Drupal\friends\Entity\FriendsInterface;

/**
 * Interface FriendsServiceInterface.
 */
interface FriendsServiceInterface {

  /**
   * Returns the allowed types of the friends entities.
   *
   * @return array
   *   A key => value array pairs of the allowed friend types
   */
  public function getAllowedTypes();

  /**
   * Returns the allowed status of the friends entities.
   *
   * @param bool $all
   *   If the return array will include all allowed statuses
   *   or only statuses that are allowed to be applied to the friend request.
   *
   * @return array
   *   A key => value array pairs of the allowed friend status
   */
  public function getAllowedStatus(bool $all = FALSE);

  /**
   * Gets the complete message to show to the user for a friends entity.
   *
   * @param string $message
   *   The machine_name of the message to get.
   * @param \Drupal\friends\Entity\FriendsInterface $friends
   *   The friends entity to generate the message for.
   *
   * @return string
   *   A token processed string.
   */
  public function getMessage(string $message, FriendsInterface $friends);

}
