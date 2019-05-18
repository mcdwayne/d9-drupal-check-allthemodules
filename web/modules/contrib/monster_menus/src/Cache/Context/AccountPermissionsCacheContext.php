<?php

namespace Drupal\monster_menus\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\monster_menus\Session\AccountProxy;
use Drupal\user\Entity\Role;

/**
 * Defines the AccountPermissionsCacheContext service, for "per permission"
 * caching. Overrides Drupal\Core\Cache\Context\AccountPermissionsCacheContext
 * to provide cache contexts that include MM groups tied to roles.
 *
 * Cache context ID: 'user.permissions'.
 */
class AccountPermissionsCacheContext extends \Drupal\Core\Cache\Context\AccountPermissionsCacheContext {

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    static $tags;

    /** @var CacheableMetadata $cacheable_metadata */
    $cacheable_metadata = parent::getCacheableMetadata();
    if (!$this->user->isAnonymous()) {
      if (!isset($tags)) {
        $tags = [];
        if ($rids = AccountProxy::getRolesHavingMMGroups()) {
          /** @var Role $role */
          foreach (\Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple($rids) as $role) {
            // Permissions depend on all roles that are tied to MM groups.
            $tags[] = 'config:user.role.' . $role->id();
            // They also depend on the MM group itself.
            $tags[] = 'mm_tree:' . $role->get('mm_gid');
          }
        }
      }
      return $cacheable_metadata->addCacheTags($tags);
    }

    return $cacheable_metadata;
  }

}
