<?php

namespace Drupal\field_collection_access\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\field_collection\Entity\FieldCollectionItem;

/**
 * Determines access to operations on the field collection item's host.
 */
class FieldCollectionAccessGrantsOperationCheck implements AccessInterface {

  /**
   * Checks access to the operation on the field collection item's host.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current request route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string|null $field_collection_item_revision
   *   Field collection item Revision.
   * @param \Drupal\field_collection\Entity\FieldCollectionItem $field_collection_item
   *   The currently logged in account.
   *
   *   TODO: Document params.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, AccountInterface $account, $field_collection_item_revision = NULL, FieldCollectionItem $field_collection_item = NULL) {
    $operation = $route->getRequirement('_field_collection_access_grants');
    return AccessResult::allowedIf($field_collection_item && $field_collection_item->access($operation, $account))->cachePerUser();
  }

}
