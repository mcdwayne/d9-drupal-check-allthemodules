<?php

namespace Drupal\degov_simplenews\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxy;

/**
 * Class InsertNameService.
 *
 * @package Drupal\degov_simplenews\Service
 */
class InsertNameService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * InsertNameService constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Add the submitted first and last name to the Simplenews subscriber.
   *
   * @param \Drupal\Core\Session\AccountProxy $user
   *   The current user.
   * @param array $subscriberData
   *   The submitted subscriber data.
   */
  public function updateForeAndSurname(AccountProxy $user, array $subscriberData): void {
    if ($user->isAnonymous()) {
      $email = $subscriberData['mail'];
    }
    else {
      $email = $user->getEmail();
    }
    $this->database
      ->update('simplenews_subscriber')
      ->fields([
        'forename' => $subscriberData['forename'],
        'surname'  => $subscriberData['surname'],
      ])
      ->condition('mail', $email, '=')
      ->execute();
  }

}
