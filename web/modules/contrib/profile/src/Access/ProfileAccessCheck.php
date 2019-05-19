<?php

namespace Drupal\profile\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileTypeInterface;

/**
 * Checks access to add, edit and delete profiles.
 */
class ProfileAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ProfileAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the profile add page for the profile type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user entity.
   * @param \Drupal\profile\Entity\ProfileTypeInterface $profile_type
   *   The profile type entity.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, AccountInterface $user, ProfileTypeInterface $profile_type) {
    // Create a stub profile to pass to entity access.
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    // Create a stubbed Profile entity for access checks.
    $profile_stub = $profile_storage->create([
      'type' => $profile_type->id(),
      'uid' => $user->id(),
    ]);
    $operation = $profile_type->allowsMultiple() ? 'view' : 'update';
    $access_handler = $this->entityTypeManager->getAccessControlHandler('profile');
    return $access_handler->access($profile_stub, $operation, $account, TRUE);
  }

}
