<?php

namespace Drupal\group\Access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\GroupRoleSynchronizerInterface;

/**
 * Calculates group permissions for an account.
 */
class GroupPermissionCalculator implements GroupPermissionCalculatorInterface {

  /**
   * The cache backend interface to use for the persistent cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache backend interface to use for the static cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $static;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group role synchronizer service.
   *
   * @var \Drupal\group\GroupRoleSynchronizerInterface
   */
  protected $groupRoleSynchronizer;

  /**
   * The membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs a GroupPermissionsCalculator object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend interface to use for the persistent cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface $static
   *   The cache backend interface to use for the static cache.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\GroupRoleSynchronizerInterface $group_role_synchronizer
   *   The group role synchronizer service.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   */
  public function __construct(CacheBackendInterface $cache, CacheBackendInterface $static, EntityTypeManagerInterface $entity_type_manager, GroupRoleSynchronizerInterface $group_role_synchronizer, GroupMembershipLoaderInterface $membership_loader) {
    $this->cache = $cache;
    $this->static = $static;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupRoleSynchronizer = $group_role_synchronizer;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateAnonymousPermissions() {
    $cid = 'group_anonymous_permissions';

    // Retrieve the permissions from the static cache if available.
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }
    // Retrieve the permissions from the persistent cache if available.
    elseif ($cache = $this->cache->get($cid)) {
      $permissions = $cache->data;
      $cache_tags = $cache->tags;
    }
    // Otherwise build the permissions and store them in the persistent cache.
    else {
      $permissions = $this->buildAnonymousPermissions();
      $cache_tags = $permissions->getCacheTags();
      $this->cache->set($cid, $permissions, Cache::PERMANENT, $cache_tags);
    }

    // Store the permissions in the static cache.
    $this->static->set($cid, $permissions, Cache::PERMANENT, $cache_tags);

    return $permissions;
  }

  /**
   * Builds the anonymous group permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissions
   *   An object representing the anonymous group permissions.
   */
  protected function buildAnonymousPermissions() {
    $calculated_permissions = new CalculatedGroupPermissions();

    // @todo Introduce group_role_list:audience:anonymous cache tag.
    // If a new group type is introduced, we need to recalculate the anonymous
    // permissions hash. Therefore, we need to introduce the group type list
    // cache tag.
    $calculated_permissions->addCacheTags(['config:group_type_list']);

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $storage = $this->entityTypeManager->getStorage('group_type');
    foreach ($storage->loadMultiple() as $group_type_id => $group_type) {
      $group_role = $group_type->getAnonymousRole();

      $item = new CalculatedGroupPermissionsItem(
        CalculatedGroupPermissionsItemInterface::SCOPE_GROUP_TYPE,
        $group_type_id,
        $group_role->getPermissions()
      );

      $calculated_permissions->addItem($item);
      $calculated_permissions->addCacheableDependency($group_role);
    }

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateOutsiderPermissions(AccountInterface $account) {
    // The permissions you have for each group type as an outsider are the same
    // for anyone with the same user roles. So it's safe to cache the complete
    // set of outsider permissions you have per group type and re-use that cache
    // for anyone else with the same user roles.
    $roles = $account->getRoles(TRUE);
    sort($roles);

    $cid = 'group_outsider_permissions_' . md5(serialize($roles));

    // Retrieve the permissions from the static cache if available.
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }
    // Retrieve the permissions from the persistent cache if available.
    elseif ($cache = $this->cache->get($cid)) {
      $permissions = $cache->data;
      $cache_tags = $cache->tags;
    }
    // Otherwise build the permissions and store them in the persistent cache.
    else {
      $permissions = $this->buildOutsiderPermissions($roles);
      $cache_tags = $permissions->getCacheTags();
      $this->cache->set($cid, $permissions, Cache::PERMANENT, $cache_tags);
    }

    // Store the permissions in the static cache.
    $this->static->set($cid, $permissions, Cache::PERMANENT, $cache_tags);

    return $permissions;
  }

