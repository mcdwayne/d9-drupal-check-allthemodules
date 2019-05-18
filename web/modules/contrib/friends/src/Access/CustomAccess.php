<?php

namespace Drupal\friends\Access;

use Drupal\user\UserInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

use Drupal\friends\Entity\FriendsInterface;

/**
 * Provides access checks for friends api controller.
 */
class CustomAccess {

  /**
   * Drupal\friends\FriendsService definition.
   *
   * @var \Drupal\friends\FriendsService
   */
  protected $friendsService;

  /**
   * Drupal\friends\FriendsStorage definition.
   *
   * @var \Drupal\friends\FriendsStorage
   */
  protected $friendsStorage;

  /**
   * Constructs a new CustomAccess object.
   */
  public function __construct() {
    $entity_type_manager = \Drupal::entityTypeManager();
    $this->friendsService = \Drupal::service('friends.default');
    $this->friendsStorage = $entity_type_manager->getStorage('friends');
  }

  /**
   * A custom access check for sending friend request to a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param Drupal\user\UserInterface $user
   *   The user to send the friend request to.
   * @param string $type
   *   The status to update the friend request to.
   */
  public function accessMakeRequest(AccountInterface $account, UserInterface $user, string $type) {
    // Cannot add yourself as a friend.
    if ($account->id() === $user->id()) {
      return AccessResult::forbidden();
    }

    // Can only add friends of the allowed types.
    $allowed_types = $this->friendsService->getAllowedTypes();
    if (!in_array($type, array_keys($allowed_types), TRUE)) {
      return AccessResult::forbidden();
    }

    // You can add friend if you haven't already added this user with this type.
    $friendship = $this->friendsStorage->getUserFriendship($account->id(), $user->id(), $type);
    if (!count($friendship)) {
      return AccessResult::allowed();
    }

    // If above didn't do it deny access.
    return AccessResult::forbidden();
  }

  /**
   * A custom access check for responding to a friend request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param Drupal\friends\Entity\FriendsInterface $friends
   *   The friends entity the user is trying to respond to.
   * @param string $status
   *   The status to update the friend request to.
   */
  public function accessResponse(AccountInterface $account, FriendsInterface $friends, string $status) {
    // If you are not the recipient of the request you can't respond to it.
    if ($account->id() != $friends->getRecipientId()) {
      return AccessResult::forbidden();
    }

    // You cannot set a friend request back to pending.
    if ($status === 'pending') {
      return AccessResult::forbidden();
    }

    // You can only respond to request by the allowed values defined in.
    $allowed_status = $this->friendsService->getAllowedStatus();
    if (!in_array($status, array_keys($allowed_status), TRUE)) {
      return AccessResult::forbidden();
    }

    // You can only respond to a pending friend request.
    if ($friends->get('friends_status')->value === 'pending') {
      return AccessResult::allowed();
    }

    // If above didn't do it deny access.
    return AccessResult::forbidden();
  }

}
