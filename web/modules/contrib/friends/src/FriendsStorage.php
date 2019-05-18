<?php

namespace Drupal\friends;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage class for friends entities.
 */
class FriendsStorage extends SqlContentEntityStorage implements FriendsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getUserFriends($uid, $type_id = NULL) {
    $query = $this->getQuery();

    $requesterOrRecipient = $query
      ->orConditionGroup()
      ->condition('user_id', $uid)
      ->condition('recipient', $uid);

    // Status Accept.
    $query->condition($requesterOrRecipient)
      ->condition('friends_status', 'accept');

    if ($type_id) {
      $query->condition('friends_type', $type_id);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getUserFriendship($uid, $uid2, $type_id = NULL) {
    $query = $this->getQuery();

    // User 1 asks user 2.
    $userOneToUserTwo = $query
      ->andConditionGroup()
      ->condition('user_id', $uid)
      ->condition('recipient', $uid2);

    // User 2 asks user 1.
    $userTwoToUserOne = $query
      ->andConditionGroup()
      ->condition('user_id', $uid2)
      ->condition('recipient', $uid);

    // We want either of the above conditions to evaluate to true.
    $requesterOrRecipient = $query
      ->orConditionGroup()
      ->condition($userOneToUserTwo)
      ->condition($userTwoToUserOne);

    $query->condition($requesterOrRecipient);

    if ($type_id) {
      $query->condition('friends_type', $type_id);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUsersFriends($uid) {
    $query = $this->getQuery();

    $requesterOrRecipient = $query
      ->orConditionGroup()
      ->condition('user_id', $uid)
      ->condition('recipient', $uid);

    $friendss = $query->condition($requesterOrRecipient)
      ->execute();

    if (!empty($friendss)) {
      $entities = $this->loadMultiple($friendss);
      $this->delete($entities);
    }
  }

}
