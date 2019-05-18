<?php

namespace Drupal\role_test_accounts\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\role_test_accounts\RoleTestAccountsManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Update role test accounts on configuration updates.
 *
 * @package Drupal\role_test_accounts\EventSubscriber
 */
class RoleTestAccountsSettingsSubscriber implements EventSubscriberInterface {

  /**
   * The role test accounts manager.
   *
   * @var \Drupal\role_test_accounts\RoleTestAccountsManagerInterface
   */
  protected $roleTestAccountsManager;

  /**
   * Constructs a RoleTestAccountsSettingsSubscriber object.
   *
   * @param \Drupal\role_test_accounts\RoleTestAccountsManagerInterface $role_test_accounts_manager
   *   The role test accounts manager.
   */
  public function __construct(RoleTestAccountsManagerInterface $role_test_accounts_manager) {
    $this->roleTestAccountsManager = $role_test_accounts_manager;
  }

  /**
   * Update role test accounts on configuration updates.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    if ($event->getConfig()->getName() === 'role_test_accounts.settings') {
      $config = $event->getConfig();

      $this->roleTestAccountsManager->generateRoleTestAccounts($config);

      if ($config->get('password') <> $config->getOriginal('password')) {
        $this->roleTestAccountsManager->setRoleTestAccountsPassword($config->get('password'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
