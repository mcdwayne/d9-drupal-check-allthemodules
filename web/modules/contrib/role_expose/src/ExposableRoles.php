<?php

namespace Drupal\role_expose;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\RoleInterface;

/**
 * Class DelegatableRoles.
 *
 * @package Drupal\role_delegation
 */
class ExposableRoles implements ExposableRolesInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AddOrUpdateCustomer object.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getSystemRoles() {
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();

    unset($roles[RoleInterface::ANONYMOUS_ID], $roles[RoleInterface::AUTHENTICATED_ID]);

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleRolesUserHas(EntityInterface $entity) {
    // Prepare the response as an empty list of items.
    $visible_roles = [];

    // User's roles, except the locked roles ('anonymous', 'authenticated').
    // This is a simple array of Role machine names.
    $user_roles = $entity->getRoles(TRUE);

    // Get all roles in the system and loop through them to see what should be
    // exposed and what not.
    foreach ($this->getSystemRoles() as $role) {
      $setting = $role->getThirdPartySetting('role_expose', 'role_expose', ExposableRoles::EXPOSE_NEVER);

      if (in_array($role->id(), $user_roles)) {
        if ($setting == ExposableRoles::EXPOSE_WITH) {
          $visible_roles[] = $role->id();
        }
        elseif ($setting == ExposableRoles::EXPOSE_ALWAYS) {
          $visible_roles[] = $role->id();
        }
      }
    }

    return $visible_roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleRolesUserDoesNotHave(EntityInterface $entity) {
    // Prepare the response as an empty list of items.
    $visible_roles = [];

    // User's roles, except the locked roles ('anonymous', 'authenticated').
    // This is a simple array of Role machine names.
    $user_roles = $entity->getRoles(TRUE);

    // Get all roles in the system and loop through them to see what should be
    // exposed and what not.
    foreach ($this->getSystemRoles() as $role) {
      $setting = $role->getThirdPartySetting('role_expose', 'role_expose', ExposableRoles::EXPOSE_NEVER);
      if (!in_array($role->id(), $user_roles)) {
        if ($setting == ExposableRoles::EXPOSE_WITHOUT) {
          $visible_roles[] = $role->id();
        }
        elseif ($setting == ExposableRoles::EXPOSE_ALWAYS) {
          $visible_roles[] = $role->id();
        }
      }
    }

    return $visible_roles;
  }

}
