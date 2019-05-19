<?php

namespace Drupal\lopd;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class LopdService.
 *
 * @package Drupal\lopd
 */
class LopdService implements LopdServiceInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $configFactory, Connection $database) {
    $this->configFactory = $configFactory;
    $this->database = $database;
  }


  /**
   * Registers the given $operation for the given $account.
   *
   * @param $account
   * @param type $operation
   *   The operation being registered.
   */
  public function lopdRegisterOperation($account, $operation) {
    return $this->database->insert('lopd')
      ->fields(array(
        'uid' => method_exists($account, 'id') ? $account->id() : $account->uid,
        'authname' => method_exists($account, 'getAccountName') ? $account->getAccountName() : $account->name,
        'ip' => \Drupal::request()->getClientIp(),
        'operation' => $operation,
        'timestamp' => time()))
      ->execute();
  }

  /**
   * Registers an log in operation for the given $user.
   *
   * @param \Drupal\user\UserInterface $account
   */
  public function lopdRegisterLogin($account) {
    return $this->lopdRegisterOperation($account, self::LOPD_OPERATION_LOGIN);
  }

  /**
   * Registers an log out operation for the given $user.
   *
   * @param \Drupal\user\UserInterface $account
   */
  public function lopdRegisterLogout($account) {
    return $this->lopdRegisterOperation($account, self::LOPD_OPERATION_LOGOUT);
  }

  /**
   * Registers an validation error operation for the given $user.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   */
  public function lopdRegisterValidationAttempt($form_state) {
    if (empty($form_state->getStorage()['uid'])) {
      // The validation failed, we have to register the login attempt.
      $user_name = $form_state->getValue('name');
      $account = $this->getAccountByName($user_name);

      if (empty($account)) {
        $account = new \stdClass();
        $account->uid = 0;
        $account->name =  $user_name;
      }

      $this->lopdRegisterOperation($account, self::LOPD_OPERATION_LOGIN_FAILED);
    }
  }

  /**
   * Helper function for fetching the user data from {users_field_data} giving
   * a $user_name.
   *
   * @param string $user_name
   * @return mixed $account
   */
  protected function getAccountByName($user_name) {
    $account = $this->database->query('SELECT * FROM {users_field_data} WHERE name = :name',
      [':name' => $user_name])->fetchObject();

    return $account;
  }

  /**
   * Remove the lopd registries previous to date set at the configuration.
   */
  public function lopdDeleteRegisters() {
    // Cleanup the lopd table.
    $years = $this->configFactory->get('lopd.settings')->get('messages_to_keep');
    if ($years > 0 ) {
      $this->database->delete('lopd')
        ->condition('timestamp', strtotime('-' . $years . 'years'), '<')
        ->execute();
    }
  }
}
