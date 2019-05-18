<?php

namespace Drupal\friends;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines an interface for vote entity storage classes.
 */
interface FriendsStorageInterface extends EntityStorageInterface {

  /**
   * Get friends of a user, optional paramater the type of friends to fetch.
   *
   * @todo This function should probably return an array of user ids instead
   * of friends ids.
   *
   * @param int|string $uid
   *   The user id to fetch the friends for.
   * @param string $type_id
   *   The Type of friends to fetch, If omitted all types of friends are
   *   fetched.
   *
   * @return array
   *   A key => value array with the ids of the friends entities that this user
   *   is a part of. Either the requester or the recipient.
   */
  public function getUserFriends($uid, $type_id = NULL);

  /**
   * Gets the friendships between 2 users, with optional friendship type.
   *
   * @param int|string $uid
   *   The id of the first user.
   * @param int|string $uid2
   *   The id of the second user.
   * @param string $type_id
   *   The type of friendship to check for.
   *
   * @return array
   *   A key => value array with the ids of the friends entities that connect
   *   the two users.
   */
  public function getUserFriendship($uid, $uid2, $type_id = NULL);

  /**
   * Delete all friend requests that user is involved in.
   *
   * @param int|string $uid
   *   The id of the user to delete the friends for.
   */
  public function deleteUsersFriends($uid);

}
