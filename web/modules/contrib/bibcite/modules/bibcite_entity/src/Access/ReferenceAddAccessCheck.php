<?php

namespace Drupal\bibcite_entity\Access;

use Drupal\bibcite_entity\Entity\ReferenceTypeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to for reference add pages.
 *
 * @ingroup reference_access
 */
class ReferenceAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the reference add page for the reference type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\bibcite_entity\Entity\ReferenceTypeInterface $bibcite_reference_type
   *   (optional) The reference type.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, ReferenceTypeInterface $bibcite_reference_type = NULL) {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('bibcite_reference');
    // If checking whether a reference of a particular type may be created.
    if ($bibcite_reference_type) {
      return $access_control_handler->createAccess($bibcite_reference_type->id(), $account, [], TRUE);
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
