<?php

namespace Drupal\acsf_sso\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\samlauth\Event\SamlauthEvents;
use Drupal\samlauth\Event\SamlauthUserSyncEvent;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that synchronizes user properties on a user_sync event.
 */
class SamlauthUserSyncSubscriber implements EventSubscriberInterface {

  const ATTRIBUTE_NAME_ROLES = 'roles';
  const ATTRIBUTE_NAME_IS_OWNER = 'is_owner';

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Construct a new SamlauthUserSyncSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [SamlauthEvents::USER_SYNC => 'onUserSync'];
  }

  /**
   * Performs actions to synchronize users with Factory data on login.
   *
   * @param \Drupal\samlauth\Event\SamlauthUserSyncEvent $event
   *   The user sync event.
   */
  public function onUserSync(SamlauthUserSyncEvent $event) {
    $attributes = $event->getAttributes();

    // Add the specified roles. The values are role names that are supposed to
    // exist on this site already.
    $add_role_machine_names = [];
    if (!empty($attributes[static::ATTRIBUTE_NAME_ROLES])) {
      foreach ($attributes[static::ATTRIBUTE_NAME_ROLES] as $role_name) {
        // These same values are (/ can be) used for Drupal 7 sites, where they
        // are equal to the role names. We don't want to use the values as
        // 'names' (labels) because these are translatable, which could get
        // messy, so we derive machine names from them.
        $add_role_machine_names[] = str_replace(' ', '_', strtolower($role_name));
      }
    }

    if (!empty($attributes[static::ATTRIBUTE_NAME_IS_OWNER])) {
      // This is the site owner. Make sure the user has the administrator role.
      // (Below is what D8 core does everywhere: the data model allows multiple
      // admin roles but the configuration screen and all the code silently
      // assume one / discard others that might have been hacked into the db.)
      $admin_roles = $this->entityTypeManager->getStorage('user_role')->getQuery()
        ->condition('is_admin', TRUE)
        ->execute();
      $add_role_machine_names[] = reset($admin_roles);
    }

    $account = $event->getAccount();
    foreach (array_unique($add_role_machine_names) as $role_machine_name) {
      // If someone accidentally tries to assign 'authenticated user', skip to
      // prevent exceptions from being thrown.
      if (!$account->hasRole($role_machine_name) && !in_array($role_machine_name, [RoleInterface::AUTHENTICATED_ID, RoleInterface::ANONYMOUS_ID])) {

        if ($role = Role::load($role_machine_name)) {
          $account->addRole($role_machine_name);
          $event->markAccountChanged();

          drupal_set_message(t('Site Factory assigned the "@role_name" role to the account.', ['@role_name' => $role->label()]));
          $this->logger->notice('Site Factory assigned the "@role" role to the account.', ['@role' => $role_machine_name]);
        }
        elseif (!$role) {
          drupal_set_message(t('Automatic role assignment failed because the website does not have a "@role_name" role.', ['@role_name' => $role_machine_name]), 'warning');
        }
      }
    }
  }

}
