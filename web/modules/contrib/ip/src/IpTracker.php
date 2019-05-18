<?php

namespace Drupal\ip;

/* 
 * @file
 * IpTracker Manager.
 */

class IpTracker {

  private $connection;
  private $account;
  private $request;

  function __construct(\Drupal\Core\Database\Connection $connection, \Symfony\Component\HttpFoundation\Request $request, \Drupal\Core\Session\AccountInterface $account) {
    $this->connection = $connection;
    $this->request = $request;
    $this->account = $account;
  }

  /**
   * Save the IpTrack
   * @return type
   */
  function save() {

    $ip  = $this->request->getClientIp();
    $uid = $this->account->id();

    $iplong = ip2long($ip);

    if (!empty($iplong)) {

      // Check to see if a row exists for this uid/ip combination.
      $sql = "SELECT visits FROM {ip_tracker} WHERE uid = :uid AND ip = :ip";
      $args = array(':uid' => $uid, ':ip' => $iplong);
      $visits = $this->connection->query($sql, $args)->fetchField();

      if ($visits) {
        // Update.
        return $this->connection->update('ip_tracker')
          ->fields(
            array(
              'visits' => $visits + 1,
              'last_visit' => REQUEST_TIME,
            )
          )
          ->condition('uid', $uid)
          ->condition('ip', $iplong)
          ->execute();
      }
      else {
        // Insert.
        return $this->connection->insert('ip_tracker')
          ->fields(
            array(
              'uid' => $uid,
              'ip' => $iplong,
              'visits' => 1,
              'first_visit' => REQUEST_TIME,
              'last_visit' => REQUEST_TIME,
            )
          )
          ->execute();
      }
      
    }
    
  }

  /**
   * Remove records in the ip_tracker table for a certain user.
   */
  function remove() {
    return $this->connection->delete('ip_tracker')
      ->condition('uid', $this->account->id())
      ->execute();
  }

}
