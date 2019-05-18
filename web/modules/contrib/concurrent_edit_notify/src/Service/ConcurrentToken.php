<?php

namespace Drupal\concurrent_edit_notify\Service;

use Drupal\Core\Database\Connection;
use Drupal\concurrent_edit_notify\ConcurrentTokenInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class ConcurrentToken.
 */
class ConcurrentToken implements ConcurrentTokenInterface {

  /**
   * The Database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new ConcurrentToken object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * Save token.
   *
   * @param array $token
   *   Array of token values.
   *
   * @return bool
   *   Return true if saved.
   */
  public function save(array $token) {
    $save = NULL;
    if ($this->database instanceof Connection) {
      if (!$this->check($token)) {
        $save = $this->database->insert('concurrent_edit_token')->fields([
          'nid' => $token['nid'],
          'uid' => $token['uid'],
          'langcode' => $token['langcode'],
          'status' => $token['status'],
          'created' => REQUEST_TIME,
        ])->execute();
      }
      elseif ($this->isDisplayed($token['nid'], $token['langcode'], $token['uid'])) {
        $this->resetDisplayed($token['nid'], $token['langcode'], $token['uid']);
        $save = TRUE;
      }
    }

    return $save;
  }

  /**
   * Load tokens.
   *
   * @param int $nid
   *   Node id of node.
   * @param string $langcode
   *   Language code of node translation.
   *
   * @return array
   *   Array of token objects.
   */
  public function load($nid, $langcode) {
    $results = [];
    if ($this->database instanceof Connection) {
      $query = $this->database->select('concurrent_edit_token', 'uet');
      $query->condition('uet.nid', $nid, '=');
      $query->condition('uet.langcode', $langcode, '=');
      $data = $query->fields('uet', ['uid', 'nid', 'langcode',
        'status', 'created',
      ])->execute();
      // Get all results.
      $results = $data->fetchAll(\PDO::FETCH_OBJ);
    }

    return $results;
  }

  /**
   * Load First token.
   *
   * @param int $nid
   *   Node id of node.
   * @param string $langcode
   *   Language code of node translation.
   *
   * @return array
   *   Array of token objects.
   */
  public function loadFirst($nid, $langcode) {
    $result = NULL;
    if ($this->database instanceof Connection) {
      $query = $this->database->select('concurrent_edit_token', 'uet');
      $query->condition('uet.nid', $nid, '=');
      $query->condition('uet.langcode', $langcode, '=');
      $query->orderBy('uet.id', 'ASC');
      $query->range(0, 1);
      $data = $query->fields('uet', ['uid', 'nid', 'langcode',
        'status', 'created',
      ])->execute();
      // Get all results.
      $result = $data->fetchObject();
    }

    return $result;
  }

  /**
   * Delete tokens.
   *
   * @param int $nid
   *   Node id of node.
   * @param string $langcode
   *   Language code of node translation.
   *
   * @return bool
   *   Return true if deleted.
   */
  public function delete($nid, $langcode) {
    $delete = FALSE;
    if ($this->database instanceof Connection) {
      $delete = $this->database->delete('concurrent_edit_token')
        ->condition('nid', $nid, '=')
        ->condition('langcode', $langcode, '=')
        ->execute();
    }

    return $delete;
  }

  /**
   * Check if token is displayed.
   *
   * @param int $nid
   *   Node id of node.
   * @param string $langcode
   *   Language code of node translation.
   * @param int $uid
   *   User id of current author.
   *
   * @return bool
   *   FALSE is token is displayed.
   */
  public function isDisplayed($nid, $langcode, $uid) {
    $status = 0;
    if ($this->database instanceof Connection) {
      $query = $this->database->select('concurrent_edit_token', 'uet');
      $query->condition('uet.nid', $nid, '=');
      $query->condition('uet.langcode', $langcode, '=');
      $query->condition('uet.uid', $uid, '=');
      $query->orderBy('uet.id', 'DESC');
      $query->range(0, 1);
      $data = $query->fields('uet', ['status'])->execute();
      // Get all results.
      $status = $data->fetchObject()->status;
    }

    return $status;
  }

  /**
   * Set token as displayed.
   *
   * @param int $nid
   *   Node id of node.
   * @param string $langcode
   *   Language code of node translation.
   * @param int $uid
   *   User id of current author.
   */
  public function setDisplayed($nid, $langcode, $uid) {
    if ($this->database instanceof Connection) {
      $this->database->update('concurrent_edit_token')->fields([
        'status' => 1,
      ])->condition('nid', $nid, '=')
        ->condition('langcode', $langcode, '=')
        ->condition('uid', $uid, '=')
        ->execute();
    }
  }

  /**
   * Reset token as not displayed.
   *
   * @param int $nid
   *   Node id of node.
   * @param string $langcode
   *   Language code of node translation.
   * @param int $uid
   *   User id of current author.
   */
  public function resetDisplayed($nid, $langcode, $uid) {
    if ($this->database instanceof Connection) {
      $this->database->update('concurrent_edit_token')->fields([
        'status' => 0,
      ])->condition('nid', $nid, '=')
        ->condition('langcode', $langcode, '=')
        ->condition('uid', $uid, '=')
        ->execute();
    }
  }

  /**
   * Check if token is already exists.
   *
   * @param array $token
   *   Array of token values.
   *
   * @return bool
   *   Return true if not exists.
   */
  public function check(array $token) {
    $status = FALSE;
    if ($this->database instanceof Connection) {
      $query = $this->database->select('concurrent_edit_token', 'uet');
      $query->condition('uet.nid', $token['nid'], '=');
      $query->condition('uet.langcode', $token['langcode'], '=');
      $query->condition('uet.uid', $token['uid'], '=');
      $count = $query->countQuery()->execute()->fetchField();
      // Set TRUE if row exists.
      if ($count) {
        $status = TRUE;
      }
    }

    return $status;
  }

  /**
   * Check if user account is logged in.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   *
   * @return bool
   *   Return TRUE if account is logged in, else FALSE.
   */
  public function isAccountLoggedIn(AccountInterface $account) {
    if ($account->id() && ($this->database instanceof Connection)) {
      $query = $this->database->select('sessions', 's');
      $query->condition('s.uid', $account->id(), '=');
      $count = $query->countQuery()->execute()->fetchField();
      if ($count) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
