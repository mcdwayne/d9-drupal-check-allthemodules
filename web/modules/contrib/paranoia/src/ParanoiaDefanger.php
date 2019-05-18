<?php

namespace Drupal\paranoia;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides methods to reduce security risks.
 */
class ParanoiaDefanger {

  /**
   * The entity type manager.
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
   * Constructs a new ParanoiaDefanger.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Unsets the admin role property of all roles.
   *
   * @see \Drupal\user\AccountSettingsForm::submitForm()
   */
  public function unsetAdminRole() {
    /** @var \Drupal\user\RoleStorageInterface $role_storage */
    $role_storage = $this->entityTypeManager->getStorage('user_role');
    $admin_roles = $role_storage->getQuery()
      ->condition('is_admin', TRUE)
      ->execute();

    foreach ($admin_roles as $rid) {
      $role = $role_storage->load($rid);
      $role->setIsAdmin(FALSE)->save();
      $this->logger->notice('Removed the admin role property from the %title role.', ['%title' => $role->label()]);
    }
  }
}