  /**
   * Builds the outsider group permissions.
   *
   * The permissions you have for each group type as an outsider are a
   * combination of the permissions configured on the outsider role and those
   * configured on the group roles you receive through role synchronization.
   *
   * @param string[] $roles
   *   The user roles for which to build the outsider permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissions
   *   An object representing the outsider group permissions.
   */
  protected function buildOutsiderPermissions(array $roles) {
    $calculated_permissions = new CalculatedGroupPermissions();

    // @todo Introduce group_role_list:audience:outsider cache tag.
    // If a new group type is introduced, we need to recalculate the outsider
    // permissions. Therefore, we need to introduce the group type list cache
    // tag.
    $calculated_permissions->addCacheTags(['config:group_type_list']);

    $group_type_storage = $this->entityTypeManager->getStorage('group_type');
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    foreach ($group_type_storage->loadMultiple() as $group_type_id => $group_type) {
      $group_role = $group_type->getOutsiderRole();
      $permissions = $group_role->getPermissions();
      $calculated_permissions->addCacheableDependency($group_role);

      $group_role_ids = [];
      foreach ($roles as $role_id) {
        $group_role_ids[] = $this->groupRoleSynchronizer->getGroupRoleId($group_type_id, $role_id);
      }

      if (!empty($group_role_ids)) {
        /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
        foreach ($group_role_storage->loadMultiple($group_role_ids) as $group_role) {
          $permissions = array_merge($permissions, $group_role->getPermissions());
          $calculated_permissions->addCacheableDependency($group_role);
        }
      }

      // Make sure the permissions only appear once per group type.
      $item = new CalculatedGroupPermissionsItem(
        CalculatedGroupPermissionsItemInterface::SCOPE_GROUP_TYPE,
        $group_type_id,
        array_unique($permissions)
      );

      $calculated_permissions->addItem($item);
    }

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateMemberPermissions(AccountInterface $account) {
    $cid = 'group_member_permissions_' . $account->id();

    // Retrieve the permissions from the static cache if available.
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }
    // Retrieve the permissions from the persistent cache if available.
    elseif ($cache = $this->cache->get($cid)) {
      $calculated_permissions = $cache->data;
      $cache_tags = $cache->tags;
    }
    // Otherwise build the permissions and store them in the persistent cache.
    else {
      $calculated_permissions = $this->buildMemberPermissions($account);
      $cache_tags = $calculated_permissions->getCacheTags();
      $this->cache->set($cid, $calculated_permissions, Cache::PERMANENT, $cache_tags);
    }

    // Store the permissions in the static cache.
    $this->static->set($cid, $calculated_permissions, Cache::PERMANENT, $cache_tags);

    return $calculated_permissions;
  }

  /**
   * Builds an authenticated user's member permissions.
   *
   * The permissions you have for each group type as an outsider are a
   * combination of the permissions configured on the outsider role and those
   * configured on the group roles you receive through role synchronization.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to build the member permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissions
   *   An object representing the member group permissions.
   */
  protected function buildMemberPermissions(AccountInterface $account) {
    $calculated_permissions = new CalculatedGroupPermissions();

    // @todo Use a cache tag for memberships (e.g.: when new one is added).
    // If the user gets added to or removed from a group, their account will
    // be re-saved in GroupContent::postDelete() and GroupContent::postSave().
    // This means we can add the user's cacheable metadata to invalidate this
    // list of permissions whenever the user is saved.
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $calculated_permissions->addCacheableDependency($user);

    foreach ($this->membershipLoader->loadByUser($account) as $group_membership) {
      $group_id = $group_membership->getGroup()->id();
      $permissions = [];

      // If the membership gets new roles or is stripped from some roles, we
      // need to recalculate the permissions.
      $calculated_permissions->addCacheableDependency($group_membership);

      foreach ($group_membership->getRoles() as $group_role) {
        $permissions = array_merge($permissions, $group_role->getPermissions());
        $calculated_permissions->addCacheableDependency($group_role);
      }

      // Make sure the permissions only appear once per group.
      $item = new CalculatedGroupPermissionsItem(
        CalculatedGroupPermissionsItemInterface::SCOPE_GROUP,
        $group_id,
        array_unique($permissions)
      );

      $calculated_permissions->addItem($item);
    }

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateAuthenticatedPermissions(AccountInterface $account) {
    $calculated_permissions = new CalculatedGroupPermissions();
    return $calculated_permissions
      ->merge($this->calculateOutsiderPermissions($account))
      ->merge($this->calculateMemberPermissions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account) {
    return $account->isAnonymous()
      ? $this->calculateAnonymousPermissions()
      : $this->calculateAuthenticatedPermissions($account);
  }

}
