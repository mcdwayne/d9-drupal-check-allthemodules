<?php

namespace Drupal\field_collection_access\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;

/**
 * Defines the field collection access view cache context service.
 *
 * Cache context ID: 'user.field_collection_access' (to vary by all operations'
 * grants).
 * Calculated cache context ID: 'user.field_collection_access:%operation', e.g.
 * 'user.field_collection_access:view' (to vary by the view operation's grants).
 *
 * This allows for field collection access grants-sensitive caching when
 * listing field collection items.
 */
class FieldCollectionAccessCacheContext extends UserCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Field Collection access view grants");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($operation = NULL) {
    // If the current user either can bypass field collection access then we
    // don't need to determine the exact field collection grants for the
    // current user.
    $grantStorage = \Drupal::service('field_collection_access.grant_storage');
    if ($grantStorage->hasBypassPermission($this->user)) {
      return 'all';
    }

    // When no specific operation is specified, check the grants for all three
    // possible operations.
    if ($operation === NULL) {
      $result = [];
      foreach (['view', 'update', 'delete'] as $op) {
        $result[] = $this->checkFieldCollectionItemGrants($op);
      }
      return implode('-', $result);
    }
    else {
      return $this->checkFieldCollectionItemGrants($operation);
    }
  }

  /**
   * Checks the field collection grants for the given operation.
   *
   * @param string $operation
   *   The operation to check the field collection grants for.
   *
   * @return string
   *   The string representation of the cache context.
   */
  protected function checkFieldCollectionItemGrants($operation) {
    $grantStorage = \Drupal::service('field_collection_access.grant_storage');

    // When checking the grants for the 'view' operation and the current user
    // has a global view grant (i.e. a view grant for field collection ID 0) —
    // note that this is automatically the case if no field collection access
    // modules exist (no hook_field_collection_access_grants() implementations)
    // then we don't need to determine the exact field collection view grants
    // for the current user.
    if ($operation === 'view' && $grantStorage->hasBypassPermission($this->user)) {
      return 'view.all';
    }

    $grants = $grantStorage->getUserGrants($operation, $this->user);
    $grants_context_parts = [];
    foreach ($grants as $realm => $gids) {
      $grants_context_parts[] = $realm . ':' . implode(',', $gids);
    }
    return $operation . '.' . implode(';', $grants_context_parts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($operation = NULL) {
    $cacheable_metadata = new CacheableMetadata();

    if (!\Drupal::moduleHandler()->getImplementations('field_collection_item_grants')) {
      return $cacheable_metadata;
    }

    // The field collection grants may change if the user is updated. (The
    // max-age is set tozero below, but sites may override this cache context,
    // and change it to anon-zero value. In such cases, this cache tag is
    // needed for correctness.)
    $cacheable_metadata->setCacheTags(['user:' . $this->user->id()]);

    // If the site is using field collection grants, this cache context can not
    // be optimized.
    return $cacheable_metadata->setCacheMaxAge(0);
  }

}
