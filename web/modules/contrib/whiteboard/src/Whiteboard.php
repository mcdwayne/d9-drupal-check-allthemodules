<?php

/**
 * @file
 * Whiteboard class definition.
 * Contains \Drupal\whiteboard\Whiteboard.
 */

namespace Drupal\whiteboard;

/**
 * Represents a whiteboard.
 */
class Whiteboard {

  protected $changedAttributes = array();

  protected $wbid = 0;
  
  protected $uid = 0;

  protected $title = '';

  protected $marks = '';

  protected $format = FALSE;

  public function __construct($wbid = FALSE) {
    if ($wbid) {
      $this->load($wbid);
    } 
  }

  public function load($wbid) {
    $data = db_query('SELECT * FROM {whiteboard} WHERE wbid = :wbid', array(':wbid' => $wbid))->fetchObject();
    if ($data) {
      $this->wbid = $data->wbid;
      $this->uid = $data->uid;
      $this->title = $data->title;
      $this->marks = $data->marks;
      $this->format = $data->format ? $data->format : filter_fallback_format();
    }
  }

  public function set($key, $value) {
    if (isset($this->$key) && $this->$key !== $value) {
      $this->$key = $value;
      if (!in_array($key, $this->changedAttributes)) {
        $this->changedAttributes[] = $key;
      }
    }
  }  

  public function get($key) {
    if (isset($this->$key)) {
      return $this->$key;
    }
  }

  public function saveMarks($marks) {
    # replace extraneous divs
    $marks['marks'] = preg_replace("/<div>\n<\/div>/U", '', $marks['marks']);
    $marks['marks'] = preg_replace("/<div><\/div>/U", '', $marks['marks']);
    $marks['marks'] = preg_replace("/\n/U", '', $marks['marks']);
  
    $id = \Drupal::database()->update('whiteboard')
            ->fields($marks)
            ->execute();
    
  }

  /**
   * Check to see if the given user has access to this chat.
   *
   * @param mixed $account
   * @param mixed $access_type
   * @return boolean
   */
  public function userHasAccess($account, $access_type) {
    // No module cares about access to this chatroom, run our standard checks.
    $admin_role_rid = \Drupal::service('config.factory')->get('user.settings')->get('admin_role');
    if ($account->id() == 1 || ($admin_role_rid && (in_array($admin_role_rid, $account->getRoles())))) {
      return TRUE;
    }

    if ($access_type == 'read' && user_access('see any whiteboard', $account)) {
      return TRUE;
    }

    if ($access_type == 'write' && user_access('write any whiteboard', $account)) {
      return TRUE;
    }
    return FALSE;
  } 

  public function save() {
    $transaction = db_transaction();
    try {
      $data = array(
        'wbid' => $this->wbid,
        'title' => $this->title,
        'uid' => $this->uid,
        'marks' => $this->marks,
        'format' => $this->format,
      );

      $op = $this->wbid ? 'update' : 'insert';

      if ($op == 'update') {
        \Drupal::database()->merge('whiteboard')
          ->key(array('wbid' => $this->wbid))
          ->fields($data)
          ->execute();
      }
      else {
        $this->wbid = \Drupal::database()->insert('whiteboard')
          ->fields($data)
          ->execute();
      }
    }
    catch (Exception $e) {
      $transaction->rollback();
      \Drupal::logger('whiteboard')->error($e);
      return FALSE;
    }
  }
}
  
