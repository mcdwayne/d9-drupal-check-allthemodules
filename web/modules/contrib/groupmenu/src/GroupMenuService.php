<?php

namespace Drupal\groupmenu;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoader;
use Drupal\system\MenuInterface;

/**
 * Checks access for displaying menu pages.
 */
class GroupMenuService implements GroupMenuServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoader
   */
  protected $membershipLoader;

  /**
   * An array containing the menu access results.
   *
   * @var array
   */
  protected $menuAccess = [];

  /**
   * An array containing the menus for a user.
   *
   * @var array
   */
  protected $userMenus = [];

  /**
   * An array containing the menus for a user and group.
   *
   * @var array
   */
  protected $userGroupMenus = [];

  /**
   * Static cache of all group menu objects keyed by group ID.
   *
   * @var \Drupal\system\MenuInterface[][]
   */
  protected $groupMenus = [];

  /**
   * Constructs a new GroupTypeController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\group\GroupMembershipLoader $membership_loader
   *   The group membership loader.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, GroupMembershipLoader $membership_loader) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function menuAccess($op, MenuInterface $menu, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    if (isset($this->menuAccess[$op][$account->id()][$menu->id()])) {
      return $this->menuAccess[$op][$account->id()][$menu->id()];
    }

    if ($account->hasPermission('administer menu')) {
      return $this->menuAccess[$op][$account->id()][$menu->id()] = AccessResult::allowed();
    }

    $plugin_id = 'group_menu:menu';
    $group_content_types = $this->entityTypeManager->getStorage('group_content_type')
      ->loadByContentPluginId($plugin_id);
    if (empty($group_content_types)) {
      return $this->menuAccess[$op][$account->id()][$menu->id()] = AccessResult::neutral();
    }

    // Load all the group content for this menu.
    $group_contents = $this->entityTypeManager->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $menu->id(),
      ]);

    // If the menu does not belong to any group, we have nothing to say.
    if (empty($group_contents)) {
      return $this->menuAccess[$op][$account->id()][$menu->id()] = AccessResult::neutral();
    }

    /** @var \Drupal\group\Entity\GroupInterface[] $groups */
    $groups = [];
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group = $group_content->getGroup();
      $groups[$group->id()] = $group;
    }

    // From this point on you need group to allow you to perform the requested
    // operation. If you are not granted access for a group, you should be
    // denied access instead.
    foreach ($groups as $group) {
      if ($group->hasPermission("$op $plugin_id entity", $account)) {
        return $this->menuAccess[$op][$account->id()][$menu->id()] = AccessResult::allowed();
      }
    }

    return $this->menuAccess[$op][$account->id()][$menu->id()] = AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function loadUserGroupMenus($op, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    if (isset($this->userMenus[$op][$account->id()])) {
      return $this->userMenus[$op][$account->id()];
    }

    $group_memberships = $this->membershipLoader->loadByUser($account);
    $this->userMenus[$op][$account->id()] = [];
    foreach ($group_memberships as $group_membership) {
      $this->userMenus[$op][$account->id()] += $this->loadUserGroupMenusByGroup($op, $group_membership->getGroupContent()->gid->target_id, $account);
    }

    return $this->userMenus[$op][$account->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadUserGroupMenusByGroup($op, $group_id, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    if (isset($this->userGroupMenus[$op][$account->id()][$group_id])) {
      return $this->userGroupMenus[$op][$account->id()][$group_id];
    }

    $group_menus = $this->getGroupMenus();
    $group_menu_for_group = (!empty($group_menus[$group_id])) ? $group_menus[$group_id] : [];

    return $this->userGroupMenus[$op][$account->id()][$group_id] = $group_menu_for_group;
  }

  /**
   * Get all group menu objects.
   *
   * We create a static cache of group menus since loading them individually
   * has a big impact on performance.
   *
   * @return \Drupal\system\MenuInterface[][]
   *   A nested array containing all group menu objects keyed by group ID.
   */
  public function getGroupMenus() {
    if (!$this->groupMenus) {
      $plugin_id = 'group_menu:menu';

      $menus = $this->entityTypeManager->getStorage('menu')
        ->loadMultiple();

      $group_content_types = $this->entityTypeManager->getStorage('group_content_type')
        ->loadByContentPluginId($plugin_id);
      if (!empty($group_content_types)) {
        $group_contents = $this->entityTypeManager->getStorage('group_content')
          ->loadByProperties(['type' => array_keys($group_content_types)]);

        foreach ($group_contents as $group_content) {
          // Make sure the group and entity IDs are set in the group content
          // entity.
          if (!isset($group_content->gid->target_id) || !isset($group_content->entity_id->target_id)) {
            continue;
          }
          /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
          $this->groupMenus[$group_content->gid->target_id][$group_content->entity_id->target_id] = $menus[$group_content->entity_id->target_id];
        }
      }
    }

    return $this->groupMenus;
  }

}
