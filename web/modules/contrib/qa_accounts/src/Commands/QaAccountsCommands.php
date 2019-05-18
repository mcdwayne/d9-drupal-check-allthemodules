<?php

namespace Drupal\qa_accounts\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines drush commands for QA Accounts module.
 */
class QaAccountsCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory->get('qa_accounts');
  }

  /**
   * Creates a test user for each custom role.
   *
   * @usage drush qa_accounts:create
   *   Create a test user for each custom user role.
   *
   * @command qa_accounts:create
   *
   * @aliases test-users-create,create-test-users,qac
   */
  public function testUsersCreate() {
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    foreach ($roles as $role_name => $role) {
      if ($role_name == 'anonymous') {
        continue;
      }
      $username = 'qa_' . $role_name;
      $user = user_load_by_name($username);
      if ($user) {
        $this->loggerFactory->notice('User @name already exists.', ['@name' => $username]);
      }
      else {
        /** @var \Drupal\user\Entity\User $user */
        $user = $this->entityTypeManager->getStorage('user')->create();
        $user->enforceIsNew();
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        $user->setPassword($username);
        if ($role_name != 'authenticated') {
          $user->addRole($role_name);
        }
        $user->activate();
        $user->save();
        $this->loggerFactory->notice('Created user @name.', ['@name' => $username]);
      }
    }
  }

  /**
   * Deletes the test users created by QA Accounts.
   *
   * @usage drush qa_accounts:delete
   *   Deletes the test users created by QA Accounts.
   *
   * @command qa_accounts:delete
   *
   * @aliases test-users-delete,delete-test-users,qad
   */
  public function testUsersDelete() {
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    foreach ($roles as $role_name => $role) {
      if ($role_name == 'anonymous') {
        continue;
      }
      $username = 'qa_' . $role_name;
      $user = user_load_by_name($username);
      if ($user) {
        $user->delete();
        $this->loggerFactory->notice('Deleted user @name.', ['@name' => $username]);
      }
      else {
        $this->loggerFactory->notice('No such user @name.', ['@name' => $username]);
      }
    }
  }
}
