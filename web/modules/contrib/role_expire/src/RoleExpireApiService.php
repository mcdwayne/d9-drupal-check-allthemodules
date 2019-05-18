<?php

namespace Drupal\role_expire;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;

/**
 * Class RoleExpireApiService.
 */
class RoleExpireApiService {

  /**
   * Configuration factory.
   *
   * Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Database connection.
   *
   * Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new RoleExpireApiService object.
   */
  public function __construct(ConfigFactory $configFactory, Connection $connection) {
    $this->config = $configFactory->get('role_expire.config');
    $this->database = $connection;
  }

  /**
   * Get expiration time of a user role.
   *
   * @param int $uid
   *   User ID.
   * @param string $rid
   *   Role ID.
   *
   * @return array
   *   Array with the expiration time.
   */
  public function getUserRoleExpiryTime($uid, $rid) {

    $query = $this->database->select('role_expire', 'n');
    $query->fields('n', ['expiry_timestamp']);
    $query->condition('n.uid', $uid, '=');
    $query->condition('n.rid', $rid, '=');
    $result = $query->execute()->fetchField();

    return (!empty($result)) ? $result : '';
  }

  /**
   * Get expiration of all roles of a user.
   *
   * @param int $uid
   *   User ID.
   *
   * @return array
   *   Array with the expiration time.
   */
  public function getAllUserRecords($uid) {

    $query = $this->database->select('role_expire', 'n');
    $query->fields('n', [
      'rid',
      'expiry_timestamp',
    ]
    );
    $query->condition('n.uid', $uid, '=');
    $result = $query->execute()->fetchAll();

    $return = [];
    foreach ($result as $row) {
      $return[$row->rid] = $row->expiry_timestamp;
    }

    return $return;
  }

  /**
   * Delete a record from the database.
   *
   * @param int $uid
   *   User ID.
   * @param string $rid
   *   Role ID.
   */
  public function deleteRecord($uid, $rid) {
    $query = $this->database->delete('role_expire');
    $query->condition('uid', $uid)->condition('rid', $rid);
    $query->execute();
  }

  /**
   * Delete all records for role.
   *
   * @param string $rid
   *   Role ID.
   */
  public function deleteRoleRecords($rid) {
    $this->database->delete('role_expire')->condition('rid', $rid)->execute();
  }

  /**
   * Delete all user expirations.
   *
   * @param int $uid
   *   User ID.
   */
  public function deleteUserRecords($uid) {
    $this->database->delete('role_expire')->condition('uid', $uid)->execute();
  }

  /**
   * Insert or update a record in the database.
   *
   * @param int $uid
   *   User ID.
   * @param string $rid
   *   Role ID.
   * @param int $expiry_timestamp
   *   The expiration timestamp.
   */
  public function writeRecord($uid, $rid, $expiry_timestamp) {

    // Delete previous expiry for user and role if it exists.
    $this->deleteRecord($uid, $rid);

    // Insert new expiry for user and role.
    $query = $this->database->insert('role_expire');
    $query->fields(['uid', 'rid', 'expiry_timestamp']);
    $query->values(['uid' => $uid, 'rid' => $rid, 'expiry_timestamp' => $expiry_timestamp]);
    $query->execute();
  }

  /**
   * Get the default duration for a role.
   *
   * @param string $rid
   *   Required. The role_id to check.
   *
   * @return string
   *   String containing the strtotime compatible default duration of the role
   *   or empty string if not set.
   */
  public function getDefaultDuration($rid) {

    $query = $this->database->select('role_expire_length', 'n');
    $query->fields('n', ['duration']);
    $query->condition('n.rid', $rid, '=');
    $result = $query->execute()->fetchField();

    return (!empty($result)) ? $result : '';
  }

  /**
   * Insert or update the default expiry duration for a role.
   *
   * @param string $rid
   *   Role ID.
   * @param string $duration
   *   The strtotime-compatible duration string.
   */
  public function setDefaultDuration($rid, $duration) {

    if (!empty($duration)) {
      // Delete previous default duration if it exists.
      $this->deleteDefaultDuration($rid);

      // Insert new default duration.
      $query = $this->database->insert('role_expire_length')->fields(['rid', 'duration']);
      $query->values(['rid' => $rid, 'duration' => Html::escape($duration)]);
      $query->execute();
    }
  }

  /**
   * Delete default duration(s) for a role.
   *
   * @param string $rid
   *   Required. The role_id to remove.
   */
  public function deleteDefaultDuration($rid) {
    $this->database->delete('role_expire_length')->condition('rid', $rid)->execute();
  }

  /**
   * Get all records that should be expired.
   *
   * @param int $time
   *   Optional. The time to check, if not set it will check current time.
   *
   * @return array
   *   All expired roles.
   */
  public function getExpired($time = '') {
    $return = [];
    if (!$time) {
      date_default_timezone_set(drupal_get_user_timezone());
      $time = \Drupal::time()->getRequestTime();
    }

    $query = $this->database->select('role_expire', 'n');
    $query->fields('n', [
      'rid',
      'uid',
      'expiry_timestamp',
    ]
    );
    $query->condition('n.expiry_timestamp', $time, '<=');
    $result = $query->execute()->fetchAll();

    foreach ($result as $row) {
      $return[] = $row;
    }
    return $return;
  }

  /**
   * Get roles to assign on expiration (global configuration).
   *
   * @return array
   *   Returns an array where the key is the original rid and the value the
   *   one that has to be assigned on expiration. The array will be empty if
   *   configuration is not set.
   */
  public function getRolesAfterExpiration() {
    $values_raw = $this->config->get('role_expire_default_roles');
    $values = empty($values_raw) ? [] : json_decode($values_raw, TRUE);
    return $values;
  }

  /**
   * Sets the default role duration for the current user/role combination.
   *
   * It won't override the current expiration time for user's role.
   *
   * @param $role_id
   *   The ID of the role.
   * @param $uid
   *   The user ID.
   */
  function processDefaultRoleDurationForUser($role_id, $uid) {
    // Does a default expiry exist for this role?
    $default_duration = $this->getDefaultDuration($role_id);
    if ($default_duration) {
      $user_role_expiry = $this->getUserRoleExpiryTime($uid, $role_id);
      // If the expiry is empty then we act!.
      if (!$user_role_expiry) {
        // Use strtotime of default duration.
        \Drupal::service('role_expire.api')->writeRecord($uid, $role_id, strtotime($default_duration));
        \Drupal::logger('role_expire')->notice(t('Added default duration @default_duration to role @role to user @account.', array('@default_duration' => $default_duration, '@role' => $role_id, '@account' => $uid)));
      }
    }
  }

}
