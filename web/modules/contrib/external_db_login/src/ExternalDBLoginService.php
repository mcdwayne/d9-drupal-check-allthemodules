<?php

namespace Drupal\external_db_login;

use Drupal;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\DatabaseExceptionWrapper;

/**
 * External DB Login Service.
 */
class ExternalDBLoginService {

  /**
   * Test Connection.
   */
  public function testConnection() {

    try {
      $this->createConnection();
      $connection = Database::getConnection();
    }
    catch (\PDOException $e) {
      $connection = Database::getConnection('default', 'default');
    }
    return $connection->getKey();
  }

  /**
   * Create connection with new database information.
   */
  public function createConnection() {
    // Set data in $info array.
    $info = array(
      'database' => $this->getConfig('external_db_login_database'),
      'username' => $this->getConfig('external_db_login_username'),
      'password' => $this->getConfig('external_db_login_password'),
      'prefix' => '',
      'host' => $this->getConfig('external_db_login_host'),
      'port' => $this->getConfig('external_db_login_port'),
      'driver' => $this->getConfig('external_db_login_driver'),
    );
    // Add connection with new database setting.
    Database::addConnectionInfo('external_db_login_connection', 'default', $info);
    try {
      // Active new connection.
      Database::setActiveConnection('external_db_login_connection');
    }
    catch (ConnectionNotDefinedException $e) {
      // Active default connection if new connection is not stablished.
      Database::setActiveConnection('default');
    }
  }

  /**
   * Set default connection.
   */
  public function setDefaultConnection() {
    Database::setActiveConnection();
  }

  /**
   * Check current user is external user or not.
   */
  public function checkExternalUser($uid) {
    $validate = 0;
    $connection = Database::getConnection();
    $check = $connection->select('users_field_data', 'ufd')
      ->fields('ufd', array('init'))
      ->condition('uid', $uid)
      ->execute()->fetchObject();
    if($check->init === 'external_mail') {
        $validate = 1;
    }
    return $validate;
  }

    /**
   * Authenticate new user.
   *
   * @param mixed $email
   *   Pass new user's email-id.
   * @param mixed $password
   *   Pass new user's password.
   */
  public function authUserAccount($email, $password) {
    try {
      // Create connection and active external database.
      $this->createConnection();
      $connection = Database::getConnection();
      // Get set information of user table.
      $user_table = $this->getConfig('external_db_login_user_table');
      $email_field = $this->getConfig('external_db_login_user_email');
      $password_field = $this->getConfig('external_db_login_user_password');
      // Check if user is exist in external database.
      $user_data = $connection->select($user_table, 'u');
      $user_data->fields('u', array($email_field));
      $user_data->condition($email_field, $email);
      $user_data->condition($password_field, $password);
      $user_result = $user_data->execute()->fetchObject();
      // Active default database.
      $this->setDefaultConnection();
      // If user found return email id of that user.
      if (count($user_result) > 0) {
        return $user_result->$email_field;
      }
      else {
        // If user not found return false.
        return FALSE;
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Handle exception found.
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('external_db_login', $e);
      return FALSE;
    }
  }

  /**
   * Get user has value from external database.
   *
   * @param mixed $email
   *   Pass email id.
   */
  public function getUserPasswordHash($email) {
    // Create connection and active external database.
    $this->createConnection();
    $connection = Database::getConnection();
    try {
      // Get set information of user table.
      $user_table = $this->getConfig('external_db_login_user_table');
      $email_field = $this->getConfig('external_db_login_user_email');
      $password_field = $this->getConfig('external_db_login_user_password');
      // Get password has value if user exist in database.
      $user_data = $connection->select($user_table, 'u');
      $user_data->fields('u', array($password_field));
      $user_data->condition($email_field, $email);
      $user_result = $user_data->execute()->fetchObject();
      // Set dafault database.
      $this->setDefaultConnection();
      if (count($user_result) > 0) {
        // Return password has value.
        return $user_result->$password_field;
      }
      else {
        // Return false if not exist.
        return FALSE;
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Handel database exception.
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('external_db_login', $e);
      return FALSE;
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('external_db_login', $e);
      return FALSE;
    }
  }

  /**
   * Return user setting value.
   */
  public function getConfig($config) {
    return Drupal::config('external_db_login.settings')->get($config);
  }

}
