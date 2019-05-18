<?php

namespace Drupal\role_test_accounts;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Class RoleTestAccountsManager.
 *
 * @package Drupal\role_test_accounts
 */
class RoleTestAccountsManager implements RoleTestAccountsManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration for Role Test Accounts.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new RoleTestAccountsManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('role_test_accounts.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function createTestAccount($role_id) {
    $user = user_load_by_name('test.' . $role_id);

    if (!$user) {
      $user_storage = $this->entityTypeManager->getStorage('user');
      /** @var \Drupal\user\UserInterface $user */
      $user = $user_storage->create(['name' => 'test.' . $role_id]);
    }
    $user->setPassword($this->config->get('password'));
    $user->activate();
    $user->addRole($role_id);
    $user->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTestAccount($role_id) {
    $user = user_load_by_name('test.' . $role_id);
    if ($user instanceof UserInterface) {
      $user->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generateRoleTestAccounts(Config $config = NULL) {
    if (!$config) {
      $config = $this->config;
    }
    $role_ids = $this->entityTypeManager->getStorage('user_role')->getQuery()->execute();
    unset($role_ids[AccountInterface::ANONYMOUS_ROLE], $role_ids[AccountInterface::AUTHENTICATED_ROLE]);

    if (!empty($config->getOriginal('selection_method') && $config->getOriginal('selection_method') === 'exclude')) {
      $original_roles = array_diff($role_ids, (array) $config->getOriginal('selected_roles'));
    }
    else {
      $original_roles = array_values($config->get('selected_roles'));
    }

    if ($config->get('selection_method') === 'exclude') {
      $new_roles = array_diff($role_ids, $config->get('selected_roles'));
    }
    else {
      $new_roles = array_values($config->get('selected_roles'));
    }

    foreach (array_diff($original_roles, $new_roles) as $role_id_deleted) {
      $this->deleteTestAccount($role_id_deleted);
    }

    foreach (array_diff($new_roles, $original_roles) as $role_id_added) {
      $this->createTestAccount($role_id_added);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRoleTestAccountsPassword($password) {
    foreach ($this->getAllRoleTestAccounts() as $user) {
      $user->setPassword($password)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRoleTestAccounts() {
    $role_ids = $this->entityTypeManager->getStorage('user_role')->getQuery()->execute();
    if (empty($role_ids)) {
      return [];
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    $users = [];

    array_walk($role_ids, function (&$value, $key) {
      $value = 'test.' . $value;
    });
    $query = $user_storage->getQuery();
    $user_ids = $query
      ->condition('name', $role_ids, 'IN')
      ->execute();

    if (!empty($user_ids)) {
      $users = $user_storage->loadMultiple($user_ids);
    }
    return $users;
  }

}
