<?php

/**
 * Contains \Drupal\duplicatemail\Controller\DuplicateMailController.
 */

namespace Drupal\duplicatemail\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

/**
 * Returns responses for duplicatemail routes.
 */
class DuplicateMailController extends ControllerBase {

  /**
   * Lists accounts that have duplicate mail addresses.
   */
  public function listMails() {
    return "Sample string.";
  }

  /**
   * Lists accounts with mails same as a particular user.
   */
  public function listPerUser(UserInterface $user) {
    $users = \Drupal::entityManager()
      ->getStorage('user')
      ->loadByProperties(array('mail' => $user->getEmail()));
    $results = 'matching users: ';
    foreach($users as $otherUser) {
    /** @var \Drupal\user\UserInterface $otherUser */
      $results .= $otherUser->getEmail() . ' ' . $otherUser->getUsername() . ' ';
    }
    return $results;
  }
}
