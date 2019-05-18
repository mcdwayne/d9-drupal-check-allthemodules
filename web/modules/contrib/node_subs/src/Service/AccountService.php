<?php

namespace Drupal\node_subs\Service;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AccountController.
 */
class AccountService {

  use StringTranslationTrait;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var Configuration
   */
  protected $config;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a new AccountService object.
   *
   * * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The parser to use when extracting message variables.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, Connection $connection, ConfigFactoryInterface $config_factory, ModuleHandler $module_handler) {
    $this->logger = $logger_factory->get('node_subs');
    $this->connection = $connection;
    $this->config = $config_factory->get('node_subs.settings');
    $this->moduleHandler = $module_handler;
  }

  public function loadMultiple(array $ids = [], array $condtitions = [], $limit = FALSE) {
    $query = $this->connection->select(NODE_SUBS_ACCOUNT_TABLE, 'account')
      ->fields('account');
    if ($ids) {
      $query->condition('account.id', $ids, 'IN');
    }
    if ($condtitions) {
      foreach ($condtitions as $field => $value) {
        if ($this->connection->schema()->fieldExists(NODE_SUBS_ACCOUNT_TABLE, $field)) {
          $query->condition('account.' . $field, $value);
        }
      }
    }
    if ($limit) {
      $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit);
    }
    $accounts = $query->execute()->fetchAllAssoc('id');

    foreach ($accounts as &$account) {
      $account->data = unserialize($account->data);
      foreach ($account->data as $key => $value) {
        if ($key != 'data') {
          $account->{$key} = $value;
          unset($account->data[$key]);
        }
      }
    }

    return $accounts;
  }

  /**
   * Loads email subscription by its id.
   * @param integer $id
   * @return bool|mixed
   */
  public function load($id = NULL) {
    $accounts = $this->loadMultiple([$id]);
    if ($accounts) {
      return array_shift($accounts);
    }
    else {
      return FALSE;
    }
  }

  public function loadByEmail($email, array $conditions = []) {
    $conditions = ['email' => $email] + $conditions;
    $accounts = $this->loadMultiple([], $conditions);
    if ($accounts) {
      return array_shift($accounts);
    }
    else {
      return FALSE;
    }
  }

  /**
   * @param array|object $account
   * @param bool $check_email
   * @return object
   * @throws \Exception
   */
  public function save($account, $check_email = TRUE) {
    if (is_array($account)) {
      $account = (object) $account;
    }
    $this->prepare($account);
    $update = FALSE;
    if (isset($account->id)) {
      $exists_account = $this->load($account->id);
      if ($exists_account) {
        $update = TRUE;
      }
    }
    else {
      if ($check_email) {
        $exists_account = $this->loadByEmail($account->email);
        if ($exists_account) {
          $update = TRUE;
          $account->id = $exists_account->id;
        }
      }
    }
    if (!isset($account->status)) {
      $account->status = 1;
    }

    $account->deleted = 0;

    $this->connection
      ->merge(NODE_SUBS_ACCOUNT_TABLE)
      ->key(['email' => $account->email])
      ->fields((array) $account)
      ->execute();

    if ($update) {
      $this->logger->info('account @mail updated', ['@mail' => $account->email]);
    }
    else {
      $this->logger->info('account @mail inserted', ['@mail' => $account->email]);
    }

    return $account;
  }

  public function create($email, $name = FALSE) {
    $account = new \stdClass();
    if ($name) {
      $account->name = $name;
    }
    $account->email = $email;
    $this->save($account);
    return $account;
  }

  public function prepare($account) {
    $available_keys = ['id', 'email', 'name', 'status'];
    $data = array();
    foreach ((array) $account as $key => $value) {
      if (!in_array($key, $available_keys)) {
        $data[$key] = $value;
        unset($account->{$key});
      }
    }
    $account->data = serialize($data);
    if (empty($account->name)) {
      $account->name = \Drupal\user\Entity\Role::load('anonymous')->label();
    }
  }

  public function getBatch($progress = 0) {
    $limit = $this->config->get('node_subs_count_per_batch');
    $ids = $this->connection->select(NODE_SUBS_ACCOUNT_TABLE, 'account')
      ->fields('account', array('id'))
      ->condition('account.status', 1)
      ->range($progress * $limit, $limit)
      ->execute()->fetchCol();

    return $this->loadMultiple($ids);
  }

  public function checkProgress($progress) {
    $progress++;
    $limit = $this->config->get('node_subs_count_per_batch');
    $query = $this->connection->select(NODE_SUBS_ACCOUNT_TABLE, 'account')
      ->fields('account', array('email'))
      ->condition('account.status', 1)
      ->range($progress * $limit, $limit);
    $count = $query->countQuery()->execute()->fetchField();
    return (bool) $count;
  }

  // todo: rename this maslo maslyanoe...
  public function countAccounts($sended = FALSE, $progress = 0) {
    $query = $this->connection->select(NODE_SUBS_ACCOUNT_TABLE, 'account')
      ->fields('account', array('email'))
      ->condition('account.status', 1);
    if ($sended) {
      $limit = $this->config->get('node_subs_count_per_batch');
      $query->range(0, $progress * $limit);
    }
    $count = $query->countQuery()->execute()->fetchField();
    return $count;
  }

  public function deleteRecords() {
    $this->connection->delete(NODE_SUBS_ACCOUNT_TABLE)
      ->condition('deleted', 1)
      ->execute();
    $this->moduleHandler->invoke('node_subs', 'node_subs_account_delete_records');
  }

  public function delete($subscriber) {
    if (is_int($subscriber)) {
      $subscriber = $this->load($subscriber);
    }
    $this->moduleHandler->invokeAll('node_subs_account_delete', ['subscriber' => $subscriber]);

    $this->connection->update(NODE_SUBS_ACCOUNT_TABLE)
      ->condition('id', $subscriber->id)
      ->fields([
        'deleted' => 1,
        'status' => 0,
      ])
      ->execute();
  }

  public function getToken($email) {
    $site_name = \Drupal::config('system.site')->get('name');
    $sold = Unicode::strlen($site_name);
    $text = $sold . str_replace('@', '-', $email);
    return md5($text);
  }

}
