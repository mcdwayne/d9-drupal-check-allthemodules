<?php
/**
 * @file
 * Contains \Drupal\mailmute_test\Controller\MailmuteTestController.
 */

namespace Drupal\mailmute_test\Controller;

use Drupal\Core\Language\LanguageInterface;
use Drupal\user\Entity\User;

/**
 * Page controller for mailmute_test.
 */
class MailmuteTestController {

  /**
   * Sends a mail to the current user.
   *
   * @param string $email
   *   The address to send an email to.
   *
   * @return array
   *   A simple render array.
   */
  public function mail($email) {
    $user = user_load_by_mail($email);
    \Drupal::service('plugin.manager.mail')->mail('user', 'status_blocked', $user->getEmail(), LanguageInterface::LANGCODE_DEFAULT, array('account' => $user));
    return array('#markup' => 'Hello World!');
  }

}
