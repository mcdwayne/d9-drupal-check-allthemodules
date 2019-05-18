<?php

namespace Drupal\itemsessionlock;

use Drupal\Core\Session\SessionHandler;

/**
 * Basic Lock object. Seemed an overkill to turn that into Entity.
 * Do not use directly, go through the plugin wrapper instead.
 */
class Lock {

  public $type = '';
  public $iid = '';
  public $timestamp = REQUEST_TIME;
  public $uid = 0;
  public $sid = '';
  public $ssid = '';
  public $data = NULL;

  /**
   *
   * @param array $configuration
   * @param type $plugin_id
   * @param type $plugin_definition
   */
  public function __construct($type, $iid, $data = NULL) {
    $this->type = $type;
    $this->iid = $iid;
    if ($data) {
      $this->data = serialize($data);
    }
  }

  /**
   * Write a lock to database.
   */
  public function write() {
    $account = \Drupal::currentUser();
    \Drupal::database()->merge('itemsessionlock')
        ->key(array('type' => $this->type, 'iid' => $this->iid))
        ->fields(array(
          'uid' => $account->id(),
          'sid' => SessionHandler::getId(),
          //@todo where is $account->getSecureSessionId() gone ?
          'ssid' => SessionHandler::getId(),
          'timestamp' => REQUEST_TIME,
          'data' => !is_null($this->data) ? serialize($this->data) : NULL,
        ))
        ->execute();
  }

  /**
   * Fetch a lock from database.
   */
  public function fetch() {
    $lock = db_query("SELECT * FROM {itemsessionlock} WHERE iid=:iid AND type=:type", array(':iid' => $this->iid, ':type' => $this->type))->fetchObject();
    if ($lock && !empty($lock->data)) {
      $lock->data = unserialize($lock->data);
    }
    if ($lock) {
      foreach ($lock as $property => $value) {
        $this->{$property} = $value;
      }
    }
  }

  /**
   * Delete a lock from database.
   */
  public function delete() {
    db_query("DELETE FROM {itemsessionlock} WHERE iid=:iid AND type=:type", array(':iid' => $this->iid, ':type' => $this->type));
  }

}
