<?php

namespace Drupal\redirect_extensions;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Session\AccountInterface;

/**
 * Class RedirectDatabaseStorage.
 */
class RedirectDatabaseStorage implements RedirectDatabaseStorageInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a new RedirectDatabaseStorage object.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $database
   *   The Database connection.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(Connection $database, AccountInterface $current_user) {
    $this->database = $database;
    $this->user = $current_user;
  }

  /**
   * Insert a redirect.
   *
   * @param string $redirect_id
   *   New redirect ID.
   */
  public function insertRedirect($redirect_id) {

    // Save current UNIX timestamp.
    $date = REQUEST_TIME;

    $record = [
      'rid' => $redirect_id,
      'uid_created' => $this->user->id(),
      'created' => $date,
      'modified' => $date,
    ];

    $this->database->insert('redirect_extensions')->fields($record)->execute();
  }

  /**
   * Check if redirect id exists.
   *
   * @param string $redirect_id
   *   Redirect ID to be checked.
   *
   * @return bool
   *   True if redirect exists.
   */
  public function redirectExists($redirect_id) {
    $query = $this->database->select('redirect_extensions', 'r');
    $query->fields('r');
    $query->condition('rid', $redirect_id, '=');
    $result = $query->execute();

    $result->allowRowCount = TRUE;

    if ($result->rowCount() > 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

  /**
   * Update a redirect.
   *
   * @param string $redirect_id
   *   ID of redirect being updated.
   */
  public function updateRedirect($redirect_id) {

    // Save current UNIX timestamp.
    $date = REQUEST_TIME;

    $record = [
      'modified' => $date,
    ];

    $this->database->update('redirect_extensions')
      ->fields($record)
      ->condition('rid', $redirect_id, '=')
      ->execute();
  }

  /**
   * Delete a redirect.
   *
   * @param string $redirect_id
   *   ID of redirect being deleted.
   */
  public function deleteRedirect($redirect_id) {
    $this->database->delete('redirect_extensions')
      ->condition('rid', $redirect_id, '=')
      ->execute();

  }

}
