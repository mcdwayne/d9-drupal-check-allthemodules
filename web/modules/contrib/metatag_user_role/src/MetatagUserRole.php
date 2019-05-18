<?php

/**
 * @file
 * Contains \Drupal\metatag_user_role\MetatagUserRole.
 */

namespace Drupal\metatag_user_role;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\UserInterface;
use Drupal\user\RoleInterface;

/**
 * Provides logic for user role metatags.
 */
class MetatagUserRole {

  /**
   * Storage of the role entities.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The Metatag defaults.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $metatagDefaults;

  /**
   * Bundles of the user entity type.
   *
   * @var array
   */
  protected $userBundles;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * MetatagUserRole constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, ModuleHandlerInterface $module_handler) {
    $this->roleStorage = $entity_type_manager->getStorage('user_role');
    $this->metatagDefaults = $entity_type_manager->getStorage('metatag_defaults');
    $this->userBundles = $bundle_info->getBundleInfo('user');
    $this->moduleHandler = $module_handler;
  }

  /**
   * Returns an array of available roles to override.
   *
   * @return array
   *    A list of available roles as $id => $label.
   */
  public function getOptions() {
    $options = [];
    $roles = $this->getRoles();
    foreach ($this->userBundles as $bundle_id => $bundle) {
      foreach ($roles as $role_id => $role) {
        $id = "user__{$bundle_id}__{$role_id}";
        if (empty($this->metatagDefaults->load($id))) {
          $options[$id] = $this->getLabel($bundle['label'], $role->label());
        }
      }
    }
    return $options;
  }

  /**
   * Adds the role label to the end of the original label.
   *
   * @param string $label
   *   The original label.
   * @param string $role
   *   The role label.
   *
   * @return string
   *   The resulting label.
   */
  public function getLabel($label, $role) {
    return strtr('label (role)', ['label' => $label, 'role' => $role]);
  }

  /**
   * Extracts all appropriate default tags for a priority user role.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity to extract meta tags from.
   *
   * @return array
   *   Array of metatags.
   */
  public function getTags(UserInterface $user) {
    $tags = [];
    $roles = $this->getRoles();
    // Trigger hook_metatag_user_role_roles_alter().
    // Allow modules to override roles used to extract meta tags.
    $this->moduleHandler->alter('metatag_user_role_roles', $roles, $user);

    foreach ($roles as $role_id => $role) {
      // The user must have a role.
      if ($user->hasRole($role_id)) {
        $id = "user__{$user->bundle()}__{$role_id}";

        // Check that metatags are specified for the role.
        /** @var \Drupal\metatag\MetatagDefaultsInterface $role_metatags */
        $role_metatags = $this->metatagDefaults->load($id);
        if ($role_metatags !== NULL) {
          // If metatags are found, then exit the loop.
          $tags = $role_metatags->get('tags');
          break;
        }
      }
    }
    return $tags;
  }

  /**
   * Returns an array of roles, excluding the authenticated and anonymous roles.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An associative array with the role id as the key and the role object as
   *   value.
   */
  protected function getRoles() {
    $roles = $this->roleStorage->loadMultiple();
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    return $roles;
  }

}
