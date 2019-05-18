<?php

namespace Drupal\friendship;

use Drupal\user\Entity\User;

/**
 * Defines friendship interface.
 */
interface FriendshipInterface {

  /**
   * Follow user.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   User object.
   */
  public function follow(User $target_user);

  /**
   * Unfollow user.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   User object.
   */
  public function unfollow(User $target_user);

  /**
   * Accept friend request.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   User object.
   */
  public function accept(User $target_user);

  /**
   * Remove from friend.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   User object.
   */
  public function removeFriend(User $target_user);

  /**
   * Decline request.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   User object.
   */
  public function decline(User $target_user);

  /**
   * Check if request is already send.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   Target user.
   */
  public function isRequestSend(User $target_user);

  /**
   * Check if current user my friend.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   Target user.
   */
  public function isFriend(User $target_user);

  /**
   * Check if user already have some relationship.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   Target user.
   */
  public function isHasRelationship(User $target_user);

  /**
   * Check if target user is followed you.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   Target user.
   */
  public function isFollowedYou(User $target_user);

}
